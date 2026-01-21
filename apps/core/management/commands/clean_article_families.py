
from django.core.management.base import BaseCommand
from apps.core.models import Article, Fournisseur

class Command(BaseCommand):
    help = 'Clean and normalize Article families and Suppliers'

    def handle(self, *args, **options):
        self.stdout.write("Starting Advanced Data Cleaning...")

        # ==========================================
        # 1. SUPPLIER MERGE (Deduplication)
        # ==========================================
        # Map: "Duplicate Name Pattern" -> "Canonical Name"
        # We need to find the Target ID first or create it.
        
        supplier_maps = {
            'ARCELOR': 'ARCELOR Mittal', # Target
            'Arcelor Mittal (Berton Siccard)': 'ARCELOR Mittal',
            'Balitrand': 'BALITRAND',
            'Maccario': 'MACCARIO',
        }
        
        for duplicate_pattern, target_name in supplier_maps.items():
            # Find the TARGET supplier (best match)
            target = Fournisseur.objects.filter(nom_fournisseur__iexact=target_name).first()
            if not target:
                continue

            # Find duplicates (contain pattern but NOT the target ID)
            duplicates = Fournisseur.objects.filter(nom_fournisseur__icontains=duplicate_pattern).exclude(id=target.id)
            
            for dup in duplicates:
                count = Article.objects.filter(fournisseur=dup).count()
                if count > 0:
                    self.stdout.write(f"  > Merging '{dup.nom_fournisseur}' ({count} items) -> '{target.nom_fournisseur}'")
                    # Move articles to target
                    Article.objects.filter(fournisseur=dup).update(fournisseur=target)
                    
                    # Delete duplicate supplier
                    dup.nom_fournisseur = f"Z_MERGED_{dup.nom_fournisseur}"
                    dup.save()

        # ==========================================
        # 1.5. ARTICLE DEDUPLICATION (After Merge)
        # ==========================================
        self.stdout.write("  > Deduplicating Articles...")
        # Strategy: Group by (Supplier, Ref) and keep one.
        from django.db.models import Count
        
        duplicates = Article.objects.values('ref_fournisseur', 'fournisseur').annotate(count=Count('id')).filter(count__gt=1)
        
        count_deleted = 0
        for dup in duplicates:
            ref = dup['ref_fournisseur']
            fourn = dup['fournisseur']
            
            # Get all duplicates for this pair
            items = Article.objects.filter(ref_fournisseur=ref, fournisseur=fourn).order_by('-id') # Keep newest? Or oldest?
            # Let's keep the one with most info? Or just the first one?
            # Script above created identical items.
            
            # Keep the first one, delete the rest
            to_delete = items[1:] 
            for item in to_delete:
                # RE-ASSIGN DEPENDENCIES BEFORE DELETING
                # Move Besoins
                from apps.core.models import Besoin, LigneCommande
                Besoin.objects.filter(article=item).update(article=items[0])
                LigneCommande.objects.filter(article=item).update(article=items[0])
                
                # Now safe to delete
                item.delete()
                count_deleted += 1
                
        if count_deleted > 0:
            self.stdout.write(f"  > Deleted {count_deleted} duplicate articles.")

        # ==========================================
        # 2. FAMILY RESTRUCTURING
        # ==========================================
        # User Feedback: "chevron, profil, profil alu c est pareil. chevron c est une sous famille"
        # Strategy: Flatten everything to "Profil" family, with distinct sub-families.

        # 1. Chevron -> Profil / Chevron
        Article.objects.filter(famille__iexact='Chevron').update(famille='Profil', sous_famille='Chevron')
        
        # 2. Profil Alu -> Profil / Alu (if sub empty)
        Article.objects.filter(famille__iexact='Profil Alu', sous_famille='').update(famille='Profil', sous_famille='Alu')
        Article.objects.filter(famille__iexact='Profil Alu').update(famille='Profil') # Rename remaining

        # 3. Clean Key-Based Classification (Enhanced)
        corrections = {
            'Conpri Bande': 'Compri Bande',
            'Forêt': 'Outillage', 
            'Forêts': 'Outillage',
            'Lame': 'Outillage', 
            'Disque': 'Outillage',
            'Bouchon': 'Accessoire',
            'Equerre': 'Accessoire',
            'Cale': 'Accessoire',
             # Merge small families into generic ones
        }
        
        for old, new in corrections.items():
            Article.objects.filter(famille__iexact=old).update(famille=new)

        # 4. Keyword Rules for Designation (Refined)
        rules = [
            (['Vis', 'Boulon', 'Ecrou', 'Rondelle', 'Cheville', 'Tige fil'], 'Visserie', 'Standard'),
            (['Silicone', 'Mastic', 'Colle', 'Cartouche', 'Resine', 'Chimique', 'Spray'], 'Chimique', 'Consommable'),
            (['Vitrage', 'Verre', '4/16/4', '44.2', 'SP10', 'Stadip', 'Miroir'], 'Vitrage', 'Sur Mesure'),
            (['Joint', 'EPDM', 'Brosse', 'Fendue'], 'Joint', 'Standard'),
            (['Poignée', 'Paumelle', 'Gache', 'Crémone', 'Cylindre', 'Verrou', 'Ferme-porte'], 'Quincaillerie', 'Porte/Fenêtre'),
            (['Sunisol', 'Compri', 'Isolant', 'Laine', 'Mousse', 'Polystyrène'], 'Isolation', 'Standard'),
            (['Bastaing', 'Madrier'], 'Profil', 'Bois'), 
            # Specific Profile Sub-Families (User Request)
            (['Chéneau', 'Cheneau'], 'Profil', 'Chéneau'),
            (['Capot'], 'Profil', 'Capot'),
            (['Faitiere', 'Faîtière'], 'Profil', 'Faîtière'),
            (['Rehausse', 'Réhausse'], 'Profil', 'Réhausse'),
            (['Chevron'], 'Profil', 'Chevron'),
            (['Parclose', 'Pareclose', 'Parre close'], 'Profil', 'Parclose'),
            (['Renfort'], 'Profil', 'Renfort'), 
            (['Poteau', 'Demi Poteau'], 'Profil', 'Poteau'),
            (['Mural'], 'Profil', 'Mural'),
            (['Traverse', 'Lisse'], 'Profil', 'Traverse'),
            (['Dormant', 'Dorm.'], 'Profil', 'Dormant'),
            (['Ouvrant', 'Battue', 'Meneau'], 'Profil', 'Ouvrant'),
            (['Tapée', 'Tapee'], 'Profil', 'Tapée'),
            (['Epine'], 'Profil', 'Epine'),
            (['Bavette', 'Rejet', 'Recup'], 'Profil', 'Bavette'),
            (['Tube'], 'Profil', 'Tube'),
            (['Corniere', 'Cornière'], 'Profil', 'Cornière'),
            (['Plat', 'Fer plat', 'Meplat', 'Méplat'], 'Profil', 'Plat'),
            ([' U ', 'PROFIL U ', 'COULISSE U'], 'Profil', 'U'),
            ([' T ', 'PROFIL T '], 'Profil', 'T'),
            (['Main courante'], 'Profil', 'Main Courante'),
            (['Angle'], 'Profil', 'Angle'),
            (['Jonction', 'Liaison'], 'Profil', 'Jonction'),
            
            # Additional Corrections (Found in "Standard")
            (['Kit', 'Ferrure', 'Chariot', 'Bequille', 'Paumelle', 'Gache'], 'Quincaillerie', 'Accessoire'),
            (['Embout', 'Cale', 'Outil'], 'Accessoire', 'Standard'),

            # Roofing / Panels (Akraplast)
            (['Evolutop', 'Alustrong', 'Plaque', 'Panneau'], 'Toiture', 'Panneau'),
            (['Polycarbonate'], 'Toiture', 'Polycarbonate'),
            (['Sunisol'], 'Toiture', 'Sunisol'),
            (['Impact', 'Control'], 'Toiture', 'Impact'),
            (['Akratex'], 'Toiture', 'Akratex'),
            (['Platine'], 'Quincaillerie', 'Platine'),

            # Consumables / Tools (Wurth / Acdis)
            (['Meche', 'Foret', 'Mèche', 'Forêt'], 'Outillage', 'Consommable'),
            (['Fond de joint'], 'Joint', 'Fond de joint'),
            (['Bande', 'Butyl'], 'Etanchéité', 'Bande'),
            (['Aerosol', 'Spray', 'Bombe'], 'Chimique', 'Spray'),
            
            # Fallbacks
            (['Profil'], 'Profil', 'Standard'),   
            (['Tole', 'Tôle', 'Pliage'], 'Tôle', 'Alu'),
            (['Store', 'Volet', 'Tablier', 'Coulisse'], 'Fermeture', 'Volet Roulant'),
        ]

        count_rules = 0
        all_articles = Article.objects.all()
        
        for article in all_articles:
            designation = article.designation.title()
            matched = False
            
            for keywords, new_fam, new_sub in rules:
                if any(k.title() in designation for k in keywords):
                    # Force update for Profil sub-families even if family is already Profil
                    # to refine "Standard" or "PROFIL" into "Poteau", "Mural", etc.
                    is_profil_refinement = (new_fam == 'Profil' and article.famille == 'Profil')
                    
                    if article.famille in ['Divers', '', '?', 'Profil Alu'] or article.famille != new_fam or is_profil_refinement:
                         # Prioritize specific sub-family over generic "Standard" or "Alu"
                         if is_profil_refinement and article.sous_famille not in ['', 'PROFIL', 'Standard', 'Alu'] and new_sub in ['Standard', 'Alu']:
                             continue # Don't overwrite specific sub-fam with generic one

                         article.famille = new_fam
                         if new_sub:
                             article.sous_famille = new_sub
                         article.save()
                         count_rules += 1
                         matched = True
                         break
            
            if not matched and not article.famille:
                article.famille = 'Divers'
                article.save()

        self.stdout.write(self.style.SUCCESS(f"Advanced Cleaning Complete. Rules applied: {count_rules}"))

