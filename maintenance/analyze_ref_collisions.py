import pandas as pd
import sys

file_path = "Articles-fournisseur.xlsm"
output_file = "collision_report.txt"

with open(output_file, "w", encoding="utf-8") as f:
    try:
        df = pd.read_excel(file_path)
        df.columns = df.columns.astype(str).str.strip()
        
        # Ensure strings
        df['Ref'] = df['Ref'].astype(str).str.strip().replace('nan', '')
        df['Fournisseur'] = df['Fournisseur'].astype(str).str.strip().replace('nan', '')
        df['Fabricant'] = df['Fabricant'].astype(str).str.strip().replace('nan', '')
        
        # Group by Ref and count unique Suppliers
        f.write("--- ANALYSING REF COLLISIONS (Same Ref, Different Supplier) ---\n")
        
        # Filter out empty refs
        df = df[df['Ref'] != '']
        
        collision_count = 0
        
        # Iterate over unique Refs that have duplicates
        ref_counts = df['Ref'].value_counts()
        dupe_refs = ref_counts[ref_counts > 1].index
        
        for ref in dupe_refs:
            rows = df[df['Ref'] == ref]
            unique_suppliers = rows['Fournisseur'].nunique()
            unique_manufacturers = rows['Fabricant'].nunique()
            
            if unique_suppliers > 1 or unique_manufacturers > 1:
                collision_count += 1
                if collision_count <= 10: # Only show first 10 details
                    f.write(f"\nCONFLICT DETECTED for Ref: '{ref}'\n")
                    f.write(f"Unique Suppliers: {unique_suppliers}, Unique Manufacturers: {unique_manufacturers}\n")
                    # Show the varying content
                    cols_to_show = ['Ref', 'Fournisseur', 'Fabricant', 'Désignation'] 
                    # Add Conditionnement if present as user mentioned it might be different too
                    if 'Conditionnement' in df.columns:
                        cols_to_show.append('Conditionnement')
                        
                    f.write(rows[cols_to_show].to_string(index=False) + "\n")
        
        f.write(f"\nTotal Refs with Supplier/Manufacturer conflicts: {collision_count}\n")
        
        # Also check strict duplicates vs non-strict
        # If we use strict deduplication (all columns match), how many unique rows?
        f.write("\n--- STRICT DEDUPLICATION CHECK ---\n")
        # Define 'meaningful' columns for uniqueness (excluding maybe internal processing cols if any)
        # Using all columns for now
        n_rows = len(df)
        n_strict_unique = len(df.drop_duplicates())
        f.write(f"Total Rows: {n_rows}\n")
        f.write(f"Strictly Unique Rows (all cols identical): {n_strict_unique}\n")
        f.write(f"Potential Duplicate Rows: {n_rows - n_strict_unique}\n")

    except Exception as e:
        f.write(f"Error: {e}\n")

print(f"Analysis written to {output_file}")
