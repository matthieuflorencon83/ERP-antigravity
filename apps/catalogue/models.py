import uuid
from django.db import models

class Article(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    ref_fournisseur = models.CharField(max_length=100, db_index=True)

    designation = models.CharField(max_length=255)
    famille = models.CharField(max_length=100)
    sous_famille = models.CharField(max_length=100)
    lg = models.CharField(max_length=50)
    unite = models.CharField(max_length=20)
    conditionnement = models.CharField(max_length=100, blank=True, null=True, verbose_name="Conditionnement")
    stock = models.DecimalField(max_digits=12, decimal_places=3, default=0.0)
    
    # New Extended Fields
    fabricant = models.CharField(max_length=100, blank=True, null=True, verbose_name="Fabricant")
    ref_fabricant = models.CharField(max_length=100, blank=True, null=True, verbose_name="Réf. Fabricant")
    type_article = models.CharField(max_length=100, blank=True, null=True, verbose_name="Type")

    prix_unitaire_ht = models.DecimalField(max_digits=10, decimal_places=2, default=0.0)
    poids_kg_metre = models.FloatField(null=True, blank=True, help_text="Poids linéaire (kg/m)")
    surface_m2_metre = models.FloatField(null=True, blank=True, help_text="Surface de laquage (m²/m)")
    longueur_standard = models.IntegerField(null=True, blank=True, help_text="Longueur barre standard en mm")
    
    # FK -> Tiers
    fournisseur = models.ForeignKey('tiers.Fournisseur', models.PROTECT)

    class Meta:
        managed = True
        db_table = 'commandes_article' # LEGACY

    def __str__(self):
        return str(self.designation)


