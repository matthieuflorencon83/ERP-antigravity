from django.urls import path
from . import views

app_name = 'achats'

urlpatterns = [
    # Commandes
    path('portfolio/', views.portfolio_achats, name='portfolio_achats'),
    path('commandes/', views.commande_list, name='commande_list'),
    path('commandes/add/', views.commande_edit, name='commande_create'),
    path('commandes/<uuid:pk>/', views.commande_detail, name='commande_detail'), # Detail view
    path('commandes/edit/<uuid:pk>/', views.commande_edit, name='commande_edit'),
    path('commandes/verification/<uuid:pk>/', views.commande_verification, name='commande_verification'),
    path('commandes/print/<uuid:pk>/', views.commande_print, name='commande_print'),

    # Affaires / Cockpit
    path('affaire/<uuid:pk>/detail/', views.affaire_commandes_detail, name='affaire_commandes_detail'),

    # HTMX Commande Actions
    path('htmx/commande/workflow/<uuid:pk>/', views.upload_document_workflow, name='upload_document_workflow'),
    path('htmx/commande/<uuid:pk>/split-arc/', views.htmx_split_commande_arc, name='htmx_split_commande_arc'),
    path('htmx/commande/<uuid:pk>/statut/<str:statut>/', views.htmx_update_statut_commande, name='htmx_update_statut_commande'),
    
    # HTMX Lignes
    path('htmx/ligne-commande/<uuid:pk>/update/', views.htmx_update_ligne_commande, name='htmx_update_ligne_commande'),
    path('htmx/ligne-commande/<uuid:pk>/reception/', views.htmx_receptionner_ligne, name='htmx_receptionner_ligne'),
    path('htmx/ligne-commande/<uuid:pk>/verification-update/', views.htmx_update_line_verification, name='htmx_update_line_verification'),
    path('htmx/ligne-commande/<uuid:pk>/delete/', views.htmx_delete_ligne_commande, name='htmx_delete_ligne_commande'),
]
