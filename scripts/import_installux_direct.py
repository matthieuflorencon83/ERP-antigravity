import os
import sys
import django
import re
from decimal import Decimal

# 1. SETUP DJANGO
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from apps.core.models import Article, Fournisseur
try:
    import pdfplumber
except ImportError:
    print("❌ pdfplumber manquant. Installation...")
    import subprocess
    subprocess.check_call([sys.executable, "-m", "pip", "install", "pdfplumber"])
    import pdfplumber

# CONFIGURATION
PDF_PATH = "tarif-installux-fr.pdf"

def parse_price(value):
    """Nettoie et convertit en Decimal (Monétaire)"""
    if not value: return Decimal('0.00')
    clean = re.sub(r'[^\d.,]', '', str(value)) # Garde chiffres, points, virgules
    clean = clean.replace(',', '.')
    # Handle multiple dots if any (1.200.00 -> 1200.00) mechanism roughly
    if clean.count('.') > 1:
        clean = clean.replace('.', '', clean.count('.') - 1)
    try:
        return Decimal(clean)
    except:
        return Decimal('0.00')

def run_import():
    print("🚀 Initialisation de l'import direct...")

    # 2. GESTION DES FOURNISSEURS
    f_installux, _ = Fournisseur.objects.get_or_create(
        nom_fournisseur="Installux",
        defaults={'code_fournisseur': 'INS'}
    )
    f_arcelor, _ = Fournisseur.objects.get_or_create(
        nom_fournisseur="Arcelor Mittal (Berton Siccard)",
        defaults={'code_fournisseur': 'ARC'}
    )
    
    print(f"✅ Fournisseurs prêts : {f_installux.nom_fournisseur} & {f_arcelor.nom_fournisseur}")

    if not os.path.exists(PDF_PATH):
        print(f"❌ ERREUR : Le fichier {PDF_PATH} est introuvable à la racine de {os.getcwd()} !")
        return

    count_created = 0
    count_updated = 0

    # 3. LECTURE PDF
    with pdfplumber.open(PDF_PATH) as pdf:
        total_pages = len(pdf.pages)
        
        for i, page in enumerate(pdf.pages):
            print(f"📄 Traitement page {i+1}/{total_pages}...", end="\r")
            
            tables = page.extract_tables()
            
            for table in tables:
                for row in table:
                    # Nettoyage
                    row = [cell.strip() if cell else "" for cell in row]
                    
                    # Filtre : Ligne valide (Ref + Prix)
                    if len(row) < 5: continue
                    ref = row[0]
                    designation = row[1]
                    
                    if "Référence" in ref or not ref: continue

                    try:
                        # Extraction Données
                        prix = parse_price(row[4]) # Colonne Prix Brut
                        unite = row[3] if len(row) > 3 else "U"
                        cond = row[2] if len(row) > 2 else ""
                        
                        if prix > 0:
                            # 4. INJECTION BASE DE DONNEES (Double Shot)
                            
                            # Pour Installux
                            obj, created = Article.objects.update_or_create(
                                fournisseur=f_installux,
                                ref_fournisseur=ref,
                                defaults={
                                    'designation': designation,
                                    'prix_unitaire_ht': prix,  # Corrected from prix_achat
                                    'unite': unite,
                                    'conditionnement': cond,
                                    'famille': 'Profils Alu',
                                    'sous_famille': 'Standard', # Default to prevent integrity error
                                    'lg': '-'                   # Default to prevent integrity error
                                }
                            )
                            
                            # Pour Arcelor
                            obj2, created2 = Article.objects.update_or_create(
                                fournisseur=f_arcelor,
                                ref_fournisseur=ref,
                                defaults={
                                    'designation': designation,
                                    'prix_unitaire_ht': prix,
                                    'unite': unite,
                                    'conditionnement': cond,
                                    'famille': 'Profils Alu',
                                    'sous_famille': 'Standard',
                                    'lg': '-'
                                }
                            )

                            if created or created2: count_created += 1
                            else: count_updated += 1

                    except Exception:
                        # print(f"Error on row {row}: {e}")
                        continue

    print("\n✅ TERMINÉ ! Bilan :")
    print(f"➕ Créés : {count_created} articles (x2 fournisseurs)")
    print(f"🔄 Mis à jour : {count_updated} articles")

if __name__ == "__main__":
    run_import()
