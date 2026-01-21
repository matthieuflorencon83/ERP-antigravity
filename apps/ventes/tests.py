from django.test import TestCase
from apps.ventes.models import Affaire, Besoin
from apps.tiers.models import Client, Fournisseur
from apps.catalogue.models import Article

class VentesTestCase(TestCase):
    def setUp(self):
        self.client = Client.objects.create(nom="Client Vente", id="V001")
        self.fournisseur = Fournisseur.objects.create(nom_fournisseur="Supplier Vente")
        self.article = Article.objects.create(designation="Prod Vente", fournisseur=self.fournisseur)

    def test_affaire_creation(self):
        """Test simple Affaire creation"""
        af = Affaire.objects.create(
            nom_affaire="Projet Alpha",
            designation="Construction Hangar",
            client=self.client
        )
        self.assertEqual(af.statut, 'EN_ATTENTE')
        self.assertTrue(af.id) # UUID exists

    def test_besoin_addition(self):
        """Test associating Needs (Besoins) to Affaire"""
        af = Affaire.objects.create(nom_affaire="Projet Beta", client=self.client, designation="Test")
        
        b = Besoin.objects.create(
            affaire=af,
            article=self.article,
            fournisseur=self.fournisseur,
            quantite=50
        )
        
        self.assertEqual(af.besoins.count(), 1)
        self.assertEqual(b.statut, 'A_TRAITER')
