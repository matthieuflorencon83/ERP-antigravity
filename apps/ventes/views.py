from django.shortcuts import render, redirect, get_object_or_404, HttpResponse
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.db import transaction
from django.views.decorators.http import require_POST
from decimal import Decimal
import logging

from apps.ventes.models import Affaire, Besoin
from apps.catalogue.models import Article
from apps.tiers.models import Client, Fournisseur
from apps.achats.models import Commande, LigneCommande

logger = logging.getLogger(__name__)

@login_required
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
            article_id = request.POST.get('article_id')
            if not article_id:
                raise ValueError("Article manquant")

            quantite = Decimal(request.POST.get('quantite') or '0')
            ral = request.POST.get('ral')
            notes = request.POST.get('notes', '')
            
            # Retrieve Article to fill defaults
            article = Article.objects.get(pk=article_id)
            unite = request.POST.get('unite') or article.unite or 'U'

            # Sauvegarde du RAL en session
            if ral:
                request.session['last_ral'] = ral
            
            # Création
            Besoin.objects.create(
                affaire=affaire,
                article=article,
                fournisseur=article.fournisseur, # Auto-populate supplier
                quantite=quantite,
                unite=unite,
                ral=ral,
                notes=notes,
                statut='A_TRAITER'
            )
            
            # Retourne la liste mise à jour (ou juste la nouvelle ligne si on optimise)
            besoins = Besoin.objects.filter(affaire=affaire).select_related('article', 'article__fournisseur').order_by('-id')
            return render(request, 'ventes/partials/besoins_table_body.html', {'besoins': besoins})
            
        except Exception as e:
            logger.error(f"Erreur création besoin: {e}")
            return HttpResponse(f"Erreur: {e}", status=400) # Simple error feedback

    # GET: Affichage initial
    besoins = Besoin.objects.filter(affaire=affaire).select_related('article', 'article__fournisseur').order_by('-id')
    
    # Stats simples
    stats = {
        'total_lignes': besoins.count(),
    }

    # Données pour autocomplétion
    articles_list = Article.objects.select_related('fournisseur').values(
        'id', 'ref_fournisseur', 'designation', 'fournisseur__nom_fournisseur'
    )
    
    last_ral = request.session.get('last_ral', '')

    context = {
        'affaire': affaire,
        'besoins': besoins,
        'stats': stats,
        'articles_json': list(articles_list), # Pour JS éventuel ou Datalist
        'last_ral': last_ral,
        'fournisseurs': Fournisseur.objects.all().order_by('nom_fournisseur'),
        'familles': Article.objects.values_list('famille', flat=True).distinct().order_by('famille') # Populate initially
    }
    return render(request, 'ventes/besoins_affaire.html', context)

@login_required
def delete_besoin(request, pk):
    besoin = get_object_or_404(Besoin, pk=pk)
    affaire = besoin.affaire
    besoin.delete()
    
    # Return OOB update for counter
    count = Besoin.objects.filter(affaire=affaire).count()
    response_content = f"""
    <span id="besoins-counter" class="badge bg-body-tertiary text-body-secondary border me-2" hx-swap-oob="true">
        <i class="bi bi-list-ol me-1"></i> {count} lignes
    </span>
    """
    return HttpResponse(response_content)

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
        return redirect('ventes:besoins_affaire', pk=pk)

    # 1. Filtrer les besoins valides (pas déjà commandés)
    besoins = Besoin.objects.filter(
        id__in=besoin_ids, 
        affaire=affaire
    ).exclude(statut='COMMANDE').select_related('article', 'fournisseur', 'article__fournisseur')

    if not besoins.exists():
        messages.warning(request, "Les besoins sélectionnés sont déjà commandés ou invalides.")
        return redirect('ventes:besoins_affaire', pk=pk)

    # 2. Regrouper par Fournisseur
    grouped = {}
    sans_fournisseur = []

    for besoin in besoins:
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
                cmd = Commande.objects.create(
                    affaire=affaire,
                    fournisseur=fournisseur,
                    statut='BROUILLON',
                    canal='EMAIL',
                    designation=f"Cde {fournisseur.nom_fournisseur} - {affaire.nom_affaire}"[:255]
                )
                
                # Création des Lignes
                for need in needs:
                    price = need.article.prix_unitaire_ht
                    
                    LigneCommande.objects.create(
                        commande=cmd,
                        article=need.article,
                        besoin_generateur=need,
                        designation=need.article.designation[:255], # Snapshot
                        quantite=need.quantite,
                        prix_unitaire=price,
                        remise=0
                    )
                    
                    # Mise à jour du Besoin
                    need.statut = 'COMMANDE'
                    need.statut_commande = 'EN COMMANDE'
                    need.save()
                
                # Update totals for command
                # (Recalcul logic duplicated? Ideally shared helper. Copied logic from Achats helper for now)
                lines = cmd.lignes.all()
                total_ht = sum(l.quantite * l.prix_unitaire for l in lines)
                cmd.total_ht = total_ht
                cmd.tva = total_ht * Decimal('0.20')
                cmd.total_ttc = cmd.total_ht + cmd.tva
                cmd.save()
                
                created_cmds.append(cmd)

        if created_cmds:
            if len(created_cmds) == 1:
                messages.success(request, "Commande brouillon créée avec succès.")
                return redirect('achats:commande_edit', pk=created_cmds[0].pk)
            else:
                messages.success(request, f"{len(created_cmds)} commandes brouillons créées.")
        
    except Exception as e:
        logger.exception("Erreur lors de la génération de commande")
        messages.error(request, f"Erreur technique : {e}")

    return redirect('ventes:besoins_affaire', pk=pk)
