from django.db import transaction
from django.utils import timezone
from .models import Commande, LigneCommande

def split_commande_from_arc(commande_origine, lignes_arc_ids):
    """
    Scinde une commande en deux suite à un ARC partiel.
    - commande_origine : La commande actuelle (gardera les lignes confirmées par l'ARC).
    - lignes_arc_ids : Liste des IDs de LigneCommande QUI SONT DANS L'ARC.
                       Les autres lignes seront déplacées vers la nouvelle commande.
    
    Retourne (commande_origine, nouvelle_commande)
    """
    
    # 1. Identifier les lignes à déplacer (ceux qui NE SONT PAS dans l'ARC)
    lignes_a_deplacer = commande_origine.lignes.exclude(id__in=lignes_arc_ids)
    
    if not lignes_a_deplacer.exists():
        return commande_origine, None # Rien à scinder
        
    with transaction.atomic():
        # 2. Créer la nouvelle commande (Enfant/Reliquat)
        # Suffixe pour le numéro ? On n'a pas de champ numero, mais on pourrait modifier la designation
        nouvelle_commande = Commande.objects.create(
            affaire=commande_origine.affaire,
            fournisseur=commande_origine.fournisseur,
            statut='ENVOYEE', # Repart en attente d'un autre ARC
            canal=commande_origine.canal,
            designation=f"{commande_origine.designation or ''} (Reliquat ARC)",
            date_commande=commande_origine.date_commande,
            # On ne copie pas les docs, c'est une nouvelle vie
        )
        
        # 3. Déplacer les lignes
        # On update le champ FK 'commande'
        lignes_a_deplacer.update(commande=nouvelle_commande)
        
        # 4. Mettre à jour les totaux des deux commandes
        _recalculate_totals(commande_origine)
        _recalculate_totals(nouvelle_commande)
        
        # 5. La commande d'origine passe en CONFIRME_ARC (car elle ne contient maintenant que ce qui est dans l'ARC)
        commande_origine.statut = 'CONFIRME_ARC'
        commande_origine.save()
        
    return commande_origine, nouvelle_commande

def _recalculate_totals(commande):
    from decimal import Decimal
    lines = commande.lignes.all()
    # Recalcul basique
    total_ht = sum(Decimal(str(line.quantite)) * line.prix_unitaire for line in lines)
    commande.total_ht = total_ht
    commande.tva = total_ht * Decimal('0.20')
    commande.total_ttc = commande.total_ht + commande.tva
    commande.save()
