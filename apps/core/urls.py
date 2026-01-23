from django.urls import path
from . import views

app_name = 'core'
 
urlpatterns = [
    path('', views.home, name='home'),
    path('settings/', views.settings_view, name='settings'),
    
    # Redirections Legacy
    path('besoins/<uuid:pk>/', views.redirect_besoins_to_affaires),
]
