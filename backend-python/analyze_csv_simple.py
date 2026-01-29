import pandas as pd
csv_path = r"C:\Users\utopi\Desktop\Articles-fournisseur 13(Données).csv"
try:
    try:
        df = pd.read_csv(csv_path, sep=';', encoding='utf-8', nrows=0)
    except:
        df = pd.read_csv(csv_path, sep=',', encoding='latin-1', nrows=0)
    
    print("COLUMNS_START")
    for c in df.columns:
        print(c)
    print("COLUMNS_END")
except Exception as e:
    print(e)
