import json
import re
import logging
import random
import google.generativeai as genai
from django.conf import settings
from django.db import transaction
from .models import Document, Client, Fournisseur, Affaire, Commande, Article, Lignecommande

logger = logging.getLogger(__name__)

def generate_readable_id(prefix, name):
    """Génère un ID type FRN-MACCAR-823"""
    if not name: return f"{prefix}-INCONNU-{random.randint(100,999)}"
    clean = re.sub(r'[^A-Z0-9]', '', name.upper())[:6]
    return f"{prefix}-{clean}-{random.randint(100, 999)}"

def analyze_document(file_obj, doc_type_hint=None):
    api_keys = getattr(settings, 'GEMINI_API_KEYS', [])
    if not api_keys:
        single_key = getattr(settings, 'GEMINI_API_KEY', None)
        if single_key: api_keys = [single_key]
    
    if not api_keys: return {"error": "Aucune clé API configurée"}
    
    # Randomize start to distribute load if we have multiple workers (optional, but good practice)
    # random.shuffle(api_keys) # Uncomment if you want random distribution
    
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
      "totaux": { "ht": Float, "ttc": Float },
      "lignes": [{ "code": "String", "designation": "String", "quantite": Float, "prix_unitaire": Float, "ral": "String", "finition": "String" }]
    }
    """

    for i, key in enumerate(api_keys):
        try:
            genai.configure(api_key=key)
            
            # Auto-choix du modèle (uniquement au premier tour ou recheck ?)
            # On le fait simple : flash
            chosen_model = "gemini-flash-latest"
            
            model = genai.GenerativeModel(chosen_model)
            file_obj.seek(0)
            response = model.generate_content([system_prompt, {"mime_type": "application/pdf", "data": file_obj.read()}])
            
            data = json.loads(response.text.replace("```json", "").replace("```", "").strip())
            
            # Post-traitement Date Livraison (Semaine -> Date)
            if data.get('date_livraison_prevue'):
                data['date_livraison_prevue'] = normalize_date_ou_semaine(data['date_livraison_prevue'])
                
            return data
        
        except Exception as e:
            logger.warning(f"API Key {i+1}/{len(api_keys)} failed: {str(e)}")
            last_error = str(e)
            continue # Try next key

    return {"error": f"Tous les quotas sont épuisés ou erreur API. Dernière erreur : {last_error}"}

def normalize_date_ou_semaine(val):
    """Convertit 'Semaine X/YYYY' ou 'X/YYYY' en date (Lundi de la semaine)."""
    if not val: return None
    import datetime
    
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
    
    return val # Retourne brut si échec (pour affichage manuel)

def save_extracted_data(doc_obj, data):
    with transaction.atomic():
        # 1. CLIENT (ID TEXTE)
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

        # 2. FOURNISSEUR (ID TEXTE)
        fournisseur = None
        if data.get('fournisseur') and data['fournisseur'].get('nom'):
            f = data['fournisseur']
            fournisseur = Fournisseur.objects.filter(nom_fournisseur=f.get('nom')).first()
            if not fournisseur:
                new_id = generate_readable_id("FRN", f.get('nom'))
                fournisseur = Fournisseur.objects.create(
                    id=new_id, nom_fournisseur=f.get('nom'), siret=f.get('siret')
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
                date_devis=data.get('date_document') # NEW
            )
            doc_obj.affaire = result_obj

        elif doc_type == "BON_COMMANDE":
            # 1. UPSERT FOURNISSEUR (Enrichissement)
            if data.get('fournisseur') and data['fournisseur'].get('nom'):
                 f = data['fournisseur']
                 # On cherche
                 fournisseur = Fournisseur.objects.filter(nom_fournisseur=f.get('nom')).first()
                 if not fournisseur:
                    new_id = generate_readable_id("FRN", f.get('nom'))
                    fournisseur = Fournisseur.objects.create(
                        id=new_id, nom_fournisseur=f.get('nom'), 
                        siret=f.get('siret'), 
                        # email not in model directly? Checking model...
                        # Fournisseur model has 'adresse', 'siret', 'tva...', 'notes'. No email field directly on Fournisseur?
                        # Let's check CoreContact or assume notes/adresse for now or add email if needed.
                        # We'll put email in notes for now to save it.
                        notes=f"Email: {f.get('email')}" if f.get('email') else ""
                    )
                 else:
                     # Update info if missing (optional)
                     if f.get('siret') and not fournisseur.siret:
                         fournisseur.siret = f.get('siret')
                         fournisseur.save()

            # 2. CREATE COMMANDE
            cli_stock, _ = Client.objects.get_or_create(id="CLI-INTERNE", defaults={"nom": "Client Interne"})
            aff_defaut, _ = Affaire.objects.get_or_create(nom_affaire="Commandes Stock", defaults={"client": cli_stock})
            
            result_obj = Commande.objects.create(
                affaire=aff_defaut, 
                numero_bdc=refs.get('num_commande'),
                date_commande=data.get('date_document'), # NEW
                prix_total_ht=totaux.get('ht', 0), 
                statut='COMMANDE'
            )
            for l in lignes:
                art_code = l.get('code') or "DIV"
                art, _ = Article.objects.get_or_create(
                    ref_fournisseur=art_code,
                    defaults={'designation': l.get('designation'), 'fournisseur': fournisseur, 'prix_unitaire_ht': l.get('prix_unitaire', 0)}
                )
                Lignecommande.objects.create(
                    commande=result_obj, article=art, quantite=l.get('quantite', 1),
                    prix_unitaire_ht=l.get('prix_unitaire', 0), ral=l.get('ral'), finition=l.get('finition')
                )
            doc_obj.commande = result_obj

        elif doc_type == "ARC_FOURNISSEUR":
            # 1. Extraction / Mise à jour des articles (Catalogue)
            logger.info(f"Processing ARC Articles. Fournisseur: {fournisseur}, Lignes: {len(lignes)}")
            
            if fournisseur:
                for l in lignes:
                    art_code = l.get('code')
                    logger.debug(f"Article processing: {art_code} - {l.get('designation')}")
                    if art_code:
                        obj, created = Article.objects.get_or_create(
                            ref_fournisseur=art_code,
                            defaults={
                                'designation': l.get('designation'),
                                'fournisseur': fournisseur,
                                'prix_unitaire_ht': l.get('prix_unitaire', 0)
                            }
                        )
                        logger.debug(f"Article {art_code} saved? {created}")
            else:
                logger.warning("Aucun fournisseur identifié pour les articles ARC.")

            # 2. Liaison au BDC (si existant uniquement)
            num_bdc_ref = refs.get('num_commande')
            result_obj = Commande.objects.filter(numero_bdc=num_bdc_ref).first()
            
            if result_obj:
                result_obj.statut = 'CONFIRME_ARC'
                result_obj.numero_arc = refs.get('num_arc')
                result_obj.date_arc = data.get('date_document') # NEW
                
                # Gestion Date Livraison Prévue
                date_liv = data.get('date_livraison_prevue')
                if date_liv:
                    result_obj.date_livraison_prevue = date_liv
                
                result_obj.save()
                doc_obj.commande = result_obj
            else:
                logger.info(f"ARC Orphelin (BDC '{num_bdc_ref}' introuvable). Données extraites.")
                result_obj = doc_obj # Retourne le document lui-même comme succès partiel

        elif doc_type == "BON_LIVRAISON":
            num_bdc_ref = refs.get('num_commande')
            result_obj = Commande.objects.filter(numero_bdc=num_bdc_ref).first()
            
            if result_obj:
                result_obj.statut = 'LIVRE'
                result_obj.numero_bl = refs.get('num_bl')
                date_bl = data.get('date_document')
                if date_bl:
                    result_obj.date_livraison_reelle = date_bl
                
                # On pourrait mettre à jour les quantités reçues ici si l'IA extrait 'reception'
                # Pour l'instant on valide juste le statut.
                result_obj.save()
                doc_obj.commande = result_obj
        
        elif doc_type == "FACTURE":
            # Essai de liaison Commande ou Affaire
            num_bdc_ref = refs.get('num_commande')
            result_obj = Commande.objects.filter(numero_bdc=num_bdc_ref).first()
            
            if result_obj:
                doc_obj.commande = result_obj
                # Optionnel: Remonter le numéro de facture sur l'affaire parente ?
                # result_obj.affaire.num_facture = refs.get('num_facture')
                # result_obj.affaire.save()
            else:
                 # Essai liaison Affaire via num_devis ?
                 num_devis_ref = refs.get('num_devis')
                 aff = Affaire.objects.filter(numero_prodevis=num_devis_ref).first()
                 if aff:
                     aff.num_facture = refs.get('num_facture')
                     aff.save()
                     doc_obj.affaire = aff
                     result_obj = aff
                 else:
                     result_obj = doc_obj # Facture Orpheline

        doc_obj.save()
        return result_obj
    return None