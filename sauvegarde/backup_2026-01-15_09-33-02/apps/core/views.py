import uuid
import logging
from django.shortcuts import render, redirect, get_object_or_404
from django.http import HttpResponse
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Q
from django.db.models import Q, Sum, Count, Case, When, Value, IntegerField
from .models import Document, Client, Fournisseur, Article, Commande, Affaire, Besoin
# On importe tout ce dont on a besoin depuis services
from .services import analyze_document, save_extracted_data, generate_readable_id
from .forms import ClientForm, FournisseurForm, ArticleForm, CommandeForm

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
        queryset = queryset.annotate(
            is_start=Case(
                When(nom__istartswith=q, then=Value(1)),
                default=Value(0),
                output_field=IntegerField(),
            )
        ).order_by('-is_start', 'nom')

    context = {
        'clients': queryset, 
        'filters': filter_params, # Pour remplir les inputs
        'object_list': queryset # Pour le compteur générique
    }
    
    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/client_table_body.html', context)

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

    q = request.GET.get('q', '')
    if q:
        queryset = queryset.filter(
            Q(nom_fournisseur__icontains=q) | 
            Q(siret__icontains=q) |
            Q(code_fournisseur__icontains=q)
        )
        queryset = queryset.annotate(
            is_start=Case(
                When(nom_fournisseur__istartswith=q, then=Value(1)),
                default=Value(0),
                output_field=IntegerField(),
            )
        ).order_by('-is_start', 'nom_fournisseur')

    context = {
        'fournisseurs': queryset,
        'filters': filter_params,
        'object_list': queryset
    }
    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/fournisseur_table_body.html', context)

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
        queryset = queryset.filter(Q(designation__icontains=filter_params['designation']) | Q(ref_fournisseur__icontains=filter_params['designation']))
    if filter_params['fournisseur']:
        queryset = queryset.filter(fournisseur__nom_fournisseur__icontains=filter_params['fournisseur'])
    if filter_params['ref']:
        queryset = queryset.filter(ref_fournisseur__icontains=filter_params['ref'])

    q = request.GET.get('q', '')
    if q:
         queryset = queryset.filter(
             Q(designation__icontains=q) |
             Q(ref_fournisseur__icontains=q)
         )
         # Prioritize items starting with the query
         queryset = queryset.annotate(
             is_start=Case(
                 When(designation__istartswith=q, then=Value(1)),
                 default=Value(0),
                 output_field=IntegerField(),
             )
         ).order_by('-is_start', 'designation')

    # Pagination
    paginator = Paginator(queryset, 50) # 50 items per page
    page_number = request.GET.get('page')
    page_obj = paginator.get_page(page_number)

    context = {
        'page_obj': page_obj,
        'articles': page_obj, # Keep for compatibility if needed, but page_obj is better
        'filters': filter_params,
        'total_count': queryset.count()
    }
    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/article_table_body.html', context)

    return render(request, 'core/article_list.html', context)

# --- CREATION MANUELLE ---

# --- CRUD UNIFIÉ (ADD / EDIT) ---

# --- CLIENTS ---



def client_edit(request, pk=None):
    if pk:
        client = get_object_or_404(Client, pk=pk)
    else:
        client = None

    if request.method == 'POST':
        form = ClientForm(request.POST, instance=client)
        if form.is_valid():
            obj = form.save()
            if request.headers.get('HX-Request'):
                 return HttpResponse(status=204, headers={'HX-Refresh': 'true'})
            return redirect('client_list')
    else:
        form = ClientForm(instance=client)

    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/client_form_partial.html', {'form': form})
    
    return render(request, 'core/form_generic.html', {'form': form})


# --- COMMANDES ---

def commande_list(request):
    queryset = Commande.objects.filter(statut__in=['BROUILLON', 'COMMANDE', 'CONFIRME_ARC', 'LIVRE']).order_by('-date_commande')
    
    # Filters
    q = request.GET.get('q')
    if q:
        queryset = queryset.filter(
            Q(numero_bdc__icontains=q) | 
            Q(designation__icontains=q) |
            Q(affaire__client__nom__icontains=q) |
            Q(affaire__nom_affaire__icontains=q)
        )
        queryset = queryset.annotate(
            is_start=Case(
                When(designation__istartswith=q, then=Value(2)),
                When(numero_bdc__istartswith=q, then=Value(2)),
                When(affaire__client__nom__istartswith=q, then=Value(1)),
                default=Value(0),
                output_field=IntegerField(),
            )
        ).order_by('-is_start', '-date_commande')

    client_filter = request.GET.get('client')
    if client_filter:
        queryset = queryset.filter(affaire__client__nom__icontains=client_filter)

    affaire_filter = request.GET.get('affaire')
    if affaire_filter:
        queryset = queryset.filter(affaire__nom_affaire__icontains=affaire_filter)

    date_min = request.GET.get('date_min')
    if date_min:
        queryset = queryset.filter(date_commande__gte=date_min)

    statut = request.GET.get('statut')
    if statut:
        queryset = queryset.filter(statut=statut)

    paginator = Paginator(queryset, 50)
    page_number = request.GET.get('page')
    page_obj = paginator.get_page(page_number)
    
    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/commande_table_body.html', {'object_list': page_obj})

    return render(request, 'core/commande_list.html', {'page_obj': page_obj})

