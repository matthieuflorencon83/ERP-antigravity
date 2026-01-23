
import os
import sys
import django
import glob
import json
from datetime import datetime
from dotenv import load_dotenv

# 0. Load environment variables from .env
load_dotenv()

# 1. Bootstrap Django
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from apps.ged.services import analyze_document

def scan_and_test():
    # 2. Find latest PDF in documents or media
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    search_paths = [
        os.path.join(base_dir, 'documents', '*.pdf'),
        os.path.join(base_dir, 'media', 'commandes', 'arc', '*.pdf'),
        os.path.join(base_dir, 'media', 'commandes', 'bl', '*.pdf'),
        os.path.join(base_dir, '*.pdf')
    ]
    
    latest_file = None
    latest_time = 0
    
    print("\n🔍 Scanning for documents...")
    
    for pattern in search_paths:
        files = glob.glob(pattern)
        for f in files:
            mtime = os.path.getmtime(f)
            if mtime > latest_time:
                latest_time = mtime
                latest_file = f
                
    if not latest_file:
        print("❌ Aucun fichier PDF trouvé (vérifié dans documents/, media/commandes/..., et racine)")
        return

    print(f"📄 Fichier analysé : {os.path.basename(latest_file)}")
    print(f"📂 Chemin complet : {latest_file}")
    print("\n🚀 Lancement de l'analyse IA (Gemini)...")
    
    try:
        # 3. Run Analysis
        # Note: analyze_document expects a file-like object or path?
        # Checking service signature... usually expects file object for Django ImageField but let's see.
        # If it takes a file object, we open it.
        
        with open(latest_file, 'rb') as f:
            # We assume analyze_document can handle a file object with a name attribute or we might need to wrap it
            # If analyze_document takes bytes or similar, we might need adjustment.
            # Based on previous context, it likely takes a file object from request.FILES or model FileField.
            # Let's try passing the file object.
            
            result = analyze_document(f)
            
        # 4. Formatted Report
        print("\n" + "="*70)
        print("🤖 RÉSULTAT DE L'ANALYSE")
        print("="*70)
        
        doc_type = result.get('type_document', 'INCONNU')
        print(f"\n🤖 Type détecté : {doc_type}")
        
        # DATES
        date_doc = result.get('date_document', 'Non détectée')
        liv_prev = result.get('date_livraison_prevue', 'Non détectée')
        print(f"\n📅 Dates clés :")
        print(f"   • Date Document : {date_doc}")
        print(f"   • Livraison Prévue : {liv_prev}")
        
        # RÉFÉRENCES
        refs = result.get('references', {})
        print(f"\n📋 Références :")
        print(f"   • N° Document : {result.get('numero_document', 'N/A')}")
        print(f"   • N° Commande : {refs.get('num_commande', 'N/A')}")
        print(f"   • N° ARC : {refs.get('num_arc', 'N/A')}")
        print(f"   • N° Devis : {refs.get('num_devis', 'N/A')}")
        print(f"   • N° BL : {refs.get('num_bl', 'N/A')}")
        print(f"   • N° Facture : {refs.get('num_facture', 'N/A')}")
        
        # FOURNISSEUR
        fournisseur = result.get('fournisseur', {})
        if fournisseur and fournisseur.get('nom'):
            print(f"\n🏢 Fournisseur :")
            print(f"   • Nom : {fournisseur.get('nom', 'N/A')}")
            print(f"   • SIRET : {fournisseur.get('siret', 'N/A')}")
            print(f"   • Email : {fournisseur.get('email', 'N/A')}")
        
        # CLIENT
        client = result.get('client', {})
        if client and client.get('nom'):
            print(f"\n👤 Client :")
            print(f"   • Nom : {client.get('nom', 'N/A')}")
            print(f"   • Tél : {client.get('tel', 'N/A')}")
            print(f"   • Email : {client.get('email', 'N/A')}")
            print(f"   • Adresse : {client.get('adresse', 'N/A')}")
        
        # TOTAUX
        totaux = result.get('totaux', {})
        print(f"\n💰 Totaux :")
        print(f"   • Total HT : {totaux.get('ht', 'N/A')} €")
        print(f"   • Total TTC : {totaux.get('ttc', 'N/A')} €")
        
        # LIGNES
        lignes = result.get('lignes', [])
        print(f"\n📦 Lignes détectées : {len(lignes)}")
        
        if lignes:
            print(f"\n{'='*70}")
            print("DÉTAIL DES LIGNES")
            print(f"{'='*70}")
            for i, ligne in enumerate(lignes, 1):
                print(f"\n[Ligne {i}]")
                print(f"  Code Article    : {ligne.get('code', 'N/A')}")
                print(f"  Désignation     : {ligne.get('designation', 'N/A')}")
                print(f"  Quantité        : {ligne.get('quantite', 0)}")
                print(f"  Prix Unitaire   : {ligne.get('prix_unitaire', 0)} €")
                print(f"  RAL             : {ligne.get('ral', 'N/A')}")
                print(f"  Finition        : {ligne.get('finition', 'N/A')}")
                print(f"  Conditionnement : {ligne.get('conditionnement', 'N/A')}")
                try:
                    qty = float(ligne.get('quantite', 0))
                    prix = float(ligne.get('prix_unitaire', 0) or 0)
                    total_ligne = qty * prix
                    print(f"  Total Ligne     : {total_ligne:.2f} €")
                except (ValueError, TypeError):
                    print(f"  Total Ligne     : N/A")
        
        # 5. Raw JSON
        print("\n" + "-"*70)
        print("⚠️ Raw JSON (Debug) :")
        print(json.dumps(result, indent=2, ensure_ascii=False))
        print("-"*70)

    except Exception as e:
        print(f"\n❌ Erreur lors de l'analyse : {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    import sys
    from io import StringIO
    
    # Capture stdout to both console and file
    log_file = open('test_ia_output.log', 'w', encoding='utf-8')
    
    class TeeOutput:
        def __init__(self, *files):
            self.files = files
        def write(self, data):
            for f in self.files:
                f.write(data)
                f.flush()
        def flush(self):
            for f in self.files:
                f.flush()
    
    original_stdout = sys.stdout
    sys.stdout = TeeOutput(sys.stdout, log_file)
    
    try:
        scan_and_test()
    finally:
        sys.stdout = original_stdout
        log_file.close()
        print(f"\n✅ Rapport complet sauvegardé dans: test_ia_output.log")
