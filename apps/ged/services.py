import json
import re
import logging
import random
import os
import shutil
from typing import Dict, Any, List, Optional, TYPE_CHECKING
from decimal import Decimal
import datetime
import uuid

from google import genai
from google.genai import types

from django.conf import settings
from django.db import transaction
from django.core.files import File
from apps.tiers.models import Client, Fournisseur
from apps.ventes.models import Affaire
from apps.achats.models import Commande, LigneCommande
from apps.catalogue.models import Article
from apps.core.models import CoreParametre 

if TYPE_CHECKING:
    pass

logger = logging.getLogger(__name__)

def generate_readable_id(prefix: str, name: Optional[str]) -> str:
    """Génère un ID type FRN-MACCAR-823"""
    if not name: return f"{prefix}-INCONNU-{random.randint(100,999)}"
    clean = re.sub(r'[^A-Z0-9]', '', name.upper())[:6]
    return f"{prefix}-{clean}-{random.randint(100, 999)}"

def normalize_date_ou_semaine(val: str) -> Optional[str]:
    """Convertit 'Semaine X/YYYY' ou 'X/YYYY' en date (Lundi de la semaine)."""
    if not val: return None
    
    # Format YYYY-MM-DD direct
    if re.match(r'^\d{4}-\d{2}-\d{2}$', val):
        return val

    # Format Semaine
    try:
        # Regex pour "7 / 2025" ou "Semaine 7 2025" ou "07/2025"
        match = re.search(r'(\d{1,2})\s*[/\s]\s*(\d{4})', val)
        if match:
            week = int(match.group(1))
            year = int(match.group(2))
            # Vendredi de la semaine (pour correspondre à la fin de semaine)
            d = datetime.date.fromisocalendar(year, week, 5)
            return d.strftime("%Y-%m-%d")
    except:
        pass
    
    return val # Retourne brut si échec

def analyze_document(file_obj: File, doc_type_hint: Optional[str] = None) -> Dict[str, Any]:
    # 1. Check DB for override
    db_param: Optional[CoreParametre] = CoreParametre.objects.first()
    db_key = db_param.gemini_api_key if db_param else None

    api_keys: List[str] = []
    if db_key:
        api_keys = [db_key]
    else:
        # 2. Fallback to Settings/Env
        api_keys = getattr(settings, 'GEMINI_API_KEYS', [])
        if not api_keys:
            single_key = getattr(settings, 'GEMINI_API_KEY', None)
            if single_key: api_keys = [single_key]
    
    if not api_keys: return {"error": "Aucune clé API configurée"}
    
    last_error = None
    
    system_prompt = """
    Tu es expert BTP. Extrais les données en JSON STRICT.
    {
      "type_document": "DEVIS_CLIENT" | "BON_COMMANDE" | "ARC_FOURNISSEUR" | "BON_LIVRAISON" | "FACTURE",
      "date_document": "YYYY-MM-DD",
      "date_livraison_prevue": "YYYY-MM-DD ou 'Semaine W/YYYY'",
      "numero_document": "String",
      "client": { "nom": "String", "tel": "String", "email": "String", "adresse": "String" },
      "fournisseur": { "nom": "String", "siret": "String", "email": "String" },
      "references": { 
        "num_devis": "String", 
        "num_commande": "String (Chercher 'Réf.client', 'N° Commande', 'Commande client', 'V/Réf')", 
        "num_arc": "String", 
        "num_bl": "String", 
        "num_facture": "String" 
      },
      "totaux": { "ht": "String (ex: 123.45)", "ttc": "String" },
      "lignes": [{ "code": "String", "designation": "String", "quantite": Float, "prix_unitaire": "String (ex: 15.50)", "ral": "String", "finition": "String", "conditionnement": "String (Type d'emballage ex: 'Crt 12', 'Palette', '300ml', vide si inconnu)" }]
    }
    """

    for i, key in enumerate(api_keys):
        try:
            client = genai.Client(api_key=key)
            models_to_try = ["gemini-1.5-flash", "gemini-flash-latest", "gemini-1.5-flash-8b"]
            
            for model_name in models_to_try:
                try:
                    logger.info(f"Trying model {model_name} with key {i+1}")
                    file_obj.seek(0)
                    file_data = file_obj.read()
                    
                    response = client.models.generate_content(
                        model=model_name,
                        contents=[
                            system_prompt,
                            types.Part.from_bytes(data=file_data, mime_type="application/pdf")
                        ]
                    )
                    
                    # Nettoyage de la réponse
                    raw_text = response.text
                    clean_json = raw_text.replace("```json", "").replace("```", "").strip()
                    data: Dict[str, Any] = json.loads(clean_json)
                    
                    if data.get('date_livraison_prevue'):
                        data['date_livraison_prevue'] = normalize_date_ou_semaine(data['date_livraison_prevue'])
                    return data
                    
                except Exception as model_error:
                    last_error = str(model_error)
                    logger.warning(f"Key {i+1} Model {model_name} failed: {last_error}")
                    if "429" in last_error or "quota" in last_error.lower() or "not found" in last_error.lower() or "404" in last_error:
                        continue 
                    else:
                        break 
        except Exception as e:
            logger.warning(f"API Key {i+1}/{len(api_keys)} failed: {str(e)}")
            last_error = str(e)
            continue 

    return {"error": f"Quota épuisé ou erreur API : {last_error}"}

