
import os
import django
from django.db import connection

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

def fix_db():
    with connection.cursor() as cursor:
        try:
            print("Attempting to add missing column...")
            cursor.execute('ALTER TABLE documents_document ADD COLUMN created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW();')
            print("Column added successfully.")
        except Exception as e:
            print(f"Error adding column: {e}")

if __name__ == "__main__":
    fix_db()
