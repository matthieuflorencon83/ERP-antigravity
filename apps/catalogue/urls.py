from django.urls import path
from . import views

# app_name = 'catalogue'

urlpatterns = [
    # Articles CRUD
    path('articles/', views.article_list, name='article_list'),
    path('articles/add/', views.article_edit, name='article_create'),
    path('articles/edit/<uuid:pk>/', views.article_edit, name='article_edit'),
    path('articles/delete/<uuid:pk>/', views.article_delete, name='article_delete'),
    
    # HTMX Core
    path('htmx/article/<uuid:pk>/detail/', views.htmx_article_detail, name='htmx_article_detail'),
    
    # HTMX Auto-complete / Search
    path('htmx/load-familles/', views.htmx_load_familles, name='htmx_load_familles'),
    path('htmx/load-sous-familles/', views.htmx_load_sous_familles, name='htmx_load_sous_familles'),
    path('htmx/load-articles-options/', views.htmx_load_articles_options, name='htmx_load_articles_options'),
    path('htmx/search-articles/', views.htmx_search_articles, name='htmx_search_articles'),
]
