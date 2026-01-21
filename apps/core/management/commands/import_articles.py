import pandas as pd
from django.core.management.base import BaseCommand
from apps.core.models import Article, Fournisseur
from decimal import Decimal
import os

try:
    from tqdm import tqdm
except ImportError:
    def tqdm(x, **kwargs):
        return x

class Command(BaseCommand):
    help = 'Import articles from Excel file (Articles-fournisseur.xlsm)'

    def handle(self, *args, **options):
        file_path = "Articles-fournisseur.xlsm"

        if not os.path.exists(file_path):
            self.stdout.write(self.style.ERROR(f"Fichier non trouvé : {file_path}"))
            return

        self.stdout.write(self.style.SUCCESS(f"Lecture du fichier : {file_path}..."))

        try:
            # Lecture avec pandas
            df = pd.read_excel(file_path)
            # Nettoyage des noms de colonnes (strip)
            df.columns = df.columns.astype(str).str.strip()
            
            total_rows = len(df)
            self.stdout.write(f"{total_rows} lignes trouvées.")

            # Conversion des colonnes critiques en string pour éviter les NaN/float
            df['Ref fournisseur'] = df['Ref fournisseur'].astype(str).replace('nan', '').str.strip()
            df['Désignation'] = df['Désignation'].astype(str).replace('nan', '').str.strip()
            df['Fournisseur'] = df['Fournisseur'].astype(str).replace('nan', '').str.strip()
            
            updated_count = 0
            created_count = 0
            error_count = 0

            # Itération
            iterator = tqdm(df.iterrows(), total=total_rows, desc="Importation")

            for index, row in iterator:
                try:
                    ref_fournisseur = row.get('Ref fournisseur')
                    if not ref_fournisseur:
                        # Skip empty refs
                        continue

                    # 1. Gestion Fournisseur
                    nom_fournisseur = row.get('Fournisseur')
                    if not nom_fournisseur:
                        nom_fournisseur = "INCONNU"
                    
                    fournisseur, _ = Fournisseur.objects.get_or_create(
                        nom_fournisseur=nom_fournisseur,
                        defaults={'nom_usage': nom_fournisseur}
                    )

                    # 2. Nettoyage Prix
                    raw_price = row.get('Prix/U HT', 0)
                    try:
                        prix_ht = Decimal(str(raw_price).replace(',', '.')) if pd.notnull(raw_price) else Decimal('0.00')
                    except Exception:
                        prix_ht = Decimal('0.00')

                    # 3. Stock Logique (Oui -> 1 (quantity?) or just separate field handling? 
                    # Prompt said: "Si 'Oui' -> True". But model is Decimal. 
                    # We will store 1.0 if 'Oui' to indicate presence, 0.0 otherwise.
                    # This is a bit hacky but fits the constraints given the model schema.
                    raw_stock = str(row.get('tenu en stock', '')).strip().lower()
                    stock_qty = Decimal('1.00') if raw_stock == 'oui' else Decimal('0.00')

                    # 4. Conditionnement & New Fields
                    conditionnement = str(row.get('Conditionnement', '')).strip()
                    if conditionnement == 'nan': conditionnement = ''
                    
                    fabricant = str(row.get('Fabricant', '')).strip().replace('nan', '')
                    type_article = str(row.get('Type', '')).strip().replace('nan', '')
                    
                    # Ref fournisseur (Col H) -> mapped to ref_fabricant
                    ref_fab_val = str(row.get('Ref fournisseur', '')).strip().replace('nan', '')

                    # 5. Création / Update
                    # UNIQUE KEY CHANGED: Use 'Ref' (Col B) instead of 'Ref fournisseur' (Col H) which is not unique
                    ref_unique = str(row.get('Ref', '')).strip()
                    cleaned_ref = ref_unique.replace('nan', '')
                    
                    # Logic: If Ref is empty or '?', fallback to 'Ref fournisseur' to avoid merging distinct items like "Tube ?" and "Cache vis ?"
                    if not cleaned_ref or cleaned_ref == '?':
                         if ref_fab_val:
                             ref_unique = ref_fab_val
                         else:
                             # If both are empty, we can't identify the article securely.
                             pass
                    else:
                        ref_unique = cleaned_ref
                    
                    try:
                        # KEY CHANGE: Lookup by Ref AND Supplier to handle collisions (same ref, diff supplier)
                        article, created = Article.objects.update_or_create(
                            ref_fournisseur=ref_unique, 
                            fournisseur=fournisseur, 
                            defaults={
                                'designation': row.get('Désignation', 'Sans désignation'),
                                'famille': str(row.get('Famille', '')).replace('nan', ''),
                                'prix_unitaire_ht': prix_ht,
                                'unite': str(row.get('Unité Qte', 'U')).replace('nan', 'U'),
                                'stock': stock_qty,
                                'conditionnement': conditionnement,
                                'fabricant': fabricant,
                                'type_article': type_article,
                                'ref_fabricant': ref_fab_val,
                                'sous_famille': type_article,
                                'lg': '',
                            }
                        )
                    except Article.MultipleObjectsReturned:
                        # This handles cases where we somehow already have duplicates for (Ref, Supplier)
                        self.stdout.write(self.style.WARNING(f"Doublons stricts trouvés pour (Ref={ref_unique}, Fourn={nom_fournisseur}). Nettoyage..."))
                        Article.objects.filter(ref_fournisseur=ref_unique, fournisseur=fournisseur).delete()
                        article = Article.objects.create(
                            ref_fournisseur=ref_unique,
                            fournisseur=fournisseur,
                            designation=row.get('Désignation', 'Sans désignation'),
                            famille=str(row.get('Famille', '')).replace('nan', ''),
                            prix_unitaire_ht=prix_ht,
                            unite=str(row.get('Unité Qte', 'U')).replace('nan', 'U'),
                            stock=stock_qty,
                            conditionnement=conditionnement,
                            fabricant=fabricant,
                            type_article=type_article,
                            ref_fabricant=ref_fab_val,
                            sous_famille=type_article,
                            lg=''
                        )
                        created = True

                    if created:
                        created_count += 1
                    else:
                        updated_count += 1

                except Exception as e:
                    error_count += 1
                    self.stdout.write(self.style.WARNING(f"Erreur ligne {index}: {str(e)}"))

            self.stdout.write(self.style.SUCCESS(f"Terminé ! Créés: {created_count}, Mis à jour: {updated_count}, Erreurs: {error_count}"))

        except Exception as e:
            self.stdout.write(self.style.ERROR(f"Erreur globale : {str(e)}"))
