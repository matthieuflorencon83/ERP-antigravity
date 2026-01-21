import os
import json
import uuid
from django.core.management.base import BaseCommand
from apps.core.models import Document, Fournisseur, Commande, Lignecommande
from apps.ged.services import analyze_document, save_extracted_data

class Command(BaseCommand):
    help = 'Test End-to-End du pipeline Upload -> IA -> Base de Données'

    def add_arguments(self, parser):
        parser.add_argument('--file', type=str, help='Chemin vers un fichier PDF de test')

    def handle(self, *args, **options):
        file_path = options.get('file')
        
        self.stdout.write(self.style.WARNING("🚀 Démarrage du Test Pipeline E2E..."))

        # 1. Simulation Upload ou Lecture Fichier
        if file_path and os.path.exists(file_path):
            self.stdout.write(f"📄 Utilisation du fichier : {file_path}")
            with open(file_path, 'rb') as f:
                file_content = f.read()
                filename = os.path.basename(file_path)
            
            # Need a seekable object for analyze_document if it seeks
            # SimpleUploadedFile is good, but analyze_document expects a file-like object with .read() and .seek()
            # Let's create a dummy Document object first to pass to save_extracted_data later
            
            # Note: analyze_document takes a file object.
            # save_extracted_data takes (doc_obj, data)
            
            # We need a file-like object for analyze_document.
            import io
            file_obj = io.BytesIO(file_content)
            file_obj.name = filename 
            
            self.stdout.write("🤖 Appel de Gemini (analyze_document)...")
            extracted_data = analyze_document(file_obj)
            
            if "error" in extracted_data:
                self.stdout.write(self.style.ERROR(f"❌ Erreur IA : {extracted_data['error']}"))
                return
            
            self.stdout.write(self.style.SUCCESS("✅ Réponse IA reçue."))
            # Print minimal JSON for check
            self.stdout.write(json.dumps(extracted_data, indent=2, ensure_ascii=False)[:500] + "\n... (tronqué)")

        else:
            self.stdout.write(self.style.WARNING("⚠️  Aucun fichier fourni (--file), utilisation de données MOCK."))
            # Données Mock (Simulation Bon de Commande)
            extracted_data = {
                "type_document": "BON_COMMANDE",
                "date_document": "2026-05-20",
                "numero_document": "BC-TEST-E2E-001",
                "fournisseur": {
                    "nom": "Maccario Vitrage Test",
                    "siret": "40281230000099",
                    "email": "contact@maccario-test.com"
                },
                "references": {
                    "num_commande": "BC-2026-999"
                },
                "totaux": { "ht": 1500.00, "ttc": 1800.00 },
                "lignes": [
                    {"code": "VIT-442", "designation": "Vitrage 44.2 Test", "quantite": 10, "prix_unitaire": 100.00},
                    {"code": "SIL-BL", "designation": "Silicone Blanc Test", "quantite": 5, "prix_unitaire": 10.00}
                ]
            }
            # Mock Document object
            file_content = b"fake pdf content"
            filename = "mock_test.pdf"

        # Create a real Document object in DB
        from django.core.files.base import ContentFile
        
        doc_obj = Document.objects.create(
            id=uuid.uuid4(),
            fichier=ContentFile(file_content, name=filename),
            type_document=extracted_data.get('type_document', 'AUTRE')
        )
        # We don't really upload the file to storage here to keep it simple, 
        # but save_extracted_data modifies doc_obj

        # 2. Sauvegarde (Le coeur du test)
        self.stdout.write("💾 Appel de save_extracted_data...")
        result_obj = save_extracted_data(doc_obj, extracted_data)

        if not result_obj:
             self.stdout.write(self.style.ERROR("❌ save_extracted_data a retourné None. Échec."))
             return

        self.stdout.write(self.style.SUCCESS(f"✅ Objet créé : {result_obj} (Type: {type(result_obj).__name__})"))

        # 3. Assertions & Vérifications
        try:
            # Check Fournisseur
            fourn_name = extracted_data['fournisseur']['nom']
            fournisseur = Fournisseur.objects.get(nom_fournisseur=fourn_name)
            
            # ASSERT ID FORMAT
            if not str(fournisseur.id).startswith("FRN-"):
                self.stdout.write(self.style.ERROR(f"❌ ID Fournisseur incorrect : {fournisseur.id} (Attendu: FRN-...)"))
                raise AssertionError("Bad Supplier ID Format")
            else:
                 self.stdout.write(self.style.SUCCESS(f"✅ ID Fournisseur valide : {fournisseur.id}"))

            # Check Commande
            if extracted_data['type_document'] == "BON_COMMANDE":
                commande = Commande.objects.get(numero_bdc=extracted_data['references']['num_commande'])
                
                # Check link to Affaire "Commandes Stock" (default logic in services.py)
                if commande.affaire.nom_affaire != "Commandes Stock":
                     self.stdout.write(self.style.ERROR(f"❌ Affaire incorrecte : {commande.affaire.nom_affaire}"))
                else:
                     self.stdout.write(self.style.SUCCESS(f"✅ Commande liée à l'Affaire : {commande.affaire.nom_affaire}"))

                # Check Lignes & Article Link
                lignes_commande = Lignecommande.objects.filter(commande=commande)
                if lignes_commande.count() != len(extracted_data['lignes']):
                     self.stdout.write(self.style.ERROR(f"❌ Nombre de lignes incorrect : {lignes_commande.count()}"))
                else:
                     self.stdout.write(self.style.SUCCESS(f"✅ {lignes_commande.count()} Lignes de commande créées."))

                # Check Article -> Fournisseur link
                first_ligne = lignes_commande.first()
                article = first_ligne.article
                if article.fournisseur != fournisseur:
                     self.stdout.write(self.style.ERROR(f"❌ L'article {article.designation} n'est pas lié au bon fournisseur !"))
                else:
                     self.stdout.write(self.style.SUCCESS(f"✅ Article lié au fournisseur : {article.fournisseur.nom_fournisseur}"))

            # 4. Rapport Final
            self.stdout.write("\n" + "="*40)
            self.stdout.write(self.style.SUCCESS("✅ SUCCÈS : Données sauvegardées et validées."))
            self.stdout.write("="*40)
            
            # URLs
            base_url = "http://127.0.0.1:8000"
            if extracted_data.get('client'):
                 self.stdout.write(f"🏢 Voir le Client : {base_url}/clients/?nom={extracted_data['client']['nom']}")
            
            if extracted_data.get('fournisseur'):
                 self.stdout.write(f"🚚 Voir le Fournisseur : {base_url}/fournisseurs/?nom={extracted_data['fournisseur']['nom']}")
                 
            self.stdout.write(f"📦 Voir les Articles : {base_url}/articles/")
            
            if result_obj and isinstance(result_obj, Commande):
                  # Admin URL fallback or maybe we have a commande detail view? Not implemented yet broadly usually
                  self.stdout.write(f"🛒 Voir la Commande (Admin) : {base_url}/admin/core/commande/{result_obj.id}/change/")
            
            self.stdout.write("="*40 + "\n")

        except Exception as e:
            self.stdout.write(self.style.ERROR(f"❌ Échec des vérifications : {str(e)}"))
            import traceback
            self.stdout.write(traceback.format_exc())
