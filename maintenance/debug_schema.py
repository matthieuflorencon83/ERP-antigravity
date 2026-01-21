
import psycopg2

DB_CONFIG = {
    "dbname": "antigravity_db",
    "user": "postgres",
    "password": "loan221213",
    "host": "localhost",
    "port": "5432"
}

def inspect():
    query = """
        SELECT column_name, is_nullable, data_type
        FROM information_schema.columns
        WHERE table_name = 'documents_document';
    """
    try:
        conn = psycopg2.connect(**DB_CONFIG)
        cur = conn.cursor()
        cur.execute(query)
        rows = cur.fetchall()
        for row in rows:
            print(row)
        conn.close()
    except Exception as e:
        print(e)

if __name__ == "__main__":
    inspect()
