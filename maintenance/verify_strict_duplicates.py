import pandas as pd

file_path = "Articles-fournisseur.xlsm"
output_file = "strict_duplicates_examples.txt"

with open(output_file, "w", encoding="utf-8") as f:
    try:
        df = pd.read_excel(file_path)
        df.columns = df.columns.astype(str).str.strip()
        
        # Find all strict duplicates (keep=False marks all duplicates as True)
        # subset=None means consider ALL columns
        dupes = df[df.duplicated(keep=False)]
        
        if dupes.empty:
            f.write("No strict duplicates found.\n")
        else:
            # Ensure Ref is string for sorting
            dupes['Ref'] = dupes['Ref'].astype(str)
            
            # Group by 'Ref' (or all columns) to show them together
            # sorting by Ref makes it easier to see the groups
            dupes_sorted = dupes.sort_values(by=['Ref'])
            
            f.write(f"Total Strict Duplicates Rows: {len(dupes)}\n")
            f.write("Here are 20 examples of groups of identical rows:\n")
            f.write("(These rows are absolutely identical in every column)\n\n")
            
            # Get 20 unique Refs that have duplicates
            unique_dupe_refs = dupes_sorted['Ref'].unique()[:20]
            
            for ref in unique_dupe_refs:
                f.write(f"--- DUPLICATE GROUP: Ref '{ref}' ---\n")
                group = dupes_sorted[dupes_sorted['Ref'] == ref]
                
                # Show key columns + a few others to prove identity
                # Adjust columns as needed for display width
                cols = ['Ref', 'Désignation', 'Fournisseur', 'Fabricant', 'Ref fournisseur', 'Prix/U HT']
                # Add Conditionnement if exists
                if 'Conditionnement' in df.columns:
                    cols.append('Conditionnement')
                
                # Filter cols that exist
                cols = [c for c in cols if c in df.columns]
                
                f.write(group[cols].to_string(index=True) + "\n")
                f.write(f"Count: {len(group)} identical copies.\n\n")

    except Exception as e:
        f.write(f"Error: {e}\n")

print(f"List written to {output_file}")
