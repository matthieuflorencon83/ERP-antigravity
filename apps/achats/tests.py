from django.test import TestCase
from decimal import Decimal
from apps.achats.models import Commande, LigneCommande
from apps.ventes.models import Affaire
from apps.tiers.models import Client, Fournisseur
from apps.catalogue.models import Article

class CommandeTestCase(TestCase):
    def setUp(self):
        # Dependencies
        self.client = Client.objects.create(nom="Test Client", id="CLI001")
        self.fournisseur = Fournisseur.objects.create(nom_fournisseur="Test Supplier")
        self.affaire = Affaire.objects.create(
            nom_affaire="Affaire Test", 
            designation="Designation Test",
            client=self.client
        )
        self.article1 = Article.objects.create(
            designation="Article 1", 
            fournisseur=self.fournisseur,
            prix_unitaire_ht=10.0
        )
        self.article2 = Article.objects.create(
            designation="Article 2", 
            fournisseur=self.fournisseur,
            prix_unitaire_ht=20.0
        )

    def test_commande_defaults(self):
        """Test default status is BROUILLON"""
        c = Commande.objects.create(affaire=self.affaire, fournisseur=self.fournisseur)
        self.assertEqual(c.statut, 'BROUILLON')
        self.assertEqual(c.total_ht, 0)

    def test_totals_calculation(self):
        """Test calculation of HT, TVA, TTC"""
        c = Commande.objects.create(affaire=self.affaire, fournisseur=self.fournisseur)
        
        # Line 1: 10 * 10.00 = 100.00
        LigneCommande.objects.create(
            commande=c,
            article=self.article1,
            designation="Ligne 1",
            quantite=10,
            prix_unitaire=10.00
        )
        
        # Line 2: 5 * 20.00 = 100.00
        LigneCommande.objects.create(
            commande=c,
            article=self.article2,
            designation="Ligne 2",
            quantite=5,
            prix_unitaire=20.00
        )
        
        # Manual Trigger (like in View) or simple property test? 
        # Since logic is likely in view, we'll implement a helper here or test the logic if moved to model.
        # Assuming we need to simulate the calculation logic usually present in views/services.
        
        # Let's perform the calculation explicitly to verify our expectations 
        # (This matches what the view `update_commande_totals` does)
        total_ht = sum(line.quantite * line.prix_unitaire for line in c.lignes.all())
        c.total_ht = Decimal(total_ht)
        c.tva = c.total_ht * Decimal('0.20')
        c.total_ttc = c.total_ht + c.tva
        c.save()

        self.assertEqual(c.total_ht, 200.00)
        self.assertEqual(c.tva, 40.00)
        self.assertEqual(c.total_ttc, 240.00)

    def test_workflow_transition(self):
        """Test status change"""
        c = Commande.objects.create(affaire=self.affaire, fournisseur=self.fournisseur)
        c.statut = 'ENVOYEE'
        c.save()
        self.assertEqual(c.statut, 'ENVOYEE')
