from django.test import TestCase
from decimal import Decimal
from apps.catalogue.models import Article
from apps.tiers.models import Fournisseur

class CatalogueTestCase(TestCase):
    def setUp(self):
        """Setup test data"""
        self.fournisseur1 = Fournisseur.objects.create(
            nom_fournisseur="Fournisseur Catalogue 1"
        )
        self.fournisseur2 = Fournisseur.objects.create(
            nom_fournisseur="Fournisseur Catalogue 2"
        )

    def test_article_creation(self):
        """Test basic article creation"""
        article = Article.objects.create(
            designation="Vitrage 44.2",
            fournisseur=self.fournisseur1,
            prix_unitaire_ht=Decimal('150.00'),
            unite="M2"
        )
        
        self.assertIsNotNone(article.id)
        self.assertEqual(article.designation, "Vitrage 44.2")
        self.assertEqual(article.prix_unitaire_ht, Decimal('150.00'))
        self.assertEqual(article.fournisseur, self.fournisseur1)

    def test_article_fournisseur_relationship(self):
        """Test article-supplier relationship"""
        article1 = Article.objects.create(
            designation="Article 1",
            fournisseur=self.fournisseur1,
            prix_unitaire_ht=10.0
        )
        article2 = Article.objects.create(
            designation="Article 2",
            fournisseur=self.fournisseur1,
            prix_unitaire_ht=20.0
        )
        
        # Use reverse query instead of .articles
        self.assertEqual(Article.objects.filter(fournisseur=self.fournisseur1).count(), 2)
        self.assertIn(article1, Article.objects.filter(fournisseur=self.fournisseur1))
        self.assertIn(article2, Article.objects.filter(fournisseur=self.fournisseur1))

    def test_article_stock_default(self):
        """Test stock field defaults to 0"""
        article = Article.objects.create(
            designation="Test Stock",
            fournisseur=self.fournisseur1
        )
        
        self.assertEqual(article.stock, Decimal('0.00'))

    def test_article_famille_filtering(self):
        """Test filtering articles by famille"""
        Article.objects.create(
            designation="Vitrage 1",
            fournisseur=self.fournisseur1,
            famille="VITRAGE"
        )
        Article.objects.create(
            designation="Vitrage 2",
            fournisseur=self.fournisseur1,
            famille="VITRAGE"
        )
        Article.objects.create(
            designation="Silicone",
            fournisseur=self.fournisseur1,
            famille="ACCESSOIRE"
        )
        
        vitrages = Article.objects.filter(famille="VITRAGE")
        self.assertEqual(vitrages.count(), 2)

    def test_article_price_calculation(self):
        """Test price with quantity"""
        article = Article.objects.create(
            designation="Test Price",
            fournisseur=self.fournisseur1,
            prix_unitaire_ht=Decimal('25.50')
        )
        
        quantity = 10
        total = article.prix_unitaire_ht * quantity
        
        self.assertEqual(total, Decimal('255.00'))
