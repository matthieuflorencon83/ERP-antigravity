from django.test import TestCase
from django.core.files.uploadedfile import SimpleUploadedFile
from unittest.mock import patch, MagicMock
from apps.ged.models import Document
from apps.achats.models import Commande
from apps.ventes.models import Affaire
from apps.tiers.models import Client, Fournisseur
from apps.ged.services import analyze_document, archive_document_locally
import uuid

class GEDTestCase(TestCase):
    def setUp(self):
        """Setup test dependencies"""
        self.client_obj = Client.objects.create(nom="Test Client GED", id="CLI-GED")
        self.fournisseur = Fournisseur.objects.create(nom_fournisseur="Test Supplier GED")
        self.affaire = Affaire.objects.create(
            nom_affaire="Affaire GED Test",
            designation="Test Designation",
            client=self.client_obj
        )
        self.commande = Commande.objects.create(
            affaire=self.affaire,
            fournisseur=self.fournisseur
        )

    def test_document_creation(self):
        """Test basic document upload"""
        fake_file = SimpleUploadedFile("test.pdf", b"fake pdf content", content_type="application/pdf")
        
        doc = Document.objects.create(
            fichier=fake_file,
            type_document="BON_LIVRAISON",
            commande=self.commande,
            affaire=self.affaire
        )
        
        self.assertIsNotNone(doc.id)
        self.assertEqual(doc.type_document, "BON_LIVRAISON")
        self.assertEqual(doc.commande, self.commande)
        self.assertEqual(doc.affaire, self.affaire)

    @patch('apps.ged.services.genai.GenerativeModel')
    def test_ai_analysis_mock(self, mock_genai):
        """Test AI analysis with mocked Gemini API"""
        # Mock Gemini response
        mock_model = MagicMock()
        mock_response = MagicMock()
        mock_response.text = '''```json
{
  "type_document": "BON_COMMANDE",
  "numero_document": "BC-2026-001",
  "date_document": "2026-01-15",
  "fournisseur": {"nom": "Test Supplier", "siret": "12345678900012"},
  "totaux": {"ht": 1000.00, "ttc": 1200.00}
}
```'''
        mock_model.generate_content.return_value = mock_response
        mock_genai.return_value = mock_model
        
        # Create fake file
        fake_file = SimpleUploadedFile("test.pdf", b"fake pdf content")
        
        # Call service
        result = analyze_document(fake_file)
        
        # Assertions
        self.assertIsInstance(result, dict)
        self.assertEqual(result.get('type_document'), 'BON_COMMANDE')
        self.assertEqual(result.get('numero_document'), 'BC-2026-001')
        self.assertIn('fournisseur', result)

    def test_document_type_choices(self):
        """Test that document type is restricted to valid choices"""
        fake_file = SimpleUploadedFile("test.pdf", b"content")
        
        # Valid type
        doc = Document.objects.create(
            fichier=fake_file,
            type_document="ARC_FOURNISSEUR",
            affaire=self.affaire
        )
        self.assertEqual(doc.type_document, "ARC_FOURNISSEUR")
        
    def test_document_affaire_relationship(self):
        """Test document can be linked to affaire without commande"""
        fake_file = SimpleUploadedFile("devis.pdf", b"content")
        
        doc = Document.objects.create(
            fichier=fake_file,
            type_document="DEVIS",
            affaire=self.affaire
        )
        
        self.assertEqual(doc.affaire, self.affaire)
        self.assertIsNone(doc.commande)
        # Use reverse query instead of .documents
        self.assertEqual(Document.objects.filter(affaire=self.affaire).count(), 1)
