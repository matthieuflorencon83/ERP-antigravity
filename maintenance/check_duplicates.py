import pandas as pd

file_path = "Articles-fournisseur.xlsm"
output_file = "duplicates_report.txt"

with open(output_file, "w", encoding="utf-8") as f:
    try:
        df = pd.read_excel(file_path)
        df.columns = df.columns.astype(str).str.strip()
        
        total_rows = len(df)
        
        # Check Ref fournisseur duplicates
        if 'Ref fournisseur' in df.columns:
            refs = df['Ref fournisseur'].astype(str).str.strip()
            unique_refs = refs.nunique()
            duplicates = total_rows - unique_refs
            
            f.write(f"Total Rows in Excel: {total_rows}\n")
            f.write(f"Unique Refs: {unique_refs}\n")
            f.write(f"Duplicate Refs found: {duplicates}\n")
            
            # Show top duplicates
            f.write("\nTop 5 Duplicate Refs:\n")
            top_dupes = refs.value_counts().head(5)
            f.write(str(top_dupes) + "\n")

            # Inspect one specific duplicate group to see differences
            target_ref = top_dupes.index[1] if len(top_dupes) > 1 else top_dupes.index[0] # Pick one that isn't nan
            f.write(f"\n--- INSPECTING DUPLICATES FOR REF: '{target_ref}' ---\n")
            
            dupe_rows = df[df['Ref fournisseur'].astype(str).str.strip() == target_ref]
            # Drop columns that are all NaN to reduce noise
            dupe_rows = dupe_rows.dropna(axis=1, how='all')
            
            f.write(dupe_rows.to_string() + "\n")
        else:
            f.write("Column 'Ref fournisseur' not found.\n")

    except Exception as e:
        f.write(f"Error: {e}\n")

print(f"Results written to {output_file}")
