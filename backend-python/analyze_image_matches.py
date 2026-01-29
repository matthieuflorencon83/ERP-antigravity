import os
import mysql.connector
from pathlib import Path

# Config
IMG_DIR = r"C:\Antigravity\Matt Dev\liste besoins\images\installux_arcelor"
DB_CONFIG = { 'user': 'root', 'password': '', 'host': '127.0.0.1', 'database': 'erp_arts_alu' }

def connect_db():
    return mysql.connector.connect(**DB_CONFIG)

def analyze_matches():
    print("🔍 Analyzing Image Matches...")
    
    # 1. Get Articles
    conn = connect_db()
    cursor = conn.cursor()
    cursor.execute("SELECT code_art FROM article")
    articles = set(row[0] for row in cursor.fetchall())
    conn.close()
    
    print(f"📦 Total Articles in DB: {len(articles)}")
    
    # 2. List Images
    image_files = []
    try:
        for f in os.listdir(IMG_DIR):
            if f.lower().endswith(('.png', '.jpg', '.jpeg', '.gif', '.bmp')):
                image_files.append(f)
    except Exception as e:
        print(f"❌ Error listing directory: {e}")
        return

    print(f"🖼️  Total Image Files: {len(image_files)}")
    
    # 3. Match Logic
    exact_matches = []
    suffix_matches = []
    no_match = []
    
    suffixes_to_try = ["TH", "L", "N", "P", "T", "D", "G", "U", "MO", "B"]
    
    for filename in image_files:
        stem = Path(filename).stem # "7016TH"
        
        # Strategy A: Exact Match
        if stem in articles:
            exact_matches.append((filename, stem))
            continue
            
        # Strategy B: Common Suffixes
        matched_suffix = False
        for suf in suffixes_to_try:
            if stem.endswith(suf):
                stripped = stem[:-len(suf)]
                if stripped in articles:
                    suffix_matches.append((filename, stripped, suf))
                    matched_suffix = True
                    break
        
        if matched_suffix: continue
        
        # Strategy C: Try stripping trailing digits? No, dangerous.
        no_match.append(filename)

    # 4. Report
    print("\n📊 MATCH REPORT:")
    print(f"✅ Exact Matches: {len(exact_matches)} (e.g., {exact_matches[:3]})")
    print(f"⚠️  Suffix Matches: {len(suffix_matches)} (e.g., {suffix_matches[:3]})")
    print(f"❌ No Match: {len(no_match)}")
    
    if no_match:
        print(f"   Sample Unmatched: {no_match[:10]}")

if __name__ == "__main__":
    analyze_matches()
