import pandas as pd
import os

file_path = "Articles-fournisseur.xlsm"
output_file = "audit_result.txt"

with open(output_file, "w", encoding="utf-8") as f:
    if os.path.exists(file_path):
        try:
            # Lire le fichier (sans header pour voir la brute si besoin, mais ici on prend le header)
            df = pd.read_excel(file_path, nrows=5) 
            
            f.write(f"✅ FICHIER TROUVÉ : {file_path}\n")
            f.write("="*50 + "\n")
            f.write("LISTE EXACTE DES COLONNES DANS L'EXCEL :\n")
            f.write(str(df.columns.tolist()) + "\n")
            f.write("="*50 + "\n")
            f.write("EXEMPLE DE LA PREMIÈRE LIGNE DE DONNÉES :\n")
            # Affiche la 1ère ligne sous forme de dictionnaire pour voir le lien Colonne -> Valeur
            f.write(str(df.iloc[0].to_dict()) + "\n")
            f.write("="*50 + "\n")
        except Exception as e:
            f.write(f"❌ Erreur de lecture : {e}\n")
    else:
        f.write(f"❌ Le fichier {file_path} est introuvable à la racine.\n")

print("Audit finished. Results written to audit_result.txt")
