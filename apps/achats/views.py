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
def portfolio_achats(request):
    """
    Vue 'Satellite' : Liste des Chantiers (Affaires) avec une Jauge 'ADN' des commandes.
    Optimized to avoid N+1 queries.
    """
    from django.db.models import Count, Q, BooleanField, ExpressionWrapper, DateField
    
    # Date du jour pour comparaison
    today = timezone.now().date()
    
    # Récupérer les affaires avec annotations
    affaires = Affaire.objects.filter(commande__isnull=False).distinct().annotate(
        total=Count('commande'),
        nb_brouillon=Count('commande', filter=Q(commande__statut='BROUILLON')),
        nb_envoyee=Count('commande', filter=Q(commande__statut='ENVOYEE')),
        nb_arc=Count('commande', filter=Q(commande__statut='CONFIRME_ARC')),
        nb_livree=Count('commande', filter=Q(commande__statut__in=['LIVREE', 'LIVREE_PARTIEL'])),
        # Alert if any active command is late
        nb_retard=Count('commande', filter=Q(
            ~Q(commande__statut='LIVREE'),
            commande__date_livraison_prevue__lt=today
        ))
    )
    
    portfolio_data = []
    
    for affaire in affaires:
        total = affaire.total
        if total == 0: continue
        
        # Calcul des % pour la jauge
        pct_brouillon = (affaire.nb_brouillon / total) * 100
        pct_envoyee = (affaire.nb_envoyee / total) * 100
        pct_arc = (affaire.nb_arc / total) * 100
        pct_livree = (affaire.nb_livree / total) * 100
        
        portfolio_data.append({
            'affaire': affaire,
            'stats': {
                'total': total,
                'nb_brouillon': affaire.nb_brouillon,
                'nb_envoyee': affaire.nb_envoyee,
                'nb_arc': affaire.nb_arc,
                'nb_livree': affaire.nb_livree,
            },
            'jauge': {
                'pct_brouillon': pct_brouillon,
                'pct_envoyee': pct_envoyee,
                'pct_arc': pct_arc,
                'pct_livree': pct_livree,
            },
            'has_alert': affaire.nb_retard > 0
        })
    
    return render(request, 'achats/portfolio.html', {'portfolio_data': portfolio_data})

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
    queryset = Commande.objects.select_related('affaire__client', 'fournisseur').filter(statut__in=['BROUILLON', 'ENVOYEE', 'CONFIRME_ARC', 'LIVREE']).order_by('-date_commande')
    
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
            form.save()
            return HttpResponse(status=204, headers={'HX-Refresh': 'true'})
    else:
        form = CommandeForm(instance=commande)

    if request.headers.get('HX-Request'):
        return render(request, 'achats/partials/commande_form_partial.html', {
            'form': form, 
            'lines': lines
        })
    
    return render(request, 'achats/commande_detail.html', {
        'form': form,
        'commande': commande,
        'lines': lines
    })

@login_required
def commande_detail(request, pk):
    return redirect('achats:commande_edit', pk=pk)

@login_required
def commande_detail(request, pk):
    return redirect('achats:commande_edit', pk=pk)

@login_required
def affaire_commandes_detail(request, pk):
    """
    Vue 'Cockpit' : Liste détaillée des commandes d'une affaire avec Timeline.
    """
    affaire = get_object_or_404(Affaire, pk=pk)
    # Récupérer les commandes triées par date (les plus récentes en haut)
    commandes = affaire.commande_set.all().order_by('-date_creation').select_related('fournisseur')
    
    return render(request, 'achats/affaire_detail.html', {
        'affaire': affaire,
        'commandes': commandes,
    })

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