def archive_document_locally(doc_obj):
    try:
        param = CoreParametre.objects.first()
        base_path = param.chemin_stockage_local if param else None
        
        if not base_path or not os.path.isdir(base_path):
            return

        import datetime
        date_str = datetime.date.today().strftime("%Y%m%d")
        if doc_obj.ai_response and doc_obj.ai_response.get('date_document'):
            try:
                d_str = doc_obj.ai_response.get('date_document')
                d = datetime.datetime.strptime(d_str, "%Y-%m-%d")
                date_str = d.strftime("%Y%m%d")
            except: pass

        sup_str = "INC"
        if doc_obj.commande:
            first_line = doc_obj.commande.lignes.first()
            if first_line and first_line.article.fournisseur:
                sup_str = first_line.article.fournisseur.nom_fournisseur[:3].upper()
        elif doc_obj.ai_response and doc_obj.ai_response.get('fournisseur'):
            f_nom = doc_obj.ai_response['fournisseur'].get('nom')
            if f_nom:
                sup_str = re.sub(r'[^A-Z]', '', f_nom.upper())[:3]

        client_affaire_str = "Divers"
        if doc_obj.affaire:
            client_affaire_str = doc_obj.affaire.client.nom if doc_obj.affaire.client else doc_obj.affaire.nom_affaire
        elif doc_obj.commande and doc_obj.commande.affaire:
            client_affaire_str = doc_obj.commande.affaire.client.nom if doc_obj.commande.affaire.client else doc_obj.commande.affaire.nom_affaire
        
        client_affaire_str = re.sub(r'[^a-zA-Z0-9]', '', client_affaire_str)

        designation_str = "Doc"
        if doc_obj.commande:
            designation_str = doc_obj.commande.numero_bdc or doc_obj.commande.designation
        elif doc_obj.affaire:
            designation_str = doc_obj.affaire.numero_prodevis or "Devis"
        
        if doc_obj.ai_response and doc_obj.ai_response.get('numero_document'):
             designation_str = doc_obj.ai_response.get('numero_document')
        
        designation_str = re.sub(r'[^a-zA-Z0-9]', '', str(designation_str))

        type_map = {
            'DEVIS_CLIENT': 'DEVIS',
            'BON_COMMANDE': 'BDC',
            'ARC_FOURNISSEUR': 'ARC',
            'BON_LIVRAISON': 'BL',
            'FACTURE': 'FACTURE'
        }
        type_str = type_map.get(doc_obj.type_document, "DOC")

        _, ext = os.path.splitext(doc_obj.fichier.path)
        new_filename = f"{date_str}_{sup_str}_{client_affaire_str}_{designation_str}_{type_str}{ext}"
        
        doc_type = doc_obj.type_document or "NON_CLASSE"
        target_dir = os.path.join(base_path, doc_type)
        os.makedirs(target_dir, exist_ok=True)

        dest_path = os.path.join(target_dir, new_filename)

        if os.path.exists(dest_path):
            base, ext = os.path.splitext(new_filename)
            dest_path = os.path.join(target_dir, f"{base}_{random.randint(1000,9999)}{ext}")

        shutil.copy2(doc_obj.fichier.path, dest_path)
        logger.info(f"Fichier archivé vers : {dest_path}")

    except Exception as e:
        logger.error(f"Erreur lors de l'archivage local : {str(e)}")

