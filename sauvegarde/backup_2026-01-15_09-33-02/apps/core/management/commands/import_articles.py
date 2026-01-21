from django.core.management.base import BaseCommand
from apps.core.models import Article, Fournisseur
from django.utils.text import slugify
import pandas as pd
import os
import sys

class Command(BaseCommand):
    help = 'Import articles from an Excel file (Articles-fournisseur.xlsm)'

    def add_arguments(self, parser):
        parser.add_argument('--path', type=str, required=True, help='Path to the Excel file')

    def handle(self, *args, **options):
        file_path = options['path']

        if not os.path.exists(file_path):
            self.stdout.write(self.style.ERROR(f'File not found: {file_path}'))
            return

        self.stdout.write(f"Reading file: {file_path}")
        
        try:
            # Using pandas to read Excel
            df = pd.read_excel(file_path)
            
            # Normalize column names: lowercase, strip, remove accents/special chars for easier mapping
            # Example: "Sous-Famille" -> "sous_famille", "Prix" -> "prix"
            df.columns = [
                str(col).strip().lower()
                .replace('é', 'e').replace('è', 'e')
                .replace(' ', '_').replace('-', '_') 
                for col in df.columns
            ]
            
            self.stdout.write(f"Columns found: {list(df.columns)}")

            count_created = 0
            count_updated = 0
            
            for index, row in df.iterrows():
                try:
                    # 1. Extraction & Cleaning
                    # Actual columns: ['cles', 'ref', 'type', 'famille', 'designation', 'fournisseur', 
                    # 'fabricant', 'ref_fournisseur', 'tenu_en_stock', 'conditionnement', 'multiple_cde', 
                    # 'unite_qte', 'ancien_prix/u_ht', 'prix/u_ht', 'unite_/_prix', 'coeff_conversion', 'date_prix']

                    # Try specific column first, fallback to generic 'ref'
                    ref = str(row.get('ref_fournisseur', '')).strip()
                    if not ref or ref == 'nan':
                        ref = str(row.get('ref', '')).strip()
                    
                    if not ref or ref == 'nan':
                        continue 

                    designation = str(row.get('designation', 'Sans désignation')).strip()
                    if designation == 'nan': designation = 'Sans désignation'
                    
                    famille = str(row.get('famille', '')).upper().strip()
                    if famille == 'NAN': famille = 'DIVERS'
                    
                    # Mapping 'type' to sous_famille as it seems to be the closest equivalent
                    sous_famille = str(row.get('type', '')).upper().strip()
                    if sous_famille == 'NAN': sous_famille = ''
                    
                    nom_fournisseur = str(row.get('fournisseur', 'INCONNU')).strip()
                    if not nom_fournisseur or nom_fournisseur == 'nan':
                        nom_fournisseur = 'INCONNU'

                    unite = str(row.get('unite_qte', 'U')).strip()
                    if not unite or unite == 'nan': 
                        unite = str(row.get('unite_/_prix', 'U')).strip()
                    if not unite or unite == 'nan': unite = 'U'

                    # Price handling - checking 'prix/u_ht'
                    prix_raw = row.get('prix/u_ht', 0)
                    try:
                        prix = float(prix_raw)
                        if pd.isna(prix): prix = 0.0
                    except (ValueError, TypeError):
                        prix = 0.0

                    # 2. Manage Fournisseur
                    # Generate a simple ID if not relying on database auto-id (Fournisseur model has CharField pk)
                    fourn_slug = slugify(nom_fournisseur)[:50].upper()
                    if not fourn_slug:
                        fourn_slug = 'DEFAULT'
                        
                    fournisseur, created_f = Fournisseur.objects.get_or_create(
                        id=fourn_slug,
                        defaults={
                            'nom_fournisseur': nom_fournisseur,
                            'nom_usage': nom_fournisseur
                        }
                    )
                    if created_f:
                        self.stdout.write(self.style.SUCCESS(f"  + Created Fournisseur: {nom_fournisseur}"))

                    # 3. Update or Create Article
                    # Note: 'lg' and 'conditionnement' are required fields in Article model but not in Excel spec.
                    # We provide defaults to avoid IntegrityError.
                    
                    defaults = {
                        'designation': designation,
                        'famille': famille,
                        'sous_famille': sous_famille,
                        'fournisseur': fournisseur,
                        'prix_unitaire_ht': prix,
                        'unite': unite,
                        'lg': str(row.get('lg', '-')), # Default if not present
                        'conditionnement': str(row.get('conditionnement', '-')), # Default if not present
                    }

                    obj, created = Article.objects.update_or_create(
                        ref_fournisseur=ref,
                        defaults=defaults
                    )

                    if created:
                        count_created += 1
                        if count_created % 10 == 0:
                            self.stdout.write(f"Importing... Created {count_created}, Updated {count_updated}")
                    else:
                        count_updated += 1
                
                except Exception as e:
                    self.stdout.write(self.style.ERROR(f"Error processing row {index}: {e}"))
                    
            self.stdout.write(self.style.SUCCESS(f"Import finished. Created: {count_created}, Updated: {count_updated}"))

        except Exception as e:
            self.stdout.write(self.style.ERROR(f"Critical Error: {e}"))
