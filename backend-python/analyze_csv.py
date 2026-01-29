import pandas as pd
import sys

csv_path = r"C:\Users\utopi\Desktop\Articles-fournisseur 13(Données).csv"

try:
    # Try reading with different encodings/separators as CSVs can be tricky
    try:
        df = pd.read_csv(csv_path, sep=';', encoding='utf-8', nrows=5)
    except:
        df = pd.read_csv(csv_path, sep=',', encoding='latin-1', nrows=5)
        
    print("--- CSV COLUMNS ---")
    for col in df.columns:
        print(f"'{col}'")
        
    print("\n--- SAMPLE DATA (First 3 rows) ---")
    print(df.head(3).to_string())
    
    print("\n--- ROW COUNT ESTIMATE ---")
    # Quick count
    with open(csv_path, 'r', encoding='latin-1') as f:
         print(sum(1 for line in f))

except Exception as e:
    print(f"Error analyzing CSV: {e}")