def save_extracted_data(doc_obj, data):
    with transaction.atomic():
        # 1. CLIENT
        client = None
        if data.get('client') and data['client'].get('nom'):
            c = data['client']
            client = Client.objects.filter(nom=c.get('nom')).first()
            if not client:
                new_id = generate_readable_id("CLI", c.get('nom'))
                client = Client.objects.create(
                    id=new_id, nom=c.get('nom'),
                    telephone_client=c.get('tel'), email_client=c.get('email'), adresse_chantier=c.get('adresse')
                )

        # 2. FOURNISSEUR
        fournisseur = None
        if data.get('fournisseur') and data['fournisseur'].get('nom'):
            f_data = data['fournisseur']
            nom_clean = f_data.get('nom', '').strip()
            
            fournisseur = Fournisseur.objects.filter(nom_fournisseur__iexact=nom_clean).first()
            
            if not fournisseur:
                new_id = generate_readable_id("FRN", nom_clean)
                fournisseur = Fournisseur.objects.create(
                    id=new_id, 
                    nom_fournisseur=nom_clean, 
                    siret=f_data.get('siret'),
                    adresse=f_data.get('adresse'),
                    notes=f"Email: {f_data.get('email')}" if f_data.get('email') else ""
                )
            else:
                 if f_data.get('siret') and not fournisseur.siret:
                     fournisseur.siret = f_data.get('siret')
                 fournisseur.save()

            if not fournisseur.corecontact_set.exists():
                 from apps.tiers.models import CoreContact
                 c_nom = "Service " + (nom_clean[:15] + '...' if len(nom_clean)>15 else nom_clean)
                 CoreContact.objects.create(
                     id=uuid.uuid4(),
                     fournisseur=fournisseur,
                     nom=c_nom,
                     email=f_data.get('email', ''),
                     type_contact='Commercial'
                 )

        # 3. ROUTAGE
        doc_type = data.get('type_document')
        refs = data.get('references', {})
        totaux = data.get('totaux', {})
        lignes = data.get('lignes', [])
        result_obj = None

        if doc_type == "DEVIS_CLIENT" and client:
            result_obj = Affaire.objects.create(
                client=client, nom_affaire=f"Chantier {client.nom}",
                numero_prodevis=refs.get('num_devis'), 
                total_vente_ht=totaux.get('ht', 0),
                date_devis=data.get('date_document')
            )
            doc_obj.affaire = result_obj

        elif doc_type == "BON_COMMANDE":
            if data.get('fournisseur') and data['fournisseur'].get('nom'):
                 f = data['fournisseur']
                 fournisseur = Fournisseur.objects.filter(nom_fournisseur=f.get('nom')).first()
                 if not fournisseur:
                    new_id = generate_readable_id("FRN", f.get('nom'))
                    fournisseur = Fournisseur.objects.create(
                        id=new_id, nom_fournisseur=f.get('nom'), 
                        siret=f.get('siret'), 
                        notes=f"Email: {f.get('email')}" if f.get('email') else ""
                    )

            cli_stock, _ = Client.objects.get_or_create(id="CLI-INTERNE", defaults={"nom": "Client Interne"})
            aff_defaut, _ = Affaire.objects.get_or_create(nom_affaire="Commandes Stock", defaults={"client": cli_stock})
            
            result_obj = Commande.objects.create(
                affaire=aff_defaut,
                fournisseur=fournisseur,
                numero_bdc=refs.get('num_commande'),
                designation=f"Commande {refs.get('num_commande') or 'Inconnue'}", 
                date_commande=data.get('date_document'), 
                total_ht=totaux.get('ht', 0), 
                statut='ENVOYEE'
            )
            for l in lignes:
                art_code = l.get('code') or "DIV"
                des = l.get('designation') or "Article Sans Nom"
                if len(des) > 255: des = des[:252] + "..."
                
                art, _ = Article.objects.get_or_create(
                    ref_fournisseur=art_code,
                    defaults={
                        'designation': des, 
                        'fournisseur': fournisseur, 
                        'prix_unitaire_ht': l.get('prix_unitaire', 0),
                        'conditionnement': l.get('conditionnement')
                    }
                )
                LigneCommande.objects.create(
                    commande=result_obj, 
                    article=art, 
                    quantite=l.get('quantite', 1),
                    prix_unitaire=l.get('prix_unitaire', 0),
                    designation=art.designation[:255]
                )
            doc_obj.commande = result_obj

        elif doc_type == "ARC_FOURNISSEUR":
            logger.info(f"Processing ARC Articles. Fournisseur: {fournisseur}, Lignes: {len(lignes)}")
            if fournisseur:
                for l in lignes:
                    art_code = l.get('code')
                    if art_code:
                        des = l.get('designation') or "Article Sans Nom"
                        if len(des) > 255: des = des[:252] + "..."
                        Article.objects.get_or_create(
                            ref_fournisseur=art_code,
                            defaults={
                                'designation': des,
                                'fournisseur': fournisseur,
                                'prix_unitaire_ht': l.get('prix_unitaire', 0),
                                'conditionnement': l.get('conditionnement')
                            }
                        )
            
            num_bdc_ref = refs.get('num_commande')
            result_obj = Commande.objects.filter(numero_bdc=num_bdc_ref).first()
            
            if result_obj:
                result_obj.statut = 'CONFIRME_ARC'
                result_obj.numero_arc = refs.get('num_arc')
                result_obj.date_arc = data.get('date_document')
                date_liv = data.get('date_livraison_prevue')
                if date_liv:
                    result_obj.date_livraison_prevue = date_liv
                result_obj.save()
                doc_obj.commande = result_obj
            else:
                logger.info(f"ARC Orphelin (BDC '{num_bdc_ref}' introuvable).")
                result_obj = doc_obj

        elif doc_type == "BON_LIVRAISON":
            num_bdc_ref = refs.get('num_commande')
            result_obj = Commande.objects.filter(numero_bdc=num_bdc_ref).first()
            if result_obj:
                result_obj.statut = 'LIVRE'
                result_obj.numero_bl = refs.get('num_bl')
                date_bl = data.get('date_document')
                if date_bl:
                    result_obj.date_livraison_reelle = date_bl
                result_obj.save()
                doc_obj.commande = result_obj
        
        elif doc_type == "FACTURE":
            num_bdc_ref = refs.get('num_commande')
            result_obj = Commande.objects.filter(numero_bdc=num_bdc_ref).first()
            if result_obj:
                doc_obj.commande = result_obj
            else:
                 num_devis_ref = refs.get('num_devis')
                 aff = Affaire.objects.filter(numero_prodevis=num_devis_ref).first()
                 if aff:
                     aff.num_facture = refs.get('num_facture')
                     aff.save()
                     doc_obj.affaire = aff
                     result_obj = aff
                 else:
                     result_obj = doc_obj 

        doc_obj.save()
        archive_document_locally(doc_obj)
        return result_obj

