from django.shortcuts import render, redirect
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.db.models import Count, Sum, Q
import uuid
from .forms import ParametreForm
from .models import CoreParametre

from apps.ventes.models import Affaire
from apps.achats.models import Commande

@login_required
def home(request):
    # --- KPI 1: VENTES (Affaires) ---
    affaires_qs = Affaire.objects.all()
    
    # Stats Affaires
    nb_affaires_cours = affaires_qs.filter(statut='EN_COURS').count()
    total_pipeline_ventes = affaires_qs.filter(statut='EN_COURS').aggregate(Sum('total_vente_ht'))['total_vente_ht__sum'] or 0
    
    # Actions requises
    nb_devis_attente = affaires_qs.filter(statut='EN_ATTENTE').count()

    # --- KPI 2: ACHATS (Commandes) ---
    commandes_qs = Commande.objects.all()
    
    # Stats Commandes
    nb_commandes_cours = commandes_qs.filter(statut__in=['ENVOYEE', 'CONFIRME_ARC', 'LIVREE_PARTIEL']).count()
    total_pipeline_achats = commandes_qs.filter(statut__in=['BROUILLON', 'ENVOYEE', 'CONFIRME_ARC']).aggregate(Sum('total_ht'))['total_ht__sum'] or 0
    
    # Actions requises
    nb_brouillons = commandes_qs.filter(statut='BROUILLON').count()
    nb_arc_manquant = commandes_qs.filter(statut='ENVOYEE').count()

    # --- RECENT ACTIVITY ---
    latest_affaires = affaires_qs.select_related('client').order_by('-date_creation')[:5]
    latest_commandes = commandes_qs.select_related('fournisseur').order_by('-date_commande')[:5]

    context = {
        'kpi': {
            'nb_affaires_cours': nb_affaires_cours,
            'total_pipeline_ventes': total_pipeline_ventes,
            'nb_devis_attente': nb_devis_attente,
            'nb_commandes_cours': nb_commandes_cours,
            'total_pipeline_achats': total_pipeline_achats,
            'nb_brouillons': nb_brouillons,
            'nb_arc_manquant': nb_arc_manquant,
        },
        'latest_affaires': latest_affaires,
        'latest_commandes': latest_commandes,
    }
    return render(request, 'core/home.html', context)

@login_required
def settings_view(request):
    # Singleton pattern
    param = CoreParametre.objects.first()
    if not param:
        param = CoreParametre.objects.create(id=uuid.uuid4(), nom_societe="Ma Société", chemin_stockage_local="/tmp")
    
    if request.method == 'POST':
        form = ParametreForm(request.POST, instance=param)
        if form.is_valid():
            form.save()
            messages.success(request, "Paramètres mis à jour avec succès.")
            return redirect('settings')
    else:
        form = ParametreForm(instance=param)
    
    return render(request, 'core/parametres.html', {'form': form})

@login_required
def redirect_besoins_to_affaires(request, pk):
    """Redirection de compatibilité pour les anciens liens /besoins/1/ -> /affaires/1/besoins/"""
    return redirect('besoins_affaire', pk=pk)