@login_required
def commande_verification(request, pk):
    commande = get_object_or_404(Commande, pk=pk)
    
    # Determine which document/data to verify
    # Default to ARC if in early stages, BL if later
    # Determine which document/data to verify
    # Default to ARC if in early stages, BL if later
    verification_type = 'ARC'
    doc_instance = None
    
    if commande.statut in ['LIVREE', 'LIVREE_PARTIEL']:
        verification_type = 'BL'
        json_data = commande.json_data_bl
        # Fetch latest BL
        doc_instance = commande.documents.filter(type_document='BON_LIVRAISON', est_actif=True).order_by('-created_at').first()
    else:
        json_data = commande.json_data_arc
        # Fetch latest ARC
        doc_instance = commande.documents.filter(type_document='ARC_FOURNISSEUR', est_actif=True).order_by('-created_at').first()

    doc_file = doc_instance.fichier if doc_instance else None

    document_url = doc_file.url if doc_file else None
    document_type = 'image' # Default simple check
    if document_url and document_url.lower().endswith('.pdf'):
        document_type = 'pdf'

    verification_lines = []
    discrepancies_count = 0
    
    # Match Logic to Helper
    # doc_lines_map is built once here
    doc_lines_map = {}
    if json_data and 'lignes' in json_data:
        for item in json_data['lignes']:
            key = item.get('reference', '').strip() or item.get('description', '').strip()
            if key:
                doc_lines_map[key] = item
    
    for line in commande.lignes.all().select_related('article'):
        row = get_verification_row(line, doc_lines_map, json_data is not None)
        if row['has_error']:
            discrepancies_count += 1
        verification_lines.append(row)

    return render(request, 'achats/commande_verification.html', {
        'commande': commande,
        'verification_lines': verification_lines,
        'discrepancies_count': discrepancies_count,
        'document_url': document_url,
        'document_type': document_type
    })

def get_verification_row(line, doc_lines_map, has_json_data):
    row = {
        'id': line.id,
        'reference': getattr(line.article, 'ref_fournisseur', 'N/A'),
        'designation': line.designation,
        'qte_cmd': line.quantite,
        'prix_cmd': line.prix_unitaire,
        'qte_doc': None,
        'prix_doc': None,
        'qte_match': False,
        'prix_match': False,
        'has_error': False
    }
    
    match = None
    ref = row['reference']
    if ref and ref in doc_lines_map:
         match = doc_lines_map[ref]
    
    if not match:
         for k, v in doc_lines_map.items():
             str_k = str(k).lower()
             str_des = str(line.designation).lower()
             if str_k in str_des or str_des in str_k:
                 match = v
                 break
    
    if match:
        try:
            q_doc = float(match.get('quantite', 0))
            row['qte_doc'] = q_doc
            if abs(q_doc - float(row['qte_cmd'])) < 0.01:
                row['qte_match'] = True
            else:
                row['has_error'] = True
        except:
            pass
            
        try:
            p_doc = float(match.get('prix_unitaire', 0))
            row['prix_doc'] = p_doc
            if abs(p_doc - float(row['prix_cmd'])) < 0.01:
                row['prix_match'] = True
            else:
                # row['has_error'] = True 
                pass 
        except:
            pass
    else:
        if has_json_data:
            row['has_error'] = False 
            
    return row

@login_required
@require_POST
def htmx_update_line_verification(request, pk):
    line = get_object_or_404(LigneCommande, pk=pk)
    
    # Update fields if provided
    if 'quantite' in request.POST:
        try:
            line.quantite = Decimal(request.POST['quantite'])
        except: pass
    if 'prix_unitaire' in request.POST:
        try:
            line.prix_unitaire = Decimal(request.POST['prix_unitaire'])
        except: pass
        
    line.save()
    update_commande_totals(line.commande)
    
    # Re-verify this line to update status
    commande = line.commande
    # Need to load doc data again - reused logic (simplification: minimal load)
    json_data = commande.json_data_arc # Default
    if commande.statut in ['LIVREE', 'LIVREE_PARTIEL'] and commande.json_data_bl:
        json_data = commande.json_data_bl
        
    doc_lines_map = {}
    if json_data and 'lignes' in json_data:
        for item in json_data['lignes']:
             key = item.get('reference', '').strip() or item.get('description', '').strip()
             if key: doc_lines_map[key] = item
             
    row = get_verification_row(line, doc_lines_map, json_data is not None)
    
    return render(request, 'achats/partials/verification_line_row.html', {'line': row})

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

