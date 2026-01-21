from django.urls import path
from . import views

urlpatterns = [
    path('', views.home, name='home'),
    # Documents
    path('documents/upload/', views.upload_document, name='upload_document'),
    path('documents/<uuid:pk>/', views.document_detail, name='document_detail'),
    
    # Management Lists
    path('fournisseurs/', views.fournisseur_list, name='fournisseur_list'),
    path('fournisseurs/add/', views.fournisseur_edit, name='fournisseur_create'),
    path('fournisseurs/edit/<str:pk>/', views.fournisseur_edit, name='fournisseur_edit'),

    path('clients/', views.client_list, name='client_list'),
    path('clients/add/', views.client_edit, name='client_create'),
    path('clients/edit/<str:pk>/', views.client_edit, name='client_edit'),

    path('articles/', views.article_list, name='article_list'),
    path('articles/add/', views.article_edit, name='article_create'),
    path('articles/edit/<uuid:pk>/', views.article_edit, name='article_edit'),

    # Commandes
    path('commandes/', views.commande_list, name='commande_list'),
    path('commandes/add/', views.commande_edit, name='commande_create'),
    path('commandes/edit/<int:pk>/', views.commande_edit, name='commande_edit'),

    # Besoins Affaire
    path('affaires/<str:pk>/besoins/', views.besoins_affaire, name='besoins_affaire'),
]
