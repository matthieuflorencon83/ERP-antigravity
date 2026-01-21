from django.urls import path
from . import views

# app_name = 'ged'

urlpatterns = [
    path('documents/upload/', views.upload_document, name='upload_document'),
    path('documents/<uuid:pk>/', views.document_detail, name='document_detail'),
]
