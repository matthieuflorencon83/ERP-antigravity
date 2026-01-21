from django.shortcuts import render, redirect, get_object_or_404, HttpResponse
from django.contrib.auth.decorators import login_required
from django.views.decorators.http import require_POST
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Q, Case, When, Value, IntegerField
from django.utils import timezone
from decimal import Decimal
import logging
import uuid # Needed for creating default params

from django.db import transaction # Added for generer_commande

from apps.achats.models import Commande, LigneCommande
from apps.ged.models import Document
from apps.core.models import CoreParametre
from apps.core.forms import CommandeForm
from apps.ventes.models import Affaire, Besoin # Added for generer_commande
from apps.ged.services import analyze_document, archive_document_locally

logger = logging.getLogger(__name__)

@login_required
@require_POST
def generer_commande(request, pk):
    """
    Génère une ou plusieurs commandes (Brouillon) à partir d'une sélection de besoins.
    pk: ID de l'Affaire
    """
    affaire = get_object_or_404(Affaire, pk=pk)
    besoin_ids = request.POST.getlist('besoin_ids')

    if not besoin_ids:
        messages.warning(request, "Aucun besoin sélectionné.")
        return redirect('besoins_affaire', pk=pk)

    # 1. Filtrer les besoins valides (pas déjà commandés)
    besoins = Besoin.objects.filter(
        id__in=besoin_ids, 
        affaire=affaire
    ).exclude(statut='COMMANDE').select_related('article', 'fournisseur', 'article__fournisseur')

    if not besoins.exists():
        messages.warning(request, "Les besoins sélectionnés sont déjà commandés ou invalides.")
        return redirect('besoins_affaire', pk=pk)

    # 2. Regrouper par Fournisseur
    grouped = {}
    sans_fournisseur = []

    for besoin in besoins:
        # Priorité : Fournisseur assigné au besoin > Fournisseur de l'article > None
        fournisseur = besoin.fournisseur or besoin.article.fournisseur
        
        if not fournisseur:
            sans_fournisseur.append(besoin)
            continue
            
        if fournisseur not in grouped:
            grouped[fournisseur] = []
        grouped[fournisseur].append(besoin)

    if sans_fournisseur:
        messages.error(request, f"{len(sans_fournisseur)} besoins ignorés car sans fournisseur défini.")

    # 3. Création des Commandes
    created_cmds = []
    
    try:
        with transaction.atomic():
            for fournisseur, needs in grouped.items():
                # Création En-tête Commande
                # On utilise un numéro temporaire ou on laisse vide, l'ID suffit pour brouillon
                cmd = Commande.objects.create(
                    affaire=affaire,
                    fournisseur=fournisseur,
                    statut='BROUILLON',
                    canal='EMAIL',
                    designation=f"Cde {fournisseur.nom_fournisseur} - {affaire.nom_affaire}"[:255]
                )
                
                # Création des Lignes
                for need in needs:
                    # Prix : Priorité besoin > article
                    # Note: Besoin model doesn't have price field currently? 
                    # Checking model... Besoin has quantite, unite, ral, finition. No price.
                    # So use Article price.
                    price = need.article.prix_unitaire_ht
                    
                    LigneCommande.objects.create(
                        commande=cmd,
                        article=need.article,
                        besoin_generateur=need,
                        designation=need.article.designation, # Snapshot
                        quantite=need.quantite,
                        prix_unitaire=price,
                        remise=0
                    )
                    
                    # Mise à jour du Besoin
                    need.statut = 'COMMANDE'
                    need.statut_commande = 'EN COMMANDE'
                    need.save()
                
                created_cmds.append(cmd)

        if created_cmds:
            if len(created_cmds) == 1:
                messages.success(request, "Commande brouillon créée avec succès.")
                return redirect('commande_edit', pk=created_cmds[0].pk)
            else:
                messages.success(request, f"{len(created_cmds)} commandes brouillons créées.")
        
    except Exception as e:
        logger.exception("Erreur lors de la génération de commande")
        messages.error(request, f"Erreur technique : {e}")

    return redirect('besoins_affaire', pk=pk) 
    
    
@login_required
def commande_list(request):
    queryset = Commande.objects.select_related('affaire__client', 'fournisseur').filter(statut__in=['BROUILLON', 'COMMANDE', 'CONFIRME_ARC', 'LIVRE']).order_by('-date_commande')
    
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
        return render(request, 'achats/partials/commande_table_body.html', {'object_list': page_obj})

    return render(request, 'achats/commande_list.html', {'page_obj': page_obj})

@login_required
def commande_edit(request, pk=None):
    if pk:
        commande = get_object_or_404(Commande, pk=pk)
        lines = LigneCommande.objects.filter(commande=commande)
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
        return render(request, 'achats/partials/commande_form_partial.html', {
            'form': form, 
            'lines': lines
        })
    
    return render(request, 'core/form_generic.html', {'form': form})

