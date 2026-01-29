import os
import shutil
import mysql.connector
from pathlib import Path

# Config
SOURCE_DIR = r"C:\Antigravity\Matt Dev\liste besoins\images\installux_arcelor"
TARGET_DIR_REL = "assets/products"
TARGET_DIR_ABS = r"C:\Antigravity\Matt Dev\ERP Arts alu\frontend-web\src\assets\products"

DB_CONFIG = { 'user': 'root', 'password': '', 'host': '127.0.0.1', 'database': 'erp_arts_alu' }

def connect_db():
    return mysql.connector.connect(**DB_CONFIG)

def link_images():
    print("🚀 ANTIGRAVITY V2.0 - Image Linker Started")
    
    # 1. Prepare Target Directory
    if not os.path.exists(TARGET_DIR_ABS):
        os.makedirs(TARGET_DIR_ABS)
        print(f"📂 Created target directory: {TARGET_DIR_ABS}")
    else:
        print(f"📂 Target directory exists: {TARGET_DIR_ABS}")

    # 2. Get Articles from DB
    conn = connect_db()
    cursor = conn.cursor()
    cursor.execute("SELECT code_art FROM article")
    articles = set(row[0] for row in cursor.fetchall())
    print(f"📦 Loaded {len(articles)} articles from DB.")

    # 3. Scan & Match
    image_files = [f for f in os.listdir(SOURCE_DIR) if f.lower().endswith(('.png', '.jpg', '.jpeg'))]
    print(f"🖼️  Found {len(image_files)} images to process.")

    matches = 0
    copied = 0
    errors = 0
    
    suffixes = ["TH", "L", "N", "P", "T", "D", "G", "U", "MO", "B"]

    print("🔄 Processing...")

    for filename in image_files:
        try:
            stem = Path(filename).stem
            matched_code = None

            # Logic A: Exact
            if stem in articles:
                matched_code = stem
            else:
                # Logic B: Suffix
                for suf in suffixes:
                    if stem.endswith(suf):
                        stripped = stem[:-len(suf)]
                        if stripped in articles:
                            matched_code = stripped
                            break
            
            if matched_code:
                # Copy File
                src_path = os.path.join(SOURCE_DIR, filename)
                dst_path = os.path.join(TARGET_DIR_ABS, filename)
                shutil.copy2(src_path, dst_path)
                copied += 1
                
                # DB Update
                web_path = f"{TARGET_DIR_REL}/{filename}"
                
                # 1. Insert Image Record
                sql_img = "INSERT INTO image (chemin) VALUES (%s)"
                cursor.execute(sql_img, (web_path,))
                image_id = cursor.lastrowid
                
                # 2. Update Article
                sql_art = "UPDATE article SET id_image = %s WHERE code_art = %s"
                cursor.execute(sql_art, (image_id, matched_code))
                
                matches += 1
                if matches % 50 == 0: print(".", end="", flush=True)

        except Exception as e:
            errors += 1
            print(f"❌ Error on {filename}: {e}")

    conn.commit()
    cursor.close()
    conn.close()

    print(f"\n\n📊 FINAL REPORT:")
    print(f"✅ Matched & Linked: {matches}")
    print(f"📂 Files Copied: {copied}")
    print(f"❌ Errors: {errors}")

if __name__ == "__main__":
    link_images()
