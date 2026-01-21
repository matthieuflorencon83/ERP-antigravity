import pandas as pd
import os

file_path = "Articles-fournisseur.xlsm"

if os.path.exists(file_path):
    try:
        df = pd.read_excel(file_path)
        types = df['Type'].dropna().unique().tolist()
        print(f"Valeurs uniques dans colonne 'Type': {types[:20]}") # Show first 20
        print(f"Nombre de valeurs uniques: {len(types)}")
    except Exception as e:
        print(f"Erreur: {e}")
else:
    print("Fichier non trouvé")