@login_required
def commande_detail(request, pk):
    return redirect('achats:commande_edit', pk=pk)

@login_required
def commande_print(request, pk):
    commande = get_object_or_404(Commande, pk=pk)
    # Get parameters (Company Info)
    # Optimization: ensure we have at least one or create default
    params = CoreParametre.objects.first()
    if not params:
        params = CoreParametre.objects.create(
            id=uuid.uuid4(),
            nom_societe="Ma Société",
            chemin_stockage_local="C:/Dev/storage"
        )
    
    return render(request, 'achats/commande_print.html', {
        'commande': commande,
        'societe': params,
        'lines': commande.lignes.all()
    })

# --- HTMX ACTIONS ---

@require_POST
@login_required
def htmx_update_statut_commande(request, pk, statut):
    commande = get_object_or_404(Commande, pk=pk)
    
    # Simple State Machine Logic
    if statut == 'ENVOYEE':
        commande.statut = 'ENVOYEE'
        commande.date_commande = timezone.now() # Date envoi
    elif statut == 'CONFIRME_ARC':
        commande.statut = 'CONFIRME_ARC'
    elif statut == 'LIVREE':
        commande.statut = 'LIVREE'
        commande.date_livraison_reelle = timezone.now()
        
    commande.save()
    
    # Return updated status area
    return render(request, 'achats/partials/commande_status_area.html', {'commande': commande})

@require_POST
@login_required
def upload_document_commande(request, pk):
    commande = get_object_or_404(Commande, pk=pk)
    
    if 'document' in request.FILES:
        uploaded_file = request.FILES['document']
        doc_type_code = request.POST.get('doc_type', 'AUTRE') # ARC or BL
        
        # Map simple code to Full Type
        full_type = 'ARC_FOURNISSEUR' if doc_type_code == 'ARC' else 'BON_LIVRAISON'
        
        # 1. Create Document attached to Commande
        doc_instance = Document.objects.create(
            fichier=uploaded_file,
            type_document=full_type,
            commande=commande,
            affaire=commande.affaire
        )
        
        # 2. Call AI Service
        try:
            ai_result = analyze_document(doc_instance.fichier)
            doc_instance.ai_response = ai_result
            
            # Extract Date if present
            date_doc = ai_result.get('date_document')
            if date_doc:
                doc_instance.date_document = date_doc
            doc_instance.save()
            
            # 3. Intelligent Update of Commande
            if doc_type_code == 'ARC':
                commande.statut = 'CONFIRME_ARC'
                commande.document_arc = uploaded_file
                commande.date_arc = date_doc
                
                # Try extract delivery date
                liv_prevue = ai_result.get('date_livraison_prevue')
                if liv_prevue:
                    commande.date_livraison_prevue = liv_prevue
                    
            elif doc_type_code == 'BL':
                commande.statut = 'LIVREE'
                commande.document_bl = uploaded_file
                commande.date_livraison_reelle = date_doc # Assuming BL date is delivery date
            
            commande.save()
            
            # 4. Local Archive
            archive_document_locally(doc_instance)
            
            messages.success(request, f"Document {doc_type_code} analysé et archivé avec succès.")
            
        except Exception as e:
            logger.error(f"AI Analysis Failed: {e}")
            messages.warning(request, "Document sauvegardé mais échec de l'analyse IA.")
            
    return render(request, 'achats/partials/commande_status_area.html', {'commande': commande})

@login_required
@require_POST
def htmx_update_ligne_commande(request, pk):
    line = get_object_or_404(LigneCommande, pk=pk)
    
    # Update fields
    try:
        new_qty = Decimal(request.POST.get('quantite', line.quantite))
        new_price = Decimal(request.POST.get('prix_unitaire', line.prix_unitaire))
        
        line.quantite = new_qty
        line.prix_unitaire = new_price
        line.save()
        
        # Recalculate Commande Totals
        commande = line.commande
        update_commande_totals(commande)
        
        # Return updated row AND totals footer
        context = {'line': line, 'form': CommandeForm(instance=commande)}
        return render(request, 'achats/partials/commande_line_row.html', context)
        
    except Exception as e:
        logger.error(f"Error updating line: {e}")
        return HttpResponse(status=400)

@login_required
@require_POST
def htmx_delete_ligne_commande(request, pk):
    line = get_object_or_404(LigneCommande, pk=pk)
    commande = line.commande
    line.delete()
    
    update_commande_totals(commande)
    
    # Return empty string to remove row, plus OOB update for footer
    return render(request, 'achats/partials/commande_line_delete.html', {'commande': commande})

def update_commande_totals(commande):
    # Helper simple
    lines = commande.lignes.all()
    total_ht = sum(l.quantite * l.prix_unitaire for l in lines)
    commande.total_ht = total_ht
    commande.tva = total_ht * Decimal('0.20') # Hardcoded 20% for now
    commande.total_ttc = commande.total_ht + commande.tva
    commande.save()
