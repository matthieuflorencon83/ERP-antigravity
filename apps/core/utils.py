import os
from django.utils.text import slugify
from django.utils import timezone

def generate_filename(instance, filename):
    """
    Génère un nom de fichier standardisé : YYYYMMDD_Fournisseur_Client_Designation_Type_vX.ext
    Chemin : documents/{doc_type}/{year}/{filename}
    """
    import datetime
    
    # Déterminer les objets liés
    commande = getattr(instance, 'commande', None)
    doc_type = getattr(instance, 'type_document', 'AUTRE')

    # Fallback si pas de commande directe (Peu probable avec le nouveau modèle strict)
    if not commande and hasattr(instance, 'affaire'):
        pass 
    elif instance.__class__.__name__ == 'Commande':
        # Cas Legacy (si upload_to est appelé sur Commande, ce qui ne devrait plus arriver avec le refactor)
        return f"documents/legacy/{filename}"

    if not commande:
        return f"documents/vrac/{filename}"
        
    # Extraction des Données
    today = timezone.now().strftime('%Y%m%d')
    fournisseur = slugify(commande.fournisseur.nom_fournisseur)[:15] if commande.fournisseur else 'Inconnu'
    client = slugify(commande.affaire.client.nom)[:15] if (commande.affaire and commande.affaire.client) else 'Stock'
    # type_doc est déjà set
    
    # Versioning
    # On regarde combien de documents de ce type existent déjà pour cette commande
    # Note: On importe Document ici pour éviter l'import circulaire au niveau module
    from apps.ged.models import Document
    existing_count = Document.objects.filter(commande=commande, type_document=doc_type).count()
    version = existing_count + 1
    
    # Extension
    ext = filename.split('.')[-1].lower()
    
    # Construction du Nom
    new_name = f"{today}_{fournisseur}_{client}_{doc_type}_v{version}.{ext}"
    
    # Dossier
    year = timezone.now().strftime('%Y')
    
    return os.path.join(f"documents/{doc_type}/{year}/", new_name)
