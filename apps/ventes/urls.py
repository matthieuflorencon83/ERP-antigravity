from django.urls import path
from . import views

# app_name = 'ventes'

urlpatterns = [
    # Besoins Management
    path('affaires/<uuid:pk>/besoins/', views.besoins_affaire, name='besoins_affaire'),
    path('besoins/<uuid:pk>/delete/', views.delete_besoin, name='delete_besoin'),
    
    # Actions
    path('affaire/<uuid:pk>/generer-commande/', views.generer_commande, name='generer_commande'),
]
