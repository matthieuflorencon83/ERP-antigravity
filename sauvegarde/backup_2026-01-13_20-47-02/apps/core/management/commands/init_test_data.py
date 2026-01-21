import random
import uuid
from decimal import Decimal
from django.core.management.base import BaseCommand
from django.db import transaction
from apps.core.models import Client, Fournisseur, Article, CoreContact, Lignecommande, Commande, Affaire, Document
from apps.core.services import generate_readable_id

class Command(BaseCommand):
    help = 'Initialise la base de données avec des données de test réalistes.'

    def handle(self, *args, **kwargs):
        self.stdout.write(self.style.WARNING('⚠️  Suppression des données existantes...'))
        
        with transaction.atomic():
            # Clean up (Child -> Parent order)
            self.stdout.write('  - Suppression Lignecommande')
            Lignecommande.objects.all().delete()
            self.stdout.write('  - Suppression Document')
            Document.objects.all().delete()
            self.stdout.write('  - Suppression Commande')
            Commande.objects.all().delete()
            self.stdout.write('  - Suppression Affaire')
            Affaire.objects.all().delete()
            
            self.stdout.write('  - Suppression Article')
            Article.objects.all().delete()
            self.stdout.write('  - Suppression CoreContact')
            CoreContact.objects.all().delete()
            self.stdout.write('  - Suppression Client')
            Client.objects.all().delete()
            self.stdout.write('  - Suppression Fournisseur')
            Fournisseur.objects.all().delete()

            self.stdout.write(self.style.SUCCESS('✅ Base nettoyée.'))

            # --- FOURNISSEURS ---
            fournisseurs_data = [
                {"nom": "POINT.P", "siret": "30691234500001", "ville": "Paris"},
                {"nom": "Saint-Gobain Vitrage", "siret": "50291134500023", "ville": "La Défense"},
                {"nom": "Maccario Vitrage", "siret": "40281230000012", "ville": "Nice"},
                {"nom": "Wurth France", "siret": "30199922200055", "ville": "Erstein"},
                {"nom": "Rexel", "siret": "44455566600011", "ville": "Paris"},
            ]
            
            created_fournisseurs = []
            for f_data in fournisseurs_data:
                f_id = generate_readable_id("FRN", f_data["nom"])
                fournisseur = Fournisseur.objects.create(
                    id=f_id,
                    nom_fournisseur=f_data["nom"],
                    siret=f_data["siret"],
                    adresse=f"{random.randint(1, 150)} Rue de l'Industrie, {f_data['ville']}",
                    code_fournisseur=f"C-{random.randint(1000,9999)}"
                )
                # Add a contact
                CoreContact.objects.create(
                    id=uuid.uuid4(),
                    fournisseur=fournisseur,
                    nom="Contact",
                    prenom=f"Service {f_data['nom'].split()[0]}",
                    email=f"contact@{f_data['nom'].lower().replace(' ', '').replace('.', '')}.com",
                    telephone=f"01{random.randint(10,99)}{random.randint(10,99)}{random.randint(10,99)}{random.randint(10,99)}",
                    type_contact="Commercial"
                )
                created_fournisseurs.append(fournisseur)
            
            self.stdout.write(self.style.SUCCESS(f'✅ {len(created_fournisseurs)} Fournisseurs créés.'))

            # --- CLIENTS ---
            clients_names = [
                "Dupont Rénovation", "SART BATIMENT", "Entreprise Martin", 
                "Lefebvre & Fils", "Baticoncept", "Renov'Azur", 
                "Sud Construction", "Maison Eco", "Travaux 2000", "Lemoine Pose"
            ]
            
            for nom in clients_names:
                c_id = generate_readable_id("CLI", nom)
                prenom = "Jean" if "Dupont" in nom else "Pierre"
                clean_name = nom.lower().replace(" ", "").replace("&", "").replace("'", "")
                email = f"contact@{clean_name}.fr"
                
                client = Client.objects.create(
                    id=c_id,
                    nom=nom,
                    prenom=prenom,
                    adresse_facturation=f"{random.randint(1, 50)} Avenue des Fleurs, 75000 Paris",
                    email_client=email,
                    telephone_client=f"06{random.randint(10,99)}{random.randint(10,99)}{random.randint(10,99)}{random.randint(10,99)}",
                    type_tiers=random.choice(['PROFESSIONNEL', 'PARTICULIER'])
                )
                # Add a contact
                CoreContact.objects.create(
                    id=uuid.uuid4(),
                    client=client,
                    nom=nom.split()[0], # Fake name from company
                    prenom="Directeur",
                    email=email,
                    telephone=client.telephone_client,
                    type_contact="Gérant"
                )

            self.stdout.write(self.style.SUCCESS(f'✅ {len(clients_names)} Clients créés.'))

            # --- ARTICLES ---
            articles_data = [
                ("Vitrage 4/16/4 Clair", "Vitrage", "U"),
                ("Vitrage 44.2/10/4 SP10", "Vitrage", "m2"),
                ("Silicone Blanc Cartouche", "Quincaillerie", "U"),
                ("Vis 5x50mm Boite 100", "Quincaillerie", "Boite"),
                ("Profilé Alu 7016 6m", "Menuiserie", "ml"),
                ("Poignée de fenêtre standard", "Quincaillerie", "U"),
                ("Paumelle renforcée", "Quincaillerie", "U"),
                ("Mousse PU expansive", "Consommable", "Bombe"),
                ("Cale de vitrage 2mm", "Consommable", "Sachet"),
                ("Joint EPDM noir", "Menuiserie", "ml"),
                ("Miroir Argent 4mm JPP", "Vitrage", "m2"),
                ("Vitrage Dépoli Acide 6mm", "Vitrage", "m2"),
                ("Porte d'entrée PVC", "Menuiserie", "U"),
                ("Baie coulissante Alu 215x240", "Menuiserie", "U"),
                ("Store Banne Motorisé 3m", "Fermeture", "U"),
                ("Volet Roulant Solaire 120x140", "Fermeture", "U"),
                ("Serrure 3 points", "Quincaillerie", "U"),
                ("Cylindre Européen 30x40", "Quincaillerie", "U"),
                ("Nettoyant Vitre Pro", "Consommable", "Bidon"),
                ("Gants de Vitrier T10", "EPI", "Paire")
            ]

            for designation, famille, unite in articles_data:
                fournisseur = random.choice(created_fournisseurs)
                base_price = Decimal(random.randint(5, 500)) if famille != "Consommable" else Decimal(random.randint(2, 20))
                
                Article.objects.create(
                    id=uuid.uuid4(),
                    designation=designation,
                    famille=famille,
                    sous_famille="Standard",
                    fournisseur=fournisseur,
                    ref_fournisseur=f"REF-{random.randint(10000, 99999)}",
                    code_article=f"ART-{random.randint(100, 999)}",
                    prix_unitaire_ht=base_price,
                    unite=unite,
                    lg="0",
                    conditionnement="Standard"
                )

            self.stdout.write(self.style.SUCCESS(f'✅ {len(articles_data)} Articles créés.'))

        # --- URLs ---
        self.stdout.write("\n" + "="*30)
        self.stdout.write("🚀 Données de test générées avec succès !")
        self.stdout.write("="*30)
        self.stdout.write("Accédez aux listes via ces URLs :")
        self.stdout.write(f"Clients      : http://127.0.0.1:8000/clients/")
        self.stdout.write(f"Fournisseurs : http://127.0.0.1:8000/fournisseurs/")
        self.stdout.write(f"Articles     : http://127.0.0.1:8000/articles/")
        self.stdout.write("="*30)
