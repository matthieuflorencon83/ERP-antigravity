import pandas as pd
import sys

# Force utf-8 output
sys.stdout.reconfigure(encoding='utf-8')

FILE_PATH = r"C:\Users\utopi\Desktop\Arts Alu\Articles-fournisseur.xlsm"

def analyze():
    try:
        df = pd.read_excel(FILE_PATH, engine='openpyxl')
        print(f"Total Columns: {len(df.columns)}")
        print("--- COLUMNS ---")
        for col in df.columns:
            print(f"- {col}")
        print("--- END ---")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    analyze()
