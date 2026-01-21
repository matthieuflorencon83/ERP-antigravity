import uuid
import logging
from django.shortcuts import render, redirect, get_object_or_404
from django.contrib import messages
from django.db.models import Q
from .models import Document, Client, Fournisseur, Article, Commande, Affaire
# On importe tout ce dont on a besoin depuis services
from .services import analyze_document, save_extracted_data, generate_readable_id
from .forms import ClientForm, FournisseurForm, ArticleForm

logger = logging.getLogger(__name__)

def home(request):
    return render(request, 'dashboard.html')

# --- LISTES (LECTURE) ---

def client_list(request):
    # Base QuerySet
    queryset = Client.objects.prefetch_related('corecontact_set').all().order_by('nom')

    # Filtres Dynamiques (Inline Filters)
    filter_params = {
        'nom': request.GET.get('nom', ''),
        'ville': request.GET.get('ville', ''), # Correspondra à adresse_facturation contient
        'email': request.GET.get('email', ''),
        'tel': request.GET.get('tel', ''),
    }

    if filter_params['nom']:
        queryset = queryset.filter(Q(nom__icontains=filter_params['nom']) | Q(prenom__icontains=filter_params['nom']))
    if filter_params['ville']:
        queryset = queryset.filter(adresse_facturation__icontains=filter_params['ville'])
    if filter_params['email']:
        queryset = queryset.filter(email_client__icontains=filter_params['email'])
    if filter_params['tel']:
        queryset = queryset.filter(telephone_client__icontains=filter_params['tel'])

    # Compatibility with generic search input if still used
    q = request.GET.get('q', '')
    if q:
        queryset = queryset.filter(
             Q(nom__icontains=q) | 
             Q(prenom__icontains=q) | 
             Q(email_client__icontains=q)
        )

    context = {
        'clients': queryset, 
        'filters': filter_params, # Pour remplir les inputs
        'object_list': queryset # Pour le compteur générique
    }
    
    # Suppression de la logique HTMX partielle complexe pour ce nouveau design full-page
    # Mais on garde si demandé pour navigation fluide (à voir)
    # Pour l'instant on rend le nouveau template complet
    return render(request, 'core/client_list.html', context)

def fournisseur_list(request):
    queryset = Fournisseur.objects.prefetch_related('corecontact_set').all().order_by('nom_fournisseur')
    
    # Filtres
    filter_params = {
        'nom': request.GET.get('nom', ''),
        'code': request.GET.get('code', ''),
        'ville': request.GET.get('ville', ''),
    }

    if filter_params['nom']:
        queryset = queryset.filter(nom_fournisseur__icontains=filter_params['nom'])
    if filter_params['code']:
        queryset = queryset.filter(code_fournisseur__icontains=filter_params['code'])
    if filter_params['ville']:
        queryset = queryset.filter(adresse__icontains=filter_params['ville'])

    # Old search compat
    q = request.GET.get('q', '')
    if q:
        queryset = queryset.filter(Q(nom_fournisseur__icontains=q) | Q(siret__icontains=q))

    context = {
        'fournisseurs': queryset,
        'filters': filter_params,
        'object_list': queryset
    }
    return render(request, 'core/fournisseur_list.html', context)

def article_list(request):
    queryset = Article.objects.select_related('fournisseur').all().order_by('designation')
    
    # Filtres
    filter_params = {
        'designation': request.GET.get('designation', ''),
        'fournisseur': request.GET.get('fournisseur', ''),
        'ref': request.GET.get('ref', ''),
    }

    if filter_params['designation']:
        queryset = queryset.filter(Q(designation__icontains=filter_params['designation']) | Q(code_article__icontains=filter_params['designation']))
    if filter_params['fournisseur']:
        queryset = queryset.filter(fournisseur__nom_fournisseur__icontains=filter_params['fournisseur'])
    if filter_params['ref']:
        queryset = queryset.filter(ref_fournisseur__icontains=filter_params['ref'])

    # Old search compat
    q = request.GET.get('q', '')
    if q:
         queryset = queryset.filter(designation__icontains=q)

    context = {
        'articles': queryset,
        'filters': filter_params,
        'object_list': queryset
    }
    return render(request, 'core/article_list.html', context)

# --- CREATION MANUELLE ---

# --- CRUD UNIFIÉ (ADD / EDIT) ---

def client_edit(request, pk=None):
    if pk:
        client = get_object_or_404(Client, pk=pk)
    else:
        client = None

    if request.method == 'POST':
        form = ClientForm(request.POST, request.FILES, instance=client)
        if form.is_valid():
            if not client: # Creation
                obj = form.save(commit=False)
                # Génération ID lisible
                obj.id = generate_readable_id(obj.nom, "CLI")
                obj.save()
                messages.success(request, f"Client {obj.nom} créé avec succès.")
            else:
                form.save()
                messages.success(request, f"Client {client.nom} modifié avec succès.")
            return redirect('client_list')
    else:
        form = ClientForm(instance=client)

    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/form_snippet.html', {'form': form, 'is_htmx': True})

    return render(request, 'core/form_generic.html', {'form': form})

