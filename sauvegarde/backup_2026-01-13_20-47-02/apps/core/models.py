from django.db import models
import uuid

class Affaire(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False) # Enhanced default
    nom_affaire = models.CharField(max_length=150)
    designation = models.CharField(max_length=255)
    date_creation = models.DateTimeField(auto_now_add=True) # Enhanced default
    client = models.ForeignKey('Client', models.DO_NOTHING)
    
    # New Fields
    numero_prodevis = models.CharField(max_length=50, null=True, blank=True)
    date_devis = models.DateField(null=True, blank=True) # Extracted date
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

    # Addresses (Specific to this Affaire, snapshot or override)
    adresse_facturation = models.TextField(blank=True, null=True)
    adresse_chantier = models.TextField(blank=True, null=True)
    
    # Legacy fields (kept for safety)
    num_devis = models.CharField(max_length=50, blank=True, null=True)
    num_facture = models.CharField(max_length=50, blank=True, null=True)
    num_sav = models.CharField(max_length=50, blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'commandes_affaire'

    def __str__(self):
        return str(self.designation)

class Article(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    ref_fournisseur = models.CharField(max_length=100)
    code_article = models.CharField(max_length=100, null=True, blank=True) # Requested
    designation = models.CharField(max_length=255)
    famille = models.CharField(max_length=100)
    sous_famille = models.CharField(max_length=100)
    lg = models.CharField(max_length=50)
    unite = models.CharField(max_length=20)
    conditionnement = models.CharField(max_length=50)
    stock = models.FloatField(default=0.0)
    prix_unitaire_ht = models.DecimalField(max_digits=10, decimal_places=2, default=0.0)
    fournisseur = models.ForeignKey('Fournisseur', models.DO_NOTHING)

    class Meta:
        managed = True
        db_table = 'commandes_article'

    def __str__(self):
        return str(self.designation)

class Client(models.Model):
    id = models.CharField(primary_key=True, max_length=50, editable=False)
    nom = models.CharField(max_length=100)
    prenom = models.CharField(max_length=100, blank=True, null=True)
    adresse_facturation = models.TextField(blank=True, null=True)
    adresse_chantier = models.TextField(blank=True, null=True)
    telephone_client = models.CharField(max_length=20, blank=True, null=True)
    email_client = models.CharField(max_length=254, blank=True, null=True)
    type_tiers = models.CharField(max_length=20, default='PARTICULIER')
    siret = models.CharField(max_length=14, blank=True, null=True)
    tva_intracommunautaire = models.CharField(max_length=20, blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'commandes_client'

    def __str__(self):
        return str(self.nom)

class Commande(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    designation = models.CharField(max_length=255)
    prix_total_ht = models.DecimalField(max_digits=12, decimal_places=2, default=0.0)
    date_creation = models.DateTimeField(auto_now_add=True)
    
    date_commande = models.DateField(blank=True, null=True)
    date_arc = models.DateField(blank=True, null=True)
    date_livraison_prevue = models.DateField(blank=True, null=True)
    date_livraison_reelle = models.DateField(blank=True, null=True)
    date_livraison_confirmee = models.DateField(blank=True, null=True) # New
    
    STATUT_CHOICES = [
        ('BROUILLON', 'Brouillon'),
        ('COMMANDE', 'Commande'),
        ('CONFIRME_ARC', 'Confirmé ARC'),
        ('LIVRE', 'Livré'),
    ]
    statut = models.CharField(max_length=20, choices=STATUT_CHOICES, default='BROUILLON')
    
    affaire = models.ForeignKey(Affaire, models.DO_NOTHING)
    
    # Legacy & New fields mixed
    num_arc = models.CharField(max_length=50, blank=True, null=True)
    numero_arc = models.CharField(max_length=50, blank=True, null=True) # Requested Alias
    num_bl = models.CharField(max_length=50, blank=True, null=True)
    numero_bl = models.CharField(max_length=50, blank=True, null=True) # Requested Alias
    
    # Specific fields per Doc Type
    numero_bdc = models.CharField(max_length=50, blank=True, null=True)
    nb_produits_recus = models.IntegerField(default=0)
    num_commande = models.CharField(max_length=50, blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'commandes_commande'

    def __str__(self):
        return str(self.designation)

class Fournisseur(models.Model):
    id = models.CharField(primary_key=True, max_length=50, editable=False)
    nom_fournisseur = models.CharField(max_length=150)
    nom_usage = models.CharField(max_length=150, blank=True, null=True)
    code_fournisseur = models.CharField(max_length=50, blank=True, null=True)
    adresse = models.TextField(blank=True, null=True)
    siret = models.CharField(max_length=14, blank=True, null=True)
    tva_intracommunautaire = models.CharField(max_length=20, blank=True, null=True)
    code_client = models.CharField(max_length=50, blank=True, null=True)
    notes = models.TextField(blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'commandes_fournisseur'

    def __str__(self):
        return str(self.pk)

class Lignecommande(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    quantite = models.FloatField(default=1.0)
    
    couleur_ral = models.CharField(max_length=50, blank=True, null=True)
    ral = models.CharField(max_length=50, blank=True, null=True) # Requested Alias
    finition = models.CharField(max_length=100, blank=True, null=True)
    dimensions = models.CharField(max_length=100, blank=True, null=True)
    
    prix_unitaire_ht = models.DecimalField(max_digits=10, decimal_places=2, default=0.0)
    # Requested fields on line item (snapshot)
    code_article = models.CharField(max_length=100, blank=True, null=True) 
    
    article = models.ForeignKey(Article, models.DO_NOTHING)
    commande = models.ForeignKey(Commande, models.DO_NOTHING)

    class Meta:
        managed = True
        db_table = 'commandes_lignecommande'

    def __str__(self):
        return str(self.pk)

class Prix(models.Model):
    id = models.UUIDField(primary_key=True)
    prix_nouveau = models.DecimalField(max_digits=10, decimal_places=2)
    prix_ancien = models.DecimalField(max_digits=10, decimal_places=2)
    date_nouveau_prix = models.DateField()
    date_ancien_prix = models.DateField(blank=True, null=True)
    article = models.ForeignKey(Article, models.DO_NOTHING)

    class Meta:
        managed = True
        db_table = 'commandes_prix'

    def __str__(self):
        return str(self.pk)

class RefCouleur(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    ral = models.CharField(max_length=50) # Ex: 7016
    nom_couleur = models.CharField(max_length=100) # Ex: Gris Anthracite
    
    SYSTEME_CHOICES = [
        ('INSTALLUX', 'Installux'),
        ('SCHUCO', 'Schüco'),
        ('STANDARD', 'Standard'),
        ('AUTRE', 'Autre'),
    ]
    systeme = models.CharField(max_length=50, choices=SYSTEME_CHOICES)
    
    code_mat = models.CharField(max_length=20, null=True, blank=True, help_text="Ex: EM ou M")
    code_sable = models.CharField(max_length=20, null=True, blank=True, help_text="Ex: SM ou S")
    code_brillant = models.CharField(max_length=20, null=True, blank=True, help_text="Ex: B")
    
    class Meta:
        managed = True
        db_table = 'core_ref_couleur'
        constraints = [
            models.UniqueConstraint(fields=['ral', 'systeme'], name='unique_ral_systeme')
        ]

    def __str__(self):
        return f"{self.ral} ({self.systeme})"

class CoreContact(models.Model):
    id = models.UUIDField(primary_key=True)
    nom = models.CharField(max_length=100)
    prenom = models.CharField(max_length=100)
    type_contact = models.CharField(max_length=20)
    telephone = models.CharField(max_length=20)
    email = models.CharField(max_length=254, blank=True, null=True)
    client = models.ForeignKey(Client, models.DO_NOTHING, blank=True, null=True)
    fournisseur = models.ForeignKey(Fournisseur, models.DO_NOTHING, blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'core_contact'

    def __str__(self):
        return str(self.nom)

class CoreParametre(models.Model):
    id = models.UUIDField(primary_key=True)
    nom_societe = models.CharField(max_length=100)
    chemin_stockage_local = models.CharField(max_length=255)

    class Meta:
        managed = True
        db_table = 'core_parametre'

    def __str__(self):
        return str(self.pk)

class Document(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    fichier = models.FileField(upload_to='documents/')
    ai_response = models.JSONField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    
    # AI Extracted Data
    TYPE_DOCUMENT_CHOICES = [
        ('DEVIS_CLIENT', 'Devis Client'),
        ('BON_COMMANDE', 'Bon de Commande'),
        ('ARC_FOURNISSEUR', 'ARC Fournisseur'),
        ('BON_LIVRAISON', 'Bon de Livraison'),
    ]
    type_document = models.CharField(max_length=100, choices=TYPE_DOCUMENT_CHOICES, null=True, blank=True)
    date_document = models.DateField(null=True, blank=True)
    
    # Optional links
    affaire = models.ForeignKey(Affaire, models.DO_NOTHING, blank=True, null=True)
    commande = models.ForeignKey(Commande, models.DO_NOTHING, blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'documents_document'

    def __str__(self):
        return f"Document {self.id} ({self.type_document})"