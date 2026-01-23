import os
import sys
import django

# Setup Django Environment
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from apps.tiers.models import Client

def run():
    print("Inspecting Client records...")
    clients_empty_pk = Client.objects.filter(id='')
    print(f"Clients with empty PK: {clients_empty_pk.count()}")
    
    for c in clients_empty_pk:
        print(f"Found Bad Client: {c.nom} (PK: '{c.pk}')")
        # Optional: Delete them if valid approach
        # c.delete()

    clients_none_pk = Client.objects.filter(id__isnull=True)
    print(f"Clients with None PK: {clients_none_pk.count()}")

    print("All Clients:")
    for c in Client.objects.all()[:10]:
        print(f" - {c.nom} (PK: '{c.pk}')")

if __name__ == "__main__":
    run()
