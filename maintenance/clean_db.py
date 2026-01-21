
import os
import django

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from apps.core.models import Document, Lignecommande, Article, Commande, Affaire, Client, Fournisseur, CoreContact

def clean():
    print("Cleaning database...")
    
    # Order matters due to Foreign Keys
    print(f"Deleting {CoreContact.objects.count()} Contacts")
    CoreContact.objects.all().delete()

    print(f"Deleting {Lignecommande.objects.count()} Lignes de Commande")
    Lignecommande.objects.all().delete()
    
    print(f"Deleting {Article.objects.count()} Articles")
    Article.objects.all().delete()
    
    print(f"Deleting {Document.objects.count()} Documents")
    Document.objects.all().delete()
    
    print(f"Deleting {Commande.objects.count()} Commandes")
    Commande.objects.all().delete()
    
    print(f"Deleting {Affaire.objects.count()} Affaires")
    Affaire.objects.all().delete()
    
    # Optional: Keep Clients/Suppliers? Assuming full clean as per request
    print(f"Deleting {Client.objects.count()} Clients")
    Client.objects.all().delete()
    
    print(f"Deleting {Fournisseur.objects.count()} Fournisseurs")
    Fournisseur.objects.all().delete()
    
    print("Database cleaned successfully.")

if __name__ == "__main__":
    clean()
