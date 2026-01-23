
import os
import sys
import django
from django.template.loader import render_to_string
from django.conf import settings

# Setup Django
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from apps.core.forms import ClientForm, FournisseurForm

def test_render():
    print("Testing Client Form Partial...")
    try:
        form = ClientForm()
        html = render_to_string('tiers/partials/client_form_partial.html', {'form': form})
        print("✅ Client Partial OK")
    except Exception as e:
        print(f"❌ Client Partial Failed: {e}")

    print("\nTesting Fournisseur Form Partial...")
    try:
        form = FournisseurForm()
        html = render_to_string('tiers/partials/fournisseur_form_partial.html', {'form': form})
        print("✅ Fournisseur Partial OK")
    except Exception as e:
        print(f"❌ Fournisseur Partial Failed: {e}")

if __name__ == "__main__":
    test_render()