@login_required
@require_POST
def upload_document_workflow(request, pk):
    """
    Vue de traitement centralisé pour l'upload de documents (HTMX).
    Gère la machine à états de la commande (Brouillon -> Envoyée -> ARC -> Livrée).
    """
    commande = get_object_or_404(Commande, pk=pk)
    
    if 'document' in request.FILES:
        uploaded_file = request.FILES['document']
        doc_type_code = request.POST.get('doc_type', 'AUTRE') # BDC, ARC, BL
        
        # Mapping Types & Comportements
        map_types = {
            'BDC': 'BON_COMMANDE',
            'ARC': 'ARC_FOURNISSEUR',
            'BL': 'BON_LIVRAISON'
        }
        full_type = map_types.get(doc_type_code, 'AUTRE')
        
        # 1. Create Document Entry
        doc_instance = Document.objects.create(
            fichier=uploaded_file,
            type_document=full_type,
            commande=commande,
            affaire=commande.affaire
        )

        try:
            # 2. Logique Métier par Type de Document
            
            # --- CAS 1 : BDC (Brouillon -> Envoyée) ---
            if doc_type_code == 'BDC':
                # commande.document_bdc = uploaded_file # REMOVED
                commande.statut = 'ENVOYEE'
                commande.date_commande = timezone.now() # Date d'envoi
                messages.success(request, "Bon de Commande sauvegardé. Statut passé à 'Envoyée'.")

            # --- CAS 2 : ARC (Envoyée -> Confirmée) ---
            elif doc_type_code == 'ARC':
                # Appel IA pour extraction
                ai_result = analyze_document(doc_instance.fichier)
                doc_instance.ai_response = ai_result
                doc_instance.save()
                
                # commande.document_arc = uploaded_file # REMOVED
                commande.statut = 'CONFIRME_ARC'
                commande.json_data_arc = ai_result
                commande.statut_verification = 'PENDING'
                
                # Extraction Dates
                if ai_result:
                    date_doc = ai_result.get('date_document')
                    liv_prevue = ai_result.get('date_livraison_prevue')
                    if date_doc: commande.date_arc = date_doc
                    if liv_prevue: commande.date_livraison_prevue = liv_prevue

                messages.success(request, "ARC analysé par IA. Statut passé à 'Confirmée'.")

            # --- CAS 3 : BL (Confirmée -> Livrée) ---
            elif doc_type_code == 'BL':
                # Appel IA pour extraction
                ai_result = analyze_document(doc_instance.fichier)
                doc_instance.ai_response = ai_result
                doc_instance.save()
                
                # commande.document_bl = uploaded_file # REMOVED
                commande.statut = 'LIVREE'
                commande.json_data_bl = ai_result
                commande.statut_verification = 'PENDING'
                
                if ai_result:
                    date_doc = ai_result.get('date_document')
                    if date_doc: commande.date_livraison_reelle = date_doc

                messages.success(request, "BL analysé par IA. Statut passé à 'Livrée'.")

            # Save Commande Changes
            commande.save()
            
            # 3. Archive Local
            archive_document_locally(doc_instance)

        except Exception as e:
            logger.error(f"Workflow Error: {e}")
            messages.warning(request, f"Erreur lors du traitement du document : {e}")
            
    # Retourne le bloc de statut mis à jour (Step 2 du Prompt)
    # Note: On retourne 'commande_detail_timeline.html' si on est dans la vue Cockpit, 
    # ou 'commande_status_area.html' pour la compatibilité. 
    # Ici, le prompt demande "Vue de Détail... Affiche Timeline".
    # On va supposer qu'on est sur la vue détail Timeline.
    return render(request, 'achats/partials/commande_timeline.html', {'commande': commande})

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
def htmx_receptionner_ligne(request, pk):
    """
    Gère la réception d'une ligne de commande (HTMX).
    Supporte la livraison partielle (Scission de ligne / Reliquat).
    """
    ligne_originale = get_object_or_404(LigneCommande, pk=pk)
    commande = ligne_originale.commande
    
    try:
        qte_recue = Decimal(request.POST.get('qte_recue', 0))
    except (ValueError, TypeError):
        return HttpResponse("Quantité invalide", status=400)
    
    context = {}

    with transaction.atomic():
        # CAS A : Tout est là (ou plus)
        # Note: On compare avec une tolérance si float, mais Decimal est mieux.
        if qte_recue >= ligne_originale.quantite:
            ligne_originale.statut_ligne = 'LIVREE'
            ligne_originale.save()
            context['lines'] = [ligne_originale]
            
        # CAS B : Partiel (Reliquat)
        else:
            qte_initiale = ligne_originale.quantite
            qte_restante = qte_initiale - qte_recue
            
            # 1. Mise à jour de la ligne reçue (Devient la ligne LIVREE)
            ligne_originale.quantite = qte_recue
            ligne_originale.statut_ligne = 'LIVREE'
            ligne_originale.save()
            
            # 2. Création du Reliquat
            nouvelle_ligne = LigneCommande.objects.create(
                commande=commande,
                article=ligne_originale.article,
                # besoin_generateur : OneToOne, donc on ne peut pas le dupliquer simplement. 
                # On le laisse à None pour le Reliquat pour l'instant (ou on le transfère, mais c'est une autre logique).
                besoin_generateur=None, 
                designation=f"{ligne_originale.designation}",
                quantite=qte_restante,
                prix_unitaire=ligne_originale.prix_unitaire,
                remise=ligne_originale.remise,
                statut_ligne='RELIQUAT'
            )
            
            context['lines'] = [ligne_originale, nouvelle_ligne]
            
        # Check Global Commande Status
        # Si toutes les lignes sont LIVREE, on passe la commande en LIVREE
        if not commande.lignes.exclude(statut_ligne='LIVREE').exists():
            commande.statut = 'LIVREE'
            commande.date_livraison_reelle = timezone.now()
            commande.save()
        elif commande.statut != 'LIVREE_PARTIEL':
            # Si au moins une ligne livrée, on passe en partiel
             if commande.lignes.filter(statut_ligne='LIVREE').exists():
                 commande.statut = 'LIVREE_PARTIEL'
                 commande.save()

    return render(request, 'achats/partials/commande_lines_reception_list.html', {'lines': context['lines']})

