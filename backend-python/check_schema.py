import mysql.connector

DB_CONFIG = {
    'user': 'root',
    'password': '',
    'host': '127.0.0.1',
    'database': 'erp_arts_alu'
}

def describe_article():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("DESCRIBE article")
        rows = cursor.fetchall()
        print(f"Table 'article' has {len(rows)} columns:")
        print(f"{'Field':<25} {'Type':<20} {'Null':<5} {'Key':<5}")
        print("-" * 60)
        for row in rows:
            # row: (Field, Type, Null, Key, Default, Extra)
            print(f"{row[0]:<25} {row[1]:<20} {row[2]:<5} {row[3]:<5}")
        conn.close()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    describe_article()
