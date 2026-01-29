import mysql.connector

DB_CONFIG = {
    'user': 'root',
    'password': '',
    'host': '127.0.0.1',
    'database': 'erp_arts_alu'
}

def check():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        print("--- Checking 'article' table ---")
        cursor.execute("SELECT code_art, designation FROM article WHERE designation LIKE '%Installux%' LIMIT 5")
        rows = cursor.fetchall()
        if rows:
            for r in rows: print(r)
        else:
            print("No articles found with 'Installux' in designation.")

        print("\n--- Checking 'fournisseur' table ---")
        cursor.execute("SELECT * FROM fournisseur WHERE nom_client LIKE '%Installux%' OR nom_court LIKE '%Installux%'")
        rows = cursor.fetchall()
        if rows:
            for r in rows: print(r)
        else:
            print("No supplier found matching 'Installux'.")

        conn.close()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    check()