def commande_edit(request, pk=None):
    if pk:
        commande = get_object_or_404(Commande, pk=pk)
        lines = Lignecommande.objects.filter(commande=commande)
    else:
        commande = None
        lines = []

    if request.method == 'DELETE':
        if commande:
            commande.delete() 
            return HttpResponse(status=204, headers={'HX-Refresh': 'true'})

    if request.method == 'POST':
        form = CommandeForm(request.POST, instance=commande)
        if form.is_valid():
            obj = form.save()
            return HttpResponse(status=204, headers={'HX-Refresh': 'true'})
    else:
        form = CommandeForm(instance=commande)

    if request.headers.get('HX-Request'):
        return render(request, 'core/partials/commande_form_partial.html', {
            'form': form, 
            'lines': lines
        })
    
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
        return render(request, 'core/partials/fournisseur_form_partial.html', {'form': form, 'is_htmx': True})

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
        return render(request, 'core/partials/article_form_partial.html', {'form': form, 'is_htmx': True})

    return render(request, 'core/form_generic.html', {'form': form})

# --- DOCUMENT & IA (LE COEUR DU SYSTEME) ---

def upload_document(request):
    doc_type_input = request.POST.get('document_type')
    action = request.POST.get('action')
    doc_id = request.POST.get('document_id')
    
    # Data for autocompletion
    existing_clients = list(Client.objects.values_list('nom', flat=True))
    existing_fournisseurs = list(Fournisseur.objects.values_list('nom_fournisseur', flat=True))
    existing_articles = list(Article.objects.values_list('ref_fournisseur', flat=True))
    
    empty_result = {
        'type_document': None,
        'date_document': '',
        'client': {'nom': '', 'siret': '', 'email': ''},
        'fournisseur': {'nom': '', 'siret': '', 'email': ''},
        'references': {'num_commande': '', 'num_arc': '', 'num_bl': '', 'num_facture': '', 'num_devis': ''},
        'totaux': {'ht': '', 'ttc': '', 'tva': ''},
        'date_livraison_prevue': '',
        'lignes': []
    }

    if request.method == 'POST':
        logger.info(f"POST received. Action: {action}, Files: {list(request.FILES.keys())}, DocID: {doc_id}")
        # CASE 1: AUTO-UPLOAD (triggered on file select)
        if request.FILES.get('file'):
            try:
                uploaded_file = request.FILES['file']
                doc = Document.objects.create(fichier=uploaded_file, type_document=doc_type_input)
                # Success - return page with PDF but NO analysis yet
                return render(request, 'documents/upload_v3.html', {
                    'document': doc,
                    'analysis_result': empty_result,
                    'doc_type_selected': doc_type_input,
                    'existing_clients': existing_clients,
                    'existing_fournisseurs': existing_fournisseurs,
                    'existing_articles': existing_articles
                })
            except Exception as e:
                logger.exception("Upload auto-trigger failed")
                return render(request, 'documents/upload_v3.html', {
                    'error': f"Erreur de chargement : {str(e)}",
                    'analysis_result': empty_result,
                    'doc_type_selected': doc_type_input
                })

        # CASE 2: MANUAL ANALYSIS (triggered by blue button)
        elif action == 'analyze' and doc_id:
            try:
                doc = get_object_or_404(Document, pk=doc_id)
                # Update type if changed in dropdown
                if doc_type_input:
                    doc.type_document = doc_type_input
                    doc.save()

                logger.info(f"Manual analysis triggered for document {doc.id}")
                analysis_result = analyze_document(doc.fichier, doc_type_hint=doc.type_document)
                doc.ai_response = analysis_result
                
                if analysis_result and not analysis_result.get('error'):
                    doc.type_document = analysis_result.get('type_document')
                    date_str = analysis_result.get('date_document')
                    if date_str:
                        doc.date_document = date_str
                    doc.save()

                return render(request, 'documents/upload_v3.html', {
                    'document': doc,
                    'analysis_result': analysis_result,
                    'doc_type_selected': doc.type_document,
                    'existing_clients': existing_clients,
                    'existing_fournisseurs': existing_fournisseurs,
                    'existing_articles': existing_articles
                })
            except Exception as e:
                logger.exception("Manual analysis failed")
                return render(request, 'documents/upload_v3.html', {
                    'document': Document.objects.filter(pk=doc_id).first(),
                    'error': f"Erreur d'analyse : {str(e)}",
                    'analysis_result': empty_result,
                    'doc_type_selected': doc_type_input
                })

    # BASE GET REQUEST
    return render(request, 'documents/upload_v3.html', {
        'analysis_result': empty_result,
        'existing_clients': existing_clients,
        'existing_fournisseurs': existing_fournisseurs,
        'existing_articles': existing_articles
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
                        "num_commande": request.POST.get('num_commande'), # Keep key for now, mapped to numero_bdc in services
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

            return redirect('upload_document')

        except Exception as e:
            logger.exception("Erreur validation document")
            messages.error(request, f"Erreur critique lors de la sauvegarde : {str(e)}")
            # On reste sur la page pour voir l'erreur
    
    # Autocomplete Data
    existing_clients = list(Client.objects.values_list('nom', flat=True))
    existing_fournisseurs = list(Fournisseur.objects.values_list('nom_fournisseur', flat=True))

    # On s'assure que analysis_result n'est pas None pour le template
    analysis_result = doc.ai_response
    if not analysis_result:
        analysis_result = {
            'type_document': doc_type,
            'date_document': '',
            'client': {'nom': '', 'siret': '', 'email': ''},
            'fournisseur': {'nom': '', 'siret': '', 'email': ''},
            'references': {'num_commande': '', 'num_arc': '', 'num_bl': '', 'num_facture': '', 'num_devis': ''},
            'totaux': {'ht': '', 'ttc': '', 'tva': ''},
            'date_livraison_prevue': ''
        }

    return render(request, 'documents/upload_v3.html', {
        'analysis_result': analysis_result,
        'document': doc,
        'doc_type_selected': doc_type,
        'existing_clients': existing_clients,
        'existing_clients': existing_clients,
        'existing_fournisseurs': existing_fournisseurs
    })

# --- BESOINS AFFAIRE (QUICK ENTRY) ---

def besoins_affaire(request, pk):
    # Short-circuit for DEMO: If pk is "1", get first or create generic
    if str(pk) == "1":
        affaire = Affaire.objects.first()
        if not affaire:
             # Create a dummy Client if needed
            client, _ = Client.objects.get_or_create(id="CLI-DEMO", defaults={'nom': 'Client Démo'})
            affaire = Affaire.objects.create(
                nom_affaire="Chantier de Démo",
                designation="Résidence Exemple",
                client=client,
                statut='EN_COURS'
            )
    else:
        affaire = get_object_or_404(Affaire, pk=pk)
    
    # POST: Création rapide (HTMX)
    if request.method == 'POST':
        try:
            article_id = request.POST.get('article')
            quantite = float(request.POST.get('quantite') or 0)
            unite = request.POST.get('unite')
            ral = request.POST.get('ral')
            notes = request.POST.get('notes')
            
            # Sauvegarde du RAL en session pour la prochaine saisie
            if ral:
                request.session['last_ral'] = ral
            
            # Création
            Besoin.objects.create(
                affaire=affaire,
                article_id=article_id,
                quantite=quantite,
                unite=unite,
                ral=ral,
                notes=notes,
                statut='A_TRAITER'
            )
            
            # Retourne la liste mise à jour (ou juste la nouvelle ligne si on optimise)
            # Pour faire simple, on retourne le tableau partiel mis à jour
            besoins = Besoin.objects.filter(affaire=affaire).select_related('article', 'article__fournisseur').order_by('-id')
            return render(request, 'core/partials/besoins_table_body.html', {'besoins': besoins})
            
        except Exception as e:
            logger.error(f"Erreur création besoin: {e}")
            return HttpResponse(f"Erreur: {e}", status=400) # Simple error feedback

    # GET: Affichage initial
    besoins = Besoin.objects.filter(affaire=affaire).select_related('article', 'article__fournisseur').order_by('-id')
    
    # Stats simples
    stats = {
        'total_lignes': besoins.count(),
        # 'total_estime': besoins.aggregate(Sum('article__prix_achat')) # A affiner plus tard avec qté
    }

    # Données pour autocomplétion
    # On optimise en ne prenant que les champs nécessaires
    articles_list = Article.objects.select_related('fournisseur').values(
        'id', 'ref_fournisseur', 'designation', 'fournisseur__nom_fournisseur'
    )
    
    last_ral = request.session.get('last_ral', '')

    context = {
        'affaire': affaire,
        'besoins': besoins,
        'stats': stats,
        'articles_json': list(articles_list), # Pour JS éventuel ou Datalist
        'last_ral': last_ral
    }
    return render(request, 'core/besoins_affaire.html', context)