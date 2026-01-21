
import os
import django
from django.conf import settings

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from apps.core.models import Document

doc = Document.objects.last()
if doc:
    print(f"ID: {doc.id}")
    print(f"Fichier Name: {doc.fichier.name}")
    print(f"Fichier URL: {doc.fichier.url}")
    print(f"Fichier Path: {doc.fichier.path}")
else:
    print("No documents found.")
