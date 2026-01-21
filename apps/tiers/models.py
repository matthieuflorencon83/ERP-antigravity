import uuid
from django.db import models

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
        db_table = 'commandes_client' # LEGACY TABLE NAME

    def __str__(self):
        return str(self.nom)

class Fournisseur(models.Model):
    id = models.CharField(primary_key=True, max_length=50, editable=False)
    nom_fournisseur = models.CharField(max_length=150, db_index=True)
    nom_usage = models.CharField(max_length=150, blank=True, null=True)
    code_fournisseur = models.CharField(max_length=50, blank=True, null=True)
    adresse = models.TextField(blank=True, null=True)
    siret = models.CharField(max_length=14, blank=True, null=True)
    tva_intracommunautaire = models.CharField(max_length=20, blank=True, null=True)
    code_client = models.CharField(max_length=50, blank=True, null=True)
    notes = models.TextField(blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'commandes_fournisseur' # LEGACY TABLE NAME

    def __str__(self):
        return str(self.pk)

    def save(self, *args, **kwargs):
        if not self.code_fournisseur:
            import random
            import re
            prefix = "F"
            # Get first 3 alphanumeric chars from name
            clean_name = re.sub(r'[^A-Z0-9]', '', (self.nom_fournisseur or "").upper())[:3]
            if not clean_name: clean_name = "XXX"
            rand_suffix = random.randint(100, 999)
            self.code_fournisseur = f"{prefix}-{clean_name}-{rand_suffix}"
        
        # Ensure ID is set
        if not self.id:
            self.id = self.code_fournisseur
            
        super().save(*args, **kwargs)

class CoreContact(models.Model):
    id = models.UUIDField(primary_key=True)
    nom = models.CharField(max_length=100)
    prenom = models.CharField(max_length=100)
    type_contact = models.CharField(max_length=20)
    telephone = models.CharField(max_length=20)
    email = models.CharField(max_length=254, blank=True, null=True)
    client = models.ForeignKey(Client, models.CASCADE, blank=True, null=True)
    fournisseur = models.ForeignKey(Fournisseur, models.CASCADE, blank=True, null=True)

    class Meta:
        managed = True
        db_table = 'core_contact' # LEGACY TABLE NAME

    def __str__(self):
        return str(self.nom)