@login_required
@require_POST
def htmx_delete_ligne_commande(request, pk):
    line = get_object_or_404(LigneCommande, pk=pk)
    commande = line.commande
    line.delete()
    
    update_commande_totals(commande)
    
    # Return empty string to remove row, plus OOB update for footer
    return render(request, 'achats/partials/commande_line_delete.html', {'commande': commande})

@login_required
@require_POST
def htmx_split_commande_arc(request, pk):
    """
    Action déclenchée depuis la modale "Split ARC".
    Effectue la scission via le service.
    """
    from .services import split_commande_from_arc
    
    commande = get_object_or_404(Commande, pk=pk)
    found_lines_ids = request.POST.getlist('found_lines[]')
    
    # Appel Service
    c_orig, c_new = split_commande_from_arc(commande, found_lines_ids)
    
    messages.success(request, f"Scission effectuée ! Commande {c_new.pk} créée pour les reliquats.")
    
    # Redirection vers le détail de l'affaire pour voir les deux commandes
    return redirect('achats:affaire_commandes_detail', pk=commande.affaire.id)

def update_commande_totals(commande):
    # Helper simple
    lines = commande.lignes.all()
    total_ht = sum(Decimal(str(line.quantite)) * line.prix_unitaire for line in lines)
    commande.total_ht = total_ht
    commande.tva = total_ht * Decimal('0.20') # Hardcoded 20% for now
    commande.total_ttc = commande.total_ht + commande.tva
    commande.save()