def format_form_data(post_data: Dict, doc_type: str) -> Dict[str, Any]:
    """Helper to transform HTML Form POST data into Standard JSON for Service"""
    final_data = {"type_document": doc_type}
    
    # 1. MAPPING SIMPLE
    if doc_type == "DEVIS_CLIENT":
        final_data.update({
            "date_document": post_data.get('date_document'),
            "references": { "num_devis": post_data.get('numero_prodevis') },
            "client": {
                "nom": post_data.get('client_nom'),
                "tel": post_data.get('client_tel'),
                "email": post_data.get('email'),
                "adresse": post_data.get('client_adresse') or post_data.get('adresse_chantier')
            },
            "totaux": { "ht": Decimal(post_data.get('total_vente_ht') or '0') }
        })

    elif doc_type == "BON_COMMANDE":
         final_data.update({
            "date_document": post_data.get('date_document'),
            "references": { "num_commande": post_data.get('num_commande') },
            "fournisseur": {
                "nom": post_data.get('fournisseur_nom'),
                "siret": post_data.get('fournisseur_siret'),
                "email": post_data.get('fournisseur_email'),
            },
            "totaux": { "ht": Decimal(post_data.get('total_achat_ht') or '0') },
            "lignes": []
        })

    elif doc_type == "ARC_FOURNISSEUR":
        final_data.update({
            "date_document": post_data.get('date_document'),
            "date_livraison_prevue": post_data.get('date_livraison_prevue'),
            "fournisseur": {
                "nom": post_data.get('fournisseur_nom'),
                "siret": post_data.get('fournisseur_siret'),
                "email": post_data.get('fournisseur_email'),
            },
            "references": {
                "num_arc": post_data.get('num_arc'),
                "num_commande": post_data.get('num_bdc_lie') 
            },
            "totaux": { "ht": Decimal(post_data.get('total_achat_ht') or '0') },
            "lignes": []
        })

    elif doc_type == "BON_LIVRAISON":
        final_data.update({
            "date_document": post_data.get('date_document'),
            "fournisseur": { "nom": post_data.get('fournisseur_nom') },
            "references": { 
                "num_bl": post_data.get('num_bl'),
                "num_commande": post_data.get('num_bdc_lie') 
            },
            "lignes": []
        })

    elif doc_type == "FACTURE":
        final_data.update({
            "date_document": post_data.get('date_document'),
            "references": {
                "num_facture": post_data.get('num_facture'),
                "num_commande": post_data.get('num_bdc_lie'),
                "num_devis": post_data.get('num_bdc_lie')
            },
            "totaux": { "ht": Decimal(post_data.get('total_achat_ht') or '0') },
            "lignes": []
        })

    # 2. LIGNES
    if doc_type in ["BON_COMMANDE", "ARC_FOURNISSEUR", "BON_LIVRAISON"]:
        i = 0
        while True:
            base_key = f"lignes[{i}]"
            # Check presence using designation or code
            key_check = f"{base_key}[designation]"
            if key_check in post_data or f"{base_key}[code_article]" in post_data:
                ligne = {
                    "code": post_data.get(f"{base_key}[code_article]"),
                    "designation": post_data.get(f"{base_key}[designation]"),
                    "quantite": Decimal(post_data.get(f"{base_key}[quantite]") or '0'),
                    "prix_unitaire": Decimal(post_data.get(f"{base_key}[prix_unitaire]") or '0'),
                    "ral": post_data.get(f"{base_key}[ral]"),
                    "finition": post_data.get(f"{base_key}[finition]"),
                    "conditionnement": post_data.get(f"{base_key}[conditionnement]")
                }
                target_list = final_data.setdefault("lignes", [])
                target_list.append(ligne)
                i += 1
            else:
                break
                
    return final_data
