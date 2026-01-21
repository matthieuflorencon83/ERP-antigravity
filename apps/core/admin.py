from django.contrib import admin
from apps.ventes.models import Affaire
from apps.catalogue.models import Article
from apps.tiers.models import Client, Fournisseur
from apps.achats.models import Commande, LigneCommande
from apps.ged.models import Document

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
    list_display = ('designation', 'affaire', 'statut', 'total_ht', 'date_commande')
    list_filter = ('statut',)



admin.site.register(Client)
admin.site.register(Fournisseur)
admin.site.register(LigneCommande)
admin.site.register(Document)
