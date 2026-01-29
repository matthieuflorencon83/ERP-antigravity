import pandas as pd
import mysql.connector
import sys

EXCEL_PATH = r"C:\Users\utopi\Desktop\Arts Alu\Articles-fournisseur.xlsm"
DB_CONFIG = { 'user': 'root', 'password': '', 'host': '127.0.0.1', 'database': 'erp_arts_alu' }

def connect_db(): return mysql.connector.connect(**DB_CONFIG)

def safe_get(row, col_name):
    val = row.get(col_name)
    if pd.isna(val): return None
    return str(val).strip()

def import_articles():
    print("Starting Final Corrected Import...")
    try:
        df = pd.read_excel(EXCEL_PATH, engine='openpyxl')
        df.columns = df.columns.str.strip()
    except Exception as e:
        print(f"Error reading Excel: {e}")
        return

    conn = connect_db()
    cursor = conn.cursor()
    
    installux_count = 0
    installux_success = 0
    
    # Iterate
    for index, row in df.iterrows():
        try:
            # Correct Detection Logic (No Truncation)
            is_installux = row.astype(str).str.contains('Installux', case=False).any()
            
            if not is_installux:
                continue

            installux_count += 1
            
            # Mapping
            code_art = safe_get(row, 'Ref')
            if not code_art:
                 keys_val = safe_get(row, 'Clés')
                 if keys_val: code_art = keys_val
            
            if not code_art:
                code_art = f"INST_{index}"
            
            designation = safe_get(row, 'Type')
            if not designation: designation = code_art

            famille = safe_get(row, 'Famille')
            ssfamille = safe_get(row, 'SsFamille')
            
            poid = row.get('Poids')
            if pd.isna(poid): poid = 0
            
            tenu_stock = 1 if 'OUI' in str(row.get('Stock', '')).upper() else 0
            cond = safe_get(row, 'cond')
            unite = safe_get(row, 'Unité')
            
            # Check exist
            cursor.execute("SELECT code_art FROM article WHERE code_art = %s", (code_art,))
            existing = cursor.fetchone()
            
            fabricant = 'Installux'
            
            if existing:
                sql = "UPDATE article SET designation=%s, famille=%s, ssfamille=%s, poid=%s, tenu_en_stock=%s, Conditionnement=%s, unite=%s, Fabricant=%s WHERE code_art=%s"
                cursor.execute(sql, (designation, famille, ssfamille, poid, tenu_stock, cond, unite, fabricant, code_art))
            else:
                sql = "INSERT INTO article (code_art, designation, famille, ssfamille, poid, tenu_en_stock, Conditionnement, unite, Fabricant) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"
                cursor.execute(sql, (code_art, designation, famille, ssfamille, poid, tenu_stock, cond, unite, fabricant))
                print(f"[INSERT] {code_art}")
            
            installux_success += 1
                
        except Exception as e:
            print(f"[ERROR] Row {index}: {e}")
            continue

    conn.commit()
    cursor.close()
    conn.close()
    
    print(f"Installux Total: {installux_count}")
    print(f"Installux Success: {installux_success}")

if __name__ == "__main__":
    import_articles()
