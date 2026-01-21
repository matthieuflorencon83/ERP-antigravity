from django.contrib import admin
from .models import (
    Affaire, Article, Client, Commande, Fournisseur, 
    Lignecommande, Prix, RefCouleur, Document, CoreContact
)

@admin.register(Affaire)
class AffaireAdmin(admin.ModelAdmin):
    list_display = ('nom_affaire', 'client', 'statut', 'date_creation')
    list_filter = ('statut',)
    search_fields = ('nom_affaire', 'client__nom')

@admin.register(Article)
class ArticleAdmin(admin.ModelAdmin):
    list_display = ('ref_fournisseur', 'designation', 'stock', 'prix_unitaire_ht')
    search_fields = ('ref_fournisseur', 'designation')

@admin.register(Commande)
class CommandeAdmin(admin.ModelAdmin):
    list_display = ('designation', 'affaire', 'statut', 'prix_total_ht', 'date_commande')
    list_filter = ('statut',)

@admin.register(RefCouleur)
class RefCouleurAdmin(admin.ModelAdmin):
    list_display = ('ral', 'systeme', 'nom_couleur', 'code_mat', 'code_sable', 'code_brillant')
    list_filter = ('systeme',)
    search_fields = ('ral', 'nom_couleur', 'systeme')

admin.site.register(Client)
admin.site.register(Fournisseur)
admin.site.register(Lignecommande)
admin.site.register(Document)
