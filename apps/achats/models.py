import uuid
from django.db import models
from django.core.validators import FileExtensionValidator
from apps.core.utils import generate_filename

class Commande(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    
    # FK -> Ventes
    affaire = models.ForeignKey('ventes.Affaire', on_delete=models.CASCADE)
    # FK -> Tiers
    fournisseur = models.ForeignKey('tiers.Fournisseur', on_delete=models.PROTECT)
    
    # Statuts (Workflow Strict 4 Étapes)
    STATUT_CHOICES = [
        ('BROUILLON', 'Brouillon'),        # Gris 
        ('ENVOYEE', 'Envoyée'),            # Jaune (BDC envoyé)
        ('CONFIRME_ARC', 'Confirmée'),     # Bleu (ARC reçu)
        ('LIVREE', 'Livrée'),              # Vert (Marchandise reçue)
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

    # Verification / IA Data
    json_data_arc = models.JSONField(blank=True, null=True, help_text="Extraction IA brute de l'ARC")
    json_data_bl = models.JSONField(blank=True, null=True, help_text="Extraction IA brute du BL")
    
    VERIFICATION_STATUS = [
        ('PENDING', 'Non vérifié'),
        ('MISMATCH', 'Écarts détectés'),
        ('VALIDATED', 'Validé'),
    ]
    statut_verification = models.CharField(max_length=20, choices=VERIFICATION_STATUS, default='PENDING')

    # 1. POUR LE SPLIT (Généalogie)
    parent_commande = models.ForeignKey('self', null=True, blank=True, on_delete=models.SET_NULL, related_name='reliquats')
    est_reliquat = models.BooleanField(default=False)
    
    # 3. STATUT ET VALIDATION
    prix_verrouilles = models.BooleanField(default=False)

    # Documents : SUPPRIMÉS (On utilise apps.ged.models.Document via reverse relation 'documents')
    # document_bdc = ... (Removed)
    # document_arc = ... (Removed)
    # document_bl = ... (Removed)

    # Dates Clés
    date_creation = models.DateTimeField(auto_now_add=True)
    date_commande = models.DateTimeField(blank=True, null=True, help_text="Date envoi BDC")
    date_arc = models.DateField(blank=True, null=True, help_text="Date réception ARC")
    date_livraison_prevue = models.DateField(blank=True, null=True, help_text="Extraite de l'ARC")
    date_livraison_reelle = models.DateField(blank=True, null=True, help_text="Date réception BL")
    
    # Legacy/Old fields kept for compatibility
    designation = models.CharField(max_length=255, blank=True, null=True, help_text="Libellé commande")
    numero_bdc = models.CharField(max_length=50, blank=True, null=True, help_text="N° BDC Interne")
    numero_arc = models.CharField(max_length=50, blank=True, null=True, help_text="N° ARC Fournisseur")
    numero_bl = models.CharField(max_length=50, blank=True, null=True, help_text="N° BL Fournisseur")
    nb_produits_recus = models.IntegerField(default=0)

    class Meta:
        managed = True
        db_table = 'commandes_commande'

    @property
    def progression_timeline(self):
        if self.statut == 'LIVREE': return 100
        if self.statut == 'CONFIRME_ARC': return 66
        if self.statut == 'ENVOYEE': return 33
        return 0

    @property
    def progression_pourcentage(self):
        # Alias pour compatibilité existante
        return self.progression_timeline

    @property
    def est_en_retard(self):
        from django.utils import timezone
        if self.statut == 'LIVREE' or not self.date_livraison_prevue:
            return False
        return self.date_livraison_prevue < timezone.now().date()

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
    prix_unitaire_facture = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True, help_text="Prix validé ARC/Facture")
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
        from decimal import Decimal
        return Decimal(str(self.quantite)) * self.prix_unitaire
