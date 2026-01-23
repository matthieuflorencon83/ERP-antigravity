from django.urls import path
from . import views

app_name = 'tiers'

urlpatterns = [
    # Clients
    path('clients/', views.client_list, name='client_list'),
    path('clients/add/', views.client_edit, name='client_create'),
    path('clients/edit/<str:pk>/', views.client_edit, name='client_edit'),

    # Fournisseurs
    path('fournisseurs/', views.fournisseur_list, name='fournisseur_list'),
    path('fournisseurs/add/', views.fournisseur_edit, name='fournisseur_create'),
    path('fournisseurs/edit/<str:pk>/', views.fournisseur_edit, name='fournisseur_edit'),
    path('fournisseurs/delete/<str:pk>/', views.fournisseur_delete, name='fournisseur_delete'),
]
