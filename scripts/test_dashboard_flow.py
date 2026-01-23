
import os
import sys
import django
from unittest.mock import patch

# Setup Django
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from django.test import RequestFactory, TestCase
from django.core.files.uploadedfile import SimpleUploadedFile
from django.contrib.sessions.middleware import SessionMiddleware
from django.contrib.messages.middleware import MessageMiddleware
from django.contrib.auth.models import User
from django.urls import reverse

from apps.achats.models import Commande
from apps.tiers.models import Fournisseur, Client
from apps.ventes.models import Affaire
from apps.ged import views as ged_views

class DashboardFlowTest(TestCase):
    def setUp(self):
        self.factory = RequestFactory()
        self.user = User.objects.create_user(username='testuser', password='password')
        
        # Create Data for Linking
        self.fournisseur = Fournisseur.objects.create(nom_fournisseur="Fournisseur Test")
        self.client = Client.objects.create(nom="Client Test")
        self.affaire = Affaire.objects.create(nom_affaire="Affaire Test", client=self.client)
        
        self.commande = Commande.objects.create(
            numero_bdc="CDE-2025-001",
            fournisseur=self.fournisseur,
            affaire=self.affaire,
            statut='ENVOYEE'
        )

    @patch('apps.ged.views.analyze_document')
    def test_dashboard_routing_arc_found(self, mock_analyze):
        print("\n🚀 Testing Dashboard Routing: ARC -> Order Verification")
        
        # 1. Mock Analysis Result (Matching ARC)
        mock_analyze.return_value = {
            "type_document": "ARC_FOURNISSEUR",
            "date_document": "2025-01-23",
            "references": {"num_commande": "CDE-2025-001"}, # MATCHES BDC
            "lignes": []
        }

        # 2. Prepare Request
        with open('test_dashboard.pdf', 'wb') as f:
            f.write(b'%PDF-1.4 mock content')
            
        with open('test_dashboard.pdf', 'rb') as f:
            uploaded_file = SimpleUploadedFile("test_dashboard.pdf", f.read(), content_type="application/pdf")
            
        request = self.factory.post(
            reverse('ged:dashboard_quick_scan'),
            {'document': uploaded_file}
        )
        request.user = self.user
        
        # Middleware
        session_middleware = SessionMiddleware(lambda r: None)
        session_middleware.process_request(request)
        request.session.save()
        MessageMiddleware(lambda r: None).process_request(request)

        # 3. Execute View
        response = ged_views.dashboard_quick_scan(request)
        
        # 4. Assert Redirection
        # HTMX redirects via HX-Redirect header
        self.assertEqual(response.status_code, 200)
        expected_url = reverse('achats:commande_verification', args=[self.commande.id])
        self.assertEqual(response['HX-Redirect'], expected_url)
        print(f"✅ Redirected to: {response['HX-Redirect']}")
        
        # Check Commande updated
        self.commande.refresh_from_db()
        self.assertEqual(self.commande.statut, 'CONFIRME_ARC')
        print("✅ Order Status Updated to CONFIRME_ARC")
        
        os.remove('test_dashboard.pdf')

    @patch('apps.ged.views.analyze_document')
    def test_dashboard_routing_unknown(self, mock_analyze):
        print("\n🚀 Testing Dashboard Routing: Unknown -> Document Detail")
        
        # 1. Mock Analysis Result (Generic Invoice, No Match)
        mock_analyze.return_value = {
            "type_document": "FACTURE",
            "date_document": "2025-01-23",
            "references": {"num_facture": "F-999"}, 
            "lignes": []
        }

        # 2. Prepare Request
        with open('test_dashboard_inv.pdf', 'wb') as f:
            f.write(b'%PDF-1.4 mock content')
            
        with open('test_dashboard_inv.pdf', 'rb') as f:
            uploaded_file = SimpleUploadedFile("test_dashboard_inv.pdf", f.read(), content_type="application/pdf")
            
        request = self.factory.post(
            reverse('ged:dashboard_quick_scan'),
            {'document': uploaded_file}
        )
        request.user = self.user
        
        # Middleware
        session_middleware = SessionMiddleware(lambda r: None)
        session_middleware.process_request(request)
        request.session.save()
        MessageMiddleware(lambda r: None).process_request(request)

        # 3. Execute View
        response = ged_views.dashboard_quick_scan(request)
        
        # 4. Assert Redirection to Document Detail
        # We need to find the created document ID to verify URL
        from apps.ged.models import Document
        latest_doc = Document.objects.order_by('-created_at').first()
        
        self.assertEqual(response.status_code, 200)
        expected_url = reverse('ged:document_detail', args=[latest_doc.id])
        self.assertEqual(response['HX-Redirect'], expected_url)
        print(f"✅ Redirected to: {response['HX-Redirect']}")
        
        os.remove('test_dashboard_inv.pdf')

if __name__ == "__main__":
    import unittest
    unittest.main()
