
from django.test import TestCase, Client
from django.urls import reverse
from django.contrib.auth.models import User
from django.utils import timezone
import apps.ventes.models as ventes_models
from apps.tiers.models import Fournisseur, Client as ClientTiers
from apps.catalogue.models import Article
from apps.achats.models import Commande, LigneCommande
import uuid

class AchatsPagesTest(TestCase):
    def setUp(self):
        # 1. Create User & Login
        self.user = User.objects.create_user(username='testuser', password='password')
        self.client = Client()
        self.client.login(username='testuser', password='password')

        # 2. Setup Data
        self.client_tiers = ClientTiers.objects.create(nom="Client Test")
        self.affaire = ventes_models.Affaire.objects.create(nom_affaire="Affaire Test", client=self.client_tiers)
        self.fournisseur = Fournisseur.objects.create(nom_fournisseur="Supplier Test")
        self.article = Article.objects.create(ref_fournisseur="REF01", designation="Art 1", prix_unitaire_ht=10, fournisseur=self.fournisseur)
        
        self.commande = Commande.objects.create(
            affaire=self.affaire,
            fournisseur=self.fournisseur,
            statut='BROUILLON',
            designation="Cmd Test"
        )
        LigneCommande.objects.create(commande=self.commande, article=self.article, quantite=5, prix_unitaire=10)

    def test_portfolio_rendering(self):
        """Test the optimized 'Satellite' view"""
        response = self.client.get(reverse('achats:portfolio_achats'))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Affaire Test")
        self.assertContains(response, "100%") # Jauge presence

    def test_commande_list(self):
        """Test the list view"""
        # Ensure date_commande is set to ensure it appears properly in list with date sorting
        self.commande.date_commande = timezone.now()
        self.commande.save()
        
        response = self.client.get(reverse('achats:commande_list'))
        self.assertEqual(response.status_code, 200)
        
        # Should now find the row
        self.assertContains(response, "Client Test") 

    def test_commande_detail_timeline(self):
        """Test detail view and timeline rendering"""
        url = reverse('achats:commande_edit', kwargs={'pk': self.commande.pk})
        response = self.client.get(url)
        self.assertEqual(response.status_code, 200)
        # Check for Status Area (Stepper) instead of Timeline-row
        self.assertContains(response, "status-area")
        self.assertContains(response, "Brouillon") 


    def test_affaire_cockpit(self):
        """Test the affaire detail cockpit"""
        url = reverse('achats:affaire_commandes_detail', kwargs={'pk': self.affaire.pk})
        response = self.client.get(url)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Affaire Test")

    def test_verification_page(self):
        """Test verification page"""
        url = reverse('achats:commande_verification', kwargs={'pk': self.commande.pk})
        response = self.client.get(url)
        self.assertEqual(response.status_code, 200)
