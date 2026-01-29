import pandas as pd
import json

csv_path = r"C:\Users\utopi\Desktop\Articles-fournisseur 13(Données).csv"

try:
    # Attempt reading with different separators common in French CSVs
    try:
        df = pd.read_csv(csv_path, sep=';', encoding='utf-8', nrows=5)
        if len(df.columns) < 2: raise ValueError("Not enough columns with ;")
    except:
        try:
            df = pd.read_csv(csv_path, sep=',', encoding='latin-1', nrows=5)
            if len(df.columns) < 2: raise ValueError("Not enough columns with ,")
        except:
             df = pd.read_csv(csv_path, sep='\t', encoding='latin-1', nrows=5)

    info = {
        "columns": df.columns.tolist(),
        "row_count_estimate": 0, # Skip for speed if large
        "sample": df.head(2).to_dict(orient='records')
    }
    
    print(json.dumps(info, indent=2, default=str))

except Exception as e:
    print(json.dumps({"error": str(e)}))
