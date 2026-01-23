import uuid
from django.db import models
from django.core.validators import FileExtensionValidator
from apps.core.utils import generate_filename

class Document(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    fichier = models.FileField(upload_to=generate_filename, validators=[
        FileExtensionValidator(allowed_extensions=['pdf', 'jpg', 'jpeg', 'png', 'msg', 'eml'])
    ])
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
    
    # FK -> Ventes
    affaire = models.ForeignKey('ventes.Affaire', models.SET_NULL, blank=True, null=True)
    # FK -> Achats
    commande = models.ForeignKey('achats.Commande', models.SET_NULL, blank=True, null=True, related_name='documents')
    
    # Versioning / Active
    est_actif = models.BooleanField(default=True, help_text="Est la version courante de ce type de doc")

    class Meta:
        managed = True
        db_table = 'documents_document' # LEGACY

    def __str__(self):
        return f"Document {self.id} ({self.type_document})"
