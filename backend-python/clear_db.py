import mysql.connector

DB_CONFIG = {
    'user': 'root',
    'password': '',
    'host': '127.0.0.1',
    'database': 'erp_arts_alu'
}

def clear_data():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        # Disable FK checks temporarily to allow truncation if needed, 
        # though DELETE usually works if children don't exist.
        cursor.execute("SET FOREIGN_KEY_CHECKS = 0;")
        
        print("Clearing 'article' table...")
        cursor.execute("TRUNCATE TABLE article")
        
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1;")
        
        conn.commit()
        print("Table 'article' is now EMPTY.")
        conn.close()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    clear_data()
