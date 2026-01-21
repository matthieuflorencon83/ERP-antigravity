import pandas as pd

file_path = "Articles-fournisseur.xlsm"

try:
    df = pd.read_excel(file_path)
    df.columns = df.columns.astype(str).str.strip()
    
    col_name = 'Clés' # Col A
    
    if col_name in df.columns:
        vals = df[col_name].astype(str).str.strip()
        total = len(vals)
        unique = vals.nunique()
        dupes = total - unique
        
        print(f"Column '{col_name}':")
        print(f"Total: {total}")
        print(f"Unique: {unique}")
        print(f"Duplicates: {dupes}")
        
        if dupes > 0:
            print("Top duplicates:")
            print(vals.value_counts().head(5))
    else:
        print(f"Column '{col_name}' not found. Available: {df.columns.tolist()}")

except Exception as e:
    print(f"Error: {e}")
