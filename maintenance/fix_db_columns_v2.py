
import psycopg2

DB_CONFIG = {
    "dbname": "antigravity_db",
    "user": "postgres",
    "password": "loan221213",
    "host": "localhost",
    "port": "5432"
}

def fix_columns():
    commands = [
         # Add date_document if missing
        "ALTER TABLE documents_document ADD COLUMN date_document DATE;",
    ]
    try:
        conn = psycopg2.connect(**DB_CONFIG)
        cur = conn.cursor()
        print("Adding missing columns...")
        for cmd in commands:
            try:
                cur.execute(cmd)
                print(f"Executed: {cmd}")
            except Exception as e:
                # Ignore if already exists
                print(f"info: {e}")
                conn.rollback() 
        conn.commit()
        conn.close()
        print("Done.")
    except Exception as e:
        print(f"Connection failed: {e}")

if __name__ == "__main__":
    fix_columns()
