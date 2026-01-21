from django.urls import path
from . import views

# app_name = 'achats'

urlpatterns = [
    # Commandes
    path('commandes/', views.commande_list, name='commande_list'),
    path('commandes/add/', views.commande_edit, name='commande_create'),
    path('commandes/<uuid:pk>/', views.commande_detail, name='commande_detail'), # Detail view
    path('commandes/edit/<uuid:pk>/', views.commande_edit, name='commande_edit'),
    path('commandes/print/<uuid:pk>/', views.commande_print, name='commande_print'),

    # HTMX Commande Actions
    path('htmx/commande/upload/<uuid:pk>/', views.upload_document_commande, name='upload_document_commande'),
    path('htmx/commande/<uuid:pk>/statut/<str:statut>/', views.htmx_update_statut_commande, name='htmx_update_statut_commande'),
    
    # HTMX Lignes
    path('htmx/ligne-commande/<uuid:pk>/update/', views.htmx_update_ligne_commande, name='htmx_update_ligne_commande'),
    path('htmx/ligne-commande/<uuid:pk>/delete/', views.htmx_delete_ligne_commande, name='htmx_delete_ligne_commande'),
]
