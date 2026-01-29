import pandas as pd

FILE_PATH = r"C:\Users\utopi\Desktop\Arts Alu\Articles-fournisseur.xlsm"

def scan():
    try:
        print(f"Scanning {FILE_PATH}...")
        df = pd.read_excel(FILE_PATH, engine='openpyxl')
        
        # Search in all string columns
        mask = df.apply(lambda x: x.astype(str).str.contains('Installux', case=False, na=False)).any(axis=1)
        results = df[mask]
        
        print(f"Found {len(results)} rows containing 'Installux'")
        if not results.empty:
            print(results.head(5)[['Ref', 'Type', 'Famille']].to_string())
            
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    scan()
