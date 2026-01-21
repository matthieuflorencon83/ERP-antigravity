from django.db import models
import uuid

class CoreParametre(models.Model):
    id = models.UUIDField(primary_key=True)
    nom_societe = models.CharField(max_length=100)
    adresse = models.CharField(max_length=255, blank=True)
    code_postal = models.CharField(max_length=10, blank=True)
    ville = models.CharField(max_length=100, blank=True)
    siret = models.CharField(max_length=50, blank=True)
    email = models.EmailField(blank=True)
    telephone = models.CharField(max_length=20, blank=True)
    # logo = models.ImageField(upload_to='company/', blank=True) # Later
    chemin_stockage_local = models.CharField(max_length=255)
    gemini_api_key = models.CharField(max_length=255, blank=True, null=True, help_text="Clé API Google Gemini (Prioritaire sur le .env)")

    class Meta:
        managed = True
        db_table = 'core_parametre'

    def __str__(self):
        return str(self.pk)