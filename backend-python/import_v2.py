import pandas as pd
import mysql.connector
from pydantic import BaseModel, Field, validator, ValidationError
from typing import Optional
import sys

# Database Config
DB_CONFIG = { 'user': 'root', 'password': '', 'host': '127.0.0.1', 'database': 'erp_arts_alu' }
csv_path = r"C:\Users\utopi\Desktop\Articles-fournisseur 13(Données).csv"

class ArticleRow(BaseModel):
    """
    V2.0 Data Model for CSV Row
    Enforces Strict Typing & cleaning before DB insertion.
    """
    ref: Optional[str] = Field(alias='Ref fournisseur')
    designation_raw: Optional[str] = Field(alias='Désignation')
    famille: Optional[str] = Field(alias='Famille')
    ssfamille: Optional[str] = Field(alias='Sous famille')
    poids: float = 0.0 # Field missing in CSV, default to 0
    stock_raw: Optional[str] = Field(alias='tenu en stock')
    cond: Optional[str] = Field(alias='Conditionnement')
    unite: Optional[str] = Field(alias='Unité Qte')
    
    # New Fields for Pricing
    prix_ht: Optional[float] = Field(alias='Prix/U HT')
    multiple_cde: Optional[str] = Field(alias='Multiple cde')
    code_fou: Optional[str] = Field(alias='Fournisseur')
    # fabricant: Optional[str] = Field(alias='Fabricant') # Exist in CSV

    # --- Validators (Shift-Left Security) ---

    @validator('prix_ht', pre=True)
    def parse_prix(cls, v):
        if pd.isna(v) or v == '': return 0.0
        try:
            # Handle French format "12,50" -> 12.50
            if isinstance(v, str):
                v = v.replace(',', '.').replace('€', '').strip()
            return float(v)
        except:
            return 0.0

    @validator('ref', 'designation_raw', 'famille', 'ssfamille', 'cond', 'unite', 'stock_raw', 'multiple_cde', 'code_fou', pre=True)
    def clean_strings(cls, v):
        """Convert NaN/Float to proper strings or None"""
        if pd.isna(v) or v == '':
            return None
        return str(v).strip()

    class Config:
        arbitrary_types_allowed = True


def connect_db():
    return mysql.connector.connect(**DB_CONFIG)

def import_data():
    print("🚀 ANTIGRAVITY V2.0 - Robust Import Started")
    print(f"📂 Reading: {csv_path}")

    try:
        # 1. Extraction (Pandas)
        # Try different separators similar to analysis script
        try:
            df = pd.read_csv(csv_path, sep=';', encoding='utf-8')
            if len(df.columns) < 2: raise ValueError("Sep ; failed")
        except:
            try:
                 df = pd.read_csv(csv_path, sep=',', encoding='latin-1')
                 if len(df.columns) < 2: raise ValueError("Sep , failed")
            except:
                 df = pd.read_csv(csv_path, sep='\t', encoding='latin-1')
        
        # Clean col names (strip spaces)
        df.columns = df.columns.str.strip()
        print(f"✅ Loaded {len(df)} rows.")
        
    except Exception as e:
        print(f"❌ CRITICAL: Failed to read CSV file. {e}")
        return

    conn = connect_db()
    cursor = conn.cursor()
    
    success_count = 0
    error_count = 0
    skip_count = 0

    print("🛡️  Validating & Importing rows...")

    for index, row in df.iterrows():
        try:
            # Filter: Check for 'Installux' (Case Insensitive)
            # Use 'Fabricant' column if exists, else generic search
            is_installux = False
            if 'Fabricant' in df.columns:
                fab = str(row.get('Fabricant', '')).lower()
                if 'installux' in fab: is_installux = True
            
            # Fallback global search if Fabricant column empty or logic fails
            if not is_installux:
                 if row.astype(str).str.contains('Installux', case=False).any():
                     is_installux = True
            
            if not is_installux:
                skip_count += 1
                continue

            # 2. Validation (Pydantic)
            row_dict = row.to_dict()
            try:
                article = ArticleRow(**row_dict)
            except ValidationError as ve:
                # Log detailed error but continue
                error_count += 1
                # print(f"ValidErr: {ve}") 
                continue

            # 3. Transformation / Logic
            final_code = article.ref
            if not final_code:
                error_count += 1
                continue # Cannot import without Ref
            
            final_designation = article.designation_raw or final_code
            tenu_stock = 1 if 'OUI' in str(article.stock_raw).upper() else 0

            # 4. Loading (SQL Upsert)
            
            # A. Table ARTICLE
            sql_art = """
                INSERT INTO article 
                (code_art, designation, famille, ssfamille, poid, tenu_en_stock, Conditionnement, unite, Fabricant)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 'Installux')
                ON DUPLICATE KEY UPDATE
                designation=%s, famille=%s, ssfamille=%s, poid=%s, tenu_en_stock=%s, Conditionnement=%s, unite=%s, Fabricant='Installux'
            """
            val_art = (
                final_code, final_designation, article.famille, article.ssfamille, 
                article.poids, tenu_stock, article.cond, article.unite,
                # Update
                final_designation, article.famille, article.ssfamille, 
                article.poids, tenu_stock, article.cond, article.unite
            )
            cursor.execute(sql_art, val_art)

            # B. Table ARTICLE_FOURNISSEUR (Pricing)
            
            code_fou_target = article.code_fou
            if not code_fou_target or 'installux' in str(code_fou_target).lower():
                code_fou_target = 'FOU_INSTALLUX' # Standardize key
                nom_fou = "INSTALLUX"
            else:
                nom_fou = str(code_fou_target).upper()
                code_fou_target = f"FOU_{nom_fou[:10].replace(' ', '_')}"

            # 1. Ensure Fournisseur Exists (FK Constraint Fix)
            sql_fou = """
                INSERT INTO fournisseur (code_fou, nom_client, type)
                VALUES (%s, %s, 'FOURNISSEUR')
                ON DUPLICATE KEY UPDATE nom_client=%s
            """
            cursor.execute(sql_fou, (code_fou_target, nom_fou, nom_fou))

            # 2. Upsert Article Fournisseur
            sql_price = """
                INSERT INTO article_fournisseur
                (code_art, code_fou, prix_u_ht, Multiple_cde)
                VALUES (%s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                prix_u_ht=%s, Multiple_cde=%s
            """
            cursor.execute(sql_price, (final_code, code_fou_target, article.prix_ht, article.multiple_cde, article.prix_ht, article.multiple_cde))

            success_count += 1
            if success_count % 100 == 0:
                print(".", end="", flush=True)

        except Exception as e:
            error_count += 1
            # print(f"\n❌ [DB Error] Row {index}: {e}") # Reduce noise
            continue

    conn.commit()
    cursor.close()
    conn.close()

    print(f"\n\n📊 FINAL REPORT:")
    print(f"✅ Successfully Imported: {success_count}")
    print(f"⚠️  Skipped (Not Installux): {skip_count}")
    print(f"❌ Errors (Validation/DB): {error_count}")

if __name__ == "__main__":
    import_data()
