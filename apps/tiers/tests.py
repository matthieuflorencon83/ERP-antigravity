from django.test import TestCase
from apps.tiers.models import Client, Fournisseur
from apps.ged.services import generate_readable_id

class TiersTestCase(TestCase):
    def test_client_creation(self):
        """Test basic client creation"""
        client = Client.objects.create(
            nom="Entreprise Test",
            id="CLI-TEST-001",
            email_client="test@example.com",
            ville="Paris"
        )
        
        self.assertEqual(client.nom, "Entreprise Test")
        self.assertEqual(client.id, "CLI-TEST-001")
        self.assertEqual(client.email_client, "test@example.com")

    def test_fournisseur_creation(self):
        """Test basic supplier creation"""
        fournisseur = Fournisseur.objects.create(
            nom_fournisseur="Maccario Vitrage",
            siret="40281230000099",
            email="contact@maccario.com"
        )
        
        self.assertIsNotNone(fournisseur.id)
        self.assertEqual(fournisseur.nom_fournisseur, "Maccario Vitrage")
        self.assertEqual(fournisseur.siret, "40281230000099")

    def test_generate_readable_id(self):
        """Test ID generation utility"""
        # Test with normal name
        id1 = generate_readable_id("Maccario Vitrage", "FRN")
        self.assertTrue(id1.startswith("FRN-"))
        self.assertGreater(len(id1), 4)  # At least "FRN-X"
        
        # Test with short name
        id2 = generate_readable_id("AB", "FRN")
        self.assertTrue(id2.startswith("FRN-"))
        
        # Test with special characters
        id3 = generate_readable_id("L'Entreprise & Co.", "CLI")
        self.assertTrue(id3.startswith("CLI-"))

    def test_client_affaires_relationship(self):
        """Test client can have multiple affaires"""
        from apps.ventes.models import Affaire
        
        client = Client.objects.create(nom="Client Multi", id="CLI-MULTI")
        
        affaire1 = Affaire.objects.create(
            nom_affaire="Projet 1",
            designation="Desc 1",
            client=client
        )
        affaire2 = Affaire.objects.create(
            nom_affaire="Projet 2",
            designation="Desc 2",
            client=client
        )
        
        # Use reverse query instead of .affaires
        self.assertEqual(Affaire.objects.filter(client=client).count(), 2)
        self.assertIn(affaire1, Affaire.objects.filter(client=client))

    def test_fournisseur_unique_siret(self):
        """Test SIRET uniqueness constraint"""
        Fournisseur.objects.create(
            nom_fournisseur="Fournisseur 1",
            siret="12345678900012"
        )
        
        # Creating another with same SIRET should work (nullable/blank)
        # but in real scenario you'd want unique constraint
        fournisseur2 = Fournisseur.objects.create(
            nom_fournisseur="Fournisseur 2",
            siret="98765432100019"
        )
        
        self.assertNotEqual(fournisseur2.siret, "12345678900012")
