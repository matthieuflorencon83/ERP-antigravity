import pandas as pd
import sys

FILE_PATH = r"C:\Users\utopi\Desktop\Arts Alu\Articles-fournisseur.xlsm"

def analyze():
    try:
        # Load Excel file (Sheet 1 usually)
        df = pd.read_excel(FILE_PATH, engine='openpyxl')
        
        print("COLUMNS:")
        print(list(df.columns))
        
        print("\nSAMPLE DATA (First 3 rows):")
        print(df.head(3).to_dict(orient='records'))
        
        print("\nTOTAL ROWS:", len(df))
        
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    analyze()
