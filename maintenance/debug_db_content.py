
import psycopg2
from psycopg2.extras import RealDictCursor
import json

DB_CONFIG = {
    "dbname": "antigravity_db",
    "user": "postgres",
    "password": "loan221213",
    "host": "localhost",
    "port": "5432"
}

def check_latest():
    query = """
        SELECT id, type_document, ai_response, created_at 
        FROM documents_document 
        ORDER BY created_at DESC 
        LIMIT 1;
    """
    try:
        conn = psycopg2.connect(**DB_CONFIG)
        cur = conn.cursor(cursor_factory=RealDictCursor)
        cur.execute(query)
        row = cur.fetchone()
        conn.close()
        
        if row:
            print(f"Latest Doc ID: {row['id']}")
            print(f"Type: {row['type_document']}")
            print(f"AI Response (Prefix): {str(row['ai_response'])[:100]}")
            print(f"Created At: {row['created_at']}")
        else:
            print("No documents found in DB.")
            
    except Exception as e:
        print(f"DB Error: {e}")

if __name__ == "__main__":
    check_latest()
