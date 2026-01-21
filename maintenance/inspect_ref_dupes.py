import pandas as pd
import sys

file_path = "Articles-fournisseur.xlsm"
output_file = "ref_dupes_report.txt"

with open(output_file, "w", encoding="utf-8") as f:
    try:
        df = pd.read_excel(file_path)
        df.columns = df.columns.astype(str).str.strip()
        
        col_name = 'Ref'
        target_val = '5855' # One of the top duplicates
        
        f.write(f"--- INSPECTING DUPLICATES FOR {col_name} = '{target_val}' ---\n")
        
        # Filter rows
        # Ensure string comparison
        df[col_name] = df[col_name].astype(str).str.strip()
        
        rows = df[df[col_name] == target_val]
        
        # Drop empty cols
        rows = rows.dropna(axis=1, how='all')
        
        f.write(rows.to_string() + "\n")
        
        # Also check '?'
        target_val_2 = '?'
        f.write(f"\n--- INSPECTING DUPLICATES FOR {col_name} = '{target_val_2}' ---\n")
        rows2 = df[df[col_name] == target_val_2]
        rows2 = rows2.dropna(axis=1, how='all')
        f.write(rows2.to_string() + "\n")

    except Exception as e:
        f.write(f"Error: {e}\n")

print(f"Results written to {output_file}")