def fournisseur_edit(request, pk=None):
    if pk:
        fournisseur = get_object_or_404(Fournisseur, pk=pk)
    else:
        fournisseur = None

    if request.method == 'POST':
        form = FournisseurForm(request.POST, request.FILES, instance=fournisseur)
        if form.is_valid():
            if not fournisseur: # Creation
                obj = form.save(commit=False)
                # Génération ID lisible
                obj.id = generate_readable_id(obj.nom_fournisseur, "FRN")
                obj.save()
                messages.success(request, f"Fournisseur {obj.nom_fournisseur} créé avec succès.")
            else:
                form.save()
                messages.success(request, f"Fournisseur {fournisseur.nom_fournisseur} modifié avec succès.")
            return redirect('fournisseur_list')
    else:
        form = FournisseurForm(instance=fournisseur)

    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/form_snippet.html', {'form': form, 'is_htmx': True})

    return render(request, 'core/form_generic.html', {'form': form})

def article_edit(request, pk=None):
    if pk:
        article = get_object_or_404(Article, pk=pk)
    else:
        article = None

    if request.method == 'POST':
        form = ArticleForm(request.POST, request.FILES, instance=article)
        if form.is_valid():
            obj = form.save()
            action = "modifié" if pk else "ajouté"
            messages.success(request, f"Article {obj.designation} {action}.")
            return redirect('article_list')
    else:
        form = ArticleForm(instance=article)

    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/form_snippet.html', {'form': form, 'is_htmx': True})

    return render(request, 'core/form_generic.html', {'form': form})

# --- DOCUMENT & IA (LE COEUR DU SYSTEME) ---

def upload_document(request):
    doc_type_input = request.POST.get('document_type')
    
    if request.method == 'POST':
        if not request.FILES.get('file'):
            messages.error(request, "Aucun fichier sélectionné.")
            return render(request, 'documents/upload_v2.html', {'doc_type_selected': doc_type_input})
            
        uploaded_file = request.FILES['file']
        doc = None
        try:
            # 1. Sauvegarde brute
            doc = Document.objects.create(fichier=uploaded_file)
            
            # 2. Analyse IA
            logger.info(f"Analyzing document {doc.id} (Type hint: {doc_type_input})")
            analysis_result = analyze_document(uploaded_file, doc_type_hint=doc_type_input)
            
            # 3. Mise à jour Document
            doc.ai_response = analysis_result
            
            if analysis_result and not analysis_result.get('error'):
                doc.type_document = analysis_result.get('type_document')
                # Gestion propre de la date via le JSON IA
                date_str = analysis_result.get('date_document')
                if date_str:
                    doc.date_document = date_str
                doc.save()
            
            return render(request, 'documents/upload_v2.html', {
                'document': doc,
                'analysis_result': analysis_result,
                'doc_type_selected': doc_type_input
            })
            
        except Exception as e:
            logger.exception("Upload view failed")
            return render(request, 'documents/upload_v2.html', {
                'document': doc, # Pass document even on error so viewer works
                'error': str(e),
                'doc_type_selected': doc_type_input
            })

    return render(request, 'documents/upload_v2.html', {
        'analysis_result': {
            'type_document': None,
            'date_document': '',
            'client': {'nom': '', 'siret': '', 'email': ''},
            'fournisseur': {'nom': '', 'siret': '', 'email': ''},
            'references': {'num_commande': '', 'num_arc': '', 'num_bl': '', 'num_facture': '', 'num_devis': ''},
            'totaux': {'ht': '', 'ttc': '', 'tva': ''},
            'date_livraison_prevue': ''
        }
    })


