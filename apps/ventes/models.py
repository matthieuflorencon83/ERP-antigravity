import uuid
from django.db import models

class Affaire(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False) 
    nom_affaire = models.CharField(max_length=150)
    designation = models.CharField(max_length=255)
    date_creation = models.DateTimeField(auto_now_add=True) 
    
    # FK -> Tiers
    client = models.ForeignKey('tiers.Client', models.PROTECT)
    
    # New Fields
    numero_prodevis = models.CharField(max_length=50, null=True, blank=True)
    date_devis = models.DateField(null=True, blank=True) 
    STATUT_CHOICES = [
        ('EN_ATTENTE', 'En Attente'),
        ('EN_COURS', 'En Cours'),
        ('TERMINE', 'Terminé'),
        ('ANNULE', 'Annulé'),
    ]
    statut = models.CharField(max_length=20, choices=STATUT_CHOICES, default='EN_ATTENTE')

    # Financials (New)
    total_achat_ht = models.DecimalField(max_digits=12, decimal_places=2, default=0.0)
    total_vente_ht = models.DecimalField(max_digits=12, decimal_places=2, default=0.0)

    # Addresses
    adresse_facturation = models.TextField(blank=True, null=True)
    adresse_chantier = models.TextField(blank=True, null=True)
    
    # Legacy fields
    num_facture = models.CharField(max_length=50, blank=True, null=True)
    num_sav = models.CharField(max_length=50, blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'commandes_affaire' # LEGACY

    def __str__(self):
        return str(self.designation)

class Besoin(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    affaire = models.ForeignKey(Affaire, on_delete=models.CASCADE, related_name='besoins')
    
    # FK -> Catalogue
    article = models.ForeignKey('catalogue.Article', on_delete=models.PROTECT)
    # FK -> Tiers
    fournisseur = models.ForeignKey('tiers.Fournisseur', on_delete=models.SET_NULL, null=True)
    
    designation_specifique = models.CharField(max_length=255, blank=True)
    quantite = models.FloatField(default=0)
    unite = models.CharField(max_length=20, default='U')
    ral = models.CharField(max_length=50, blank=True, help_text="Couleur spécifique pour cet élément")
    finition = models.CharField(max_length=50, blank=True)
    longueur_commande = models.IntegerField(null=True, blank=True, help_text="Longueur à commander en mm (si débit)")
    notes = models.TextField(blank=True)
    
    STATUT_CHOICES = [
        ('A_TRAITER', 'À Traiter'),
        ('VALIDE', 'Validé pour commande'),
        ('COMMANDE', 'Commandé'),
        ('EN_STOCK', 'Pris sur Stock'),
    ]
    statut = models.CharField(max_length=20, choices=STATUT_CHOICES, default='A_TRAITER')
    
    statut_commande = models.CharField(max_length=20, blank=True, default="A COMMANDER", help_text="ex: A COMMANDER, EN COMMANDE")
    
    class Meta:
        managed = True
        db_table = 'commandes_besoin' # LEGACY
        
    def __str__(self):
        return f"{self.article.designation} ({self.quantite} {self.unite})"
