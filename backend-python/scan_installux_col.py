import pandas as pd

FILE_PATH = r"C:\Users\utopi\Desktop\Arts Alu\Articles-fournisseur.xlsm"

def scan():
    try:
        df = pd.read_excel(FILE_PATH, engine='openpyxl')
        
        # Find which columns contain "Installux"
        for col in df.columns:
            matches = df[df[col].astype(str).str.contains('Installux', case=False, na=False)]
            if not matches.empty:
                print(f"--- MATCH FOUND IN COLUMN: '{col}' ---")
                print(matches[[col, 'Ref', 'Type']].head(5).to_string())
                print("---------------------------------------")

    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    scan()
