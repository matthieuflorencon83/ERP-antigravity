from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from apps.ged.models import Document
from apps.tiers.models import Client, Fournisseur
from apps.catalogue.models import Article
from apps.ventes.models import Affaire
from apps.achats.models import Commande
# REFACTOR: Import from new service layer
from apps.ged.services import analyze_document, save_extracted_data, format_form_data
from decimal import Decimal
import logging

logger = logging.getLogger(__name__)

@login_required
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

@login_required
def document_detail(request, pk):
    doc = get_object_or_404(Document, pk=pk)
    
    # Détermination sécurisée du type
    ai_res = doc.ai_response or {}
    doc_type = ai_res.get('type') or ai_res.get('type_document') or "AUTRE"

    if request.method == 'POST':
        try:
            # 1. Utilisation du Helper Service pour formater les données
            final_data = format_form_data(request.POST, doc_type)

            # 2. Appel du Service Intelligent
            result_obj = save_extracted_data(doc, final_data)
            
            # 3. Feedback Utilisateur
            if result_obj:
                if isinstance(result_obj, Affaire):
                    messages.success(request, f"Affaire '{result_obj.nom_affaire}' initialisée avec succès !")
                elif isinstance(result_obj, Commande):
                    messages.success(request, f"Commande {result_obj.numero_bdc or result_obj.numero_arc} traitée. Statut: {result_obj.get_statut_display()}")
                elif isinstance(result_obj, Document):
                    messages.success(request, f"Données extraites avec succès. ARC en attente de liaison (BDC introuvable).")
            else:
                messages.warning(request, "Traitement effectué, mais aucun objet principal n'a été retourné.")

            return redirect('ged:upload_document')

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

    return render(request, 'ged/upload_v3.html', {
        'analysis_result': analysis_result,
        'document': doc,
        'doc_type_selected': doc_type,
        'existing_clients': existing_clients,
        'existing_fournisseurs': existing_fournisseurs
    })
