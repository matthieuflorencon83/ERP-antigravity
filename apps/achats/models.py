import uuid
from django.db import models
from django.core.validators import FileExtensionValidator

class Commande(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    
    # FK -> Ventes
    affaire = models.ForeignKey('ventes.Affaire', on_delete=models.CASCADE)
    # FK -> Tiers
    fournisseur = models.ForeignKey('tiers.Fournisseur', on_delete=models.PROTECT)
    
    # Statuts
    STATUT_CHOICES = [
        ('BROUILLON', 'En préparation'),
        ('ENVOYEE', 'Validée/Envoyée'),
        ('CONFIRME_ARC', 'ARC reçu et vérifié'),
        ('LIVREE_PARTIEL', 'BL reçu incomplet'),
        ('LIVREE', 'Tout est là'),
        ('FACTUREE', 'Terminée'),
    ]
    statut = models.CharField(max_length=20, choices=STATUT_CHOICES, default='BROUILLON')

    # Canal d'achat
    CANAL_CHOICES = [
        ('EMAIL', 'Email'),
        ('WEB', 'Web'),
        ('TELEPHONE', 'Téléphone'),
        ('AUTRE', 'Autre'),
    ]
    canal = models.CharField(max_length=20, choices=CANAL_CHOICES, default='EMAIL')
    ref_externe_web = models.CharField(max_length=100, blank=True, null=True, help_text="N° de commande sur site fournisseur")

    # Finances
    total_ht = models.DecimalField(max_digits=12, decimal_places=2, default=0.0)
    tva = models.DecimalField(max_digits=12, decimal_places=2, default=0.0)
    total_ttc = models.DecimalField(max_digits=12, decimal_places=2, default=0.0)

    # Documents IA
    document_arc = models.FileField(upload_to='commandes/arc/', blank=True, null=True, validators=[FileExtensionValidator(allowed_extensions=['pdf', 'jpg', 'png'])])
    document_bl = models.FileField(upload_to='commandes/bl/', blank=True, null=True, validators=[FileExtensionValidator(allowed_extensions=['pdf', 'jpg', 'png'])])

    # Dates
    date_creation = models.DateTimeField(auto_now_add=True)
    date_commande = models.DateTimeField(blank=True, null=True, help_text="Date réelle de l'envoi")
    
    # Legacy/Old fields kept for compatibility
    designation = models.CharField(max_length=255, blank=True, null=True, help_text="Libellé commande")
    numero_bdc = models.CharField(max_length=50, blank=True, null=True, help_text="N° BDC Interne")
    numero_arc = models.CharField(max_length=50, blank=True, null=True, help_text="N° ARC Fournisseur")
    numero_bl = models.CharField(max_length=50, blank=True, null=True, help_text="N° BL Fournisseur")
    
    date_arc = models.DateField(blank=True, null=True)
    date_livraison_prevue = models.DateField(blank=True, null=True)
    date_livraison_reelle = models.DateField(blank=True, null=True)
    nb_produits_recus = models.IntegerField(default=0)

    class Meta:
        managed = True
        db_table = 'commandes_commande' # LEGACY

    @property
    def progression_pourcentage(self):
        if self.statut == 'LIVREE':
            return 100
        elif self.statut == 'CONFIRME_ARC':
            return 66
        elif self.statut == 'ENVOYEE':
            return 33
        return 0

    def __str__(self):
        return f"CDE {self.pk} - {self.fournisseur}"

class LigneCommande(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    commande = models.ForeignKey(Commande, on_delete=models.CASCADE, related_name='lignes')
    
    # FK -> Catalogue
    article = models.ForeignKey('catalogue.Article', on_delete=models.PROTECT)
    
    # FK -> Ventes
    besoin_generateur = models.OneToOneField('ventes.Besoin', on_delete=models.SET_NULL, null=True, blank=True, related_name='ligne_commande_associee')
    
    designation = models.CharField(max_length=255) 
    quantite = models.FloatField(default=1.0)
    prix_unitaire = models.DecimalField(max_digits=10, decimal_places=2, default=0.0)
    remise = models.DecimalField(max_digits=5, decimal_places=2, default=0.0)

    # Status Ligne
    STATUT_LIGNE_CHOICES = [
        ('ATTENTE', 'En attente'),
        ('LIVREE', 'Livrée'),
        ('RELIQUAT', 'Reliquat (Partiel)'),
    ]
    statut_ligne = models.CharField(max_length=20, choices=STATUT_LIGNE_CHOICES, default='ATTENTE')
    
    class Meta:
        managed = True
        db_table = 'commandes_lignecommande' # LEGACY

    def __str__(self):
        return f"{self.designation} (x{self.quantite})"

    @property
    def total_ligne(self):
        return float(self.quantite) * float(self.prix_unitaire)
