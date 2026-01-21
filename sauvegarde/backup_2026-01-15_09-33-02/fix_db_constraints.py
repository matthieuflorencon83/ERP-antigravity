
import psycopg2

DB_CONFIG = {
    "dbname": "antigravity_db",
    "user": "postgres",
    "password": "loan221213",
    "host": "localhost",
    "port": "5432"
}

def fix():
    commands = [
        "ALTER TABLE documents_document ALTER COLUMN type_document DROP NOT NULL;",
        "ALTER TABLE documents_document ALTER COLUMN date_upload DROP NOT NULL;",
        "ALTER TABLE documents_document ALTER COLUMN ai_response DROP NOT NULL;",
        "ALTER TABLE documents_document ALTER COLUMN date_upload SET DEFAULT NOW();" 
    ]
    try:
        conn = psycopg2.connect(**DB_CONFIG)
        cur = conn.cursor()
        print("Relaxing constraints...")
        for cmd in commands:
            try:
                cur.execute(cmd)
                print(f"Executed: {cmd}")
            except Exception as e:
                print(f"Failed {cmd}: {e}")
                conn.rollback() 
        conn.commit()
        conn.close()
        print("Done.")
    except Exception as e:
        print(f"Connection failed: {e}")

if __name__ == "__main__":
    fix()