def document_detail(request, pk):
    doc = get_object_or_404(Document, pk=pk)
    
    # Détermination sécurisée du type
    ai_res = doc.ai_response or {}
    doc_type = ai_res.get('type') or ai_res.get('type_document') or "AUTRE"

    if request.method == 'POST':
        try:
            # CORRECTION CRITIQUE : Utiliser 'type_document' comme clé attendue par services.py
            final_data = {"type_document": doc_type}

            # 1. RECONSTRUCTION DU JSON SELON TYPE (Mapping Form -> Service Data)
            if doc_type == "DEVIS_CLIENT":
                final_data.update({
                    "date_document": request.POST.get('date_document'), # NEW
                    "references": {
                        "num_devis": request.POST.get('numero_prodevis'),
                    },
                    "client": {
                        "nom": request.POST.get('client_nom'),
                        "tel": request.POST.get('client_tel'), # Important pour le service
                        "email": request.POST.get('email'),
                        "adresse": request.POST.get('client_adresse') or request.POST.get('adresse_chantier')
                    },
                    "totaux": {
                        "ht": float(request.POST.get('total_vente_ht') or 0),
                    }
                })

            elif doc_type == "BON_COMMANDE":
                 final_data.update({
                    "date_document": request.POST.get('date_document'), # NEW
                    "references": {
                        "num_commande": request.POST.get('num_commande'),
                    },
                    "fournisseur": {
                        "nom": request.POST.get('fournisseur_nom'),
                        "siret": request.POST.get('fournisseur_siret'), # NEW
                        "email": request.POST.get('fournisseur_email'), # NEW
                    },
                    "totaux": {
                         "ht": float(request.POST.get('total_achat_ht') or 0) 
                    },
                    "lignes": [] # Rempli plus bas
                })

            elif doc_type == "ARC_FOURNISSEUR":
                final_data.update({
                    "date_document": request.POST.get('date_document'), # NEW
                    "date_livraison_prevue": request.POST.get('date_livraison_prevue'), # NEW
                    "fournisseur": {
                        "nom": request.POST.get('fournisseur_nom'),
                        "siret": request.POST.get('fournisseur_siret'),
                        "email": request.POST.get('fournisseur_email'),
                    },
                    "references": {
                        "num_arc": request.POST.get('num_arc'),
                        "num_commande": request.POST.get('num_bdc_lie') 
                    },
                    "totaux": {
                         "ht": float(request.POST.get('total_achat_ht') or 0) 
                    },
                    "lignes": []
                })

            elif doc_type == "BON_LIVRAISON":
                final_data.update({
                    "date_document": request.POST.get('date_document'),
                    "fournisseur": { "nom": request.POST.get('fournisseur_nom') },
                    "references": { 
                        "num_bl": request.POST.get('num_bl'),
                        "num_commande": request.POST.get('num_bdc_lie') 
                    },
                    "lignes": []
                })

            elif doc_type == "FACTURE":
                final_data.update({
                    "date_document": request.POST.get('date_document'),
                    "references": {
                        "num_facture": request.POST.get('num_facture'),
                        "num_commande": request.POST.get('num_bdc_lie'),
                        "num_devis": request.POST.get('num_bdc_lie') # Fallback simple
                    },
                    "totaux": {
                         "ht": float(request.POST.get('total_achat_ht') or 0) 
                    },
                    "lignes": []
                })

            # 2. GESTION DES LIGNES (BOUCLE UNIVERSELLE)
            # On récupère les lignes du formulaire HTML pour les mettre dans le JSON
            if doc_type in ["BON_COMMANDE", "ARC_FOURNISSEUR", "BON_LIVRAISON"]:
                i = 0
                while True:
                    base_key = f"lignes[{i}]"
                    # On vérifie si la ligne existe dans le POST
                    if f"{base_key}[designation]" in request.POST or f"{base_key}[code_article]" in request.POST:
                        ligne = {
                            "code": request.POST.get(f"{base_key}[code_article]"),
                            "designation": request.POST.get(f"{base_key}[designation]"),
                            "quantite": float(request.POST.get(f"{base_key}[quantite]") or 0),
                            "prix_unitaire": float(request.POST.get(f"{base_key}[prix_unitaire]") or 0),
                            "ral": request.POST.get(f"{base_key}[ral]"),
                            "finition": request.POST.get(f"{base_key}[finition]"),
                        }
                        # Nettoyage
                        target_list = final_data.setdefault("lignes", [])
                        target_list.append(ligne)
                        i += 1
                    else:
                        break

            # 3. APPEL DU SERVICE INTELLIGENT
            # result_obj peut être une Affaire OU une Commande
            result_obj = save_extracted_data(doc, final_data)
            
            # 4. MESSAGE DE SUCCES DYNAMIQUE
            if result_obj:
                # Si c'est une Affaire (Devis)
                if isinstance(result_obj, Affaire):
                    messages.success(request, f"Affaire '{result_obj.nom_affaire}' initialisée avec succès !")
                # Si c'est une Commande (BDC, ARC, BL)
                elif isinstance(result_obj, Commande):
                    messages.success(request, f"Commande {result_obj.numero_bdc or result_obj.numero_arc} traitée. Statut: {result_obj.get_statut_display()}")
                # Si c'est un Document (Cas ARC Orphelin)
                elif isinstance(result_obj, Document):
                    messages.success(request, f"Données extraites avec succès. ARC en attente de liaison (BDC introuvable).")
            else:
                messages.warning(request, "Traitement effectué, mais aucun objet principal n'a été retourné.")

            return redirect('home')

        except Exception as e:
            logger.exception("Erreur validation document")
            messages.error(request, f"Erreur critique lors de la sauvegarde : {str(e)}")
            # On reste sur la page pour voir l'erreur
    
    return render(request, 'documents/upload_v2.html', {
        'analysis_result': doc.ai_response,
        'document': doc,
        'doc_type_selected': doc_type 
    })