from django.db import connection

queries = [
    "ALTER TABLE documents_document ALTER COLUMN type_document TYPE varchar(100);",
    "ALTER TABLE commandes_commande ALTER COLUMN numero_bdc TYPE varchar(100);",
    "ALTER TABLE commandes_commande ALTER COLUMN numero_arc TYPE varchar(100);",
    "ALTER TABLE commandes_commande ALTER COLUMN num_arc TYPE varchar(100);",
    "ALTER TABLE commandes_commande ALTER COLUMN num_bl TYPE varchar(100);",
    "ALTER TABLE commandes_commande ALTER COLUMN numero_bl TYPE varchar(100);",
    "ALTER TABLE commandes_commande ALTER COLUMN num_commande TYPE varchar(100);",
    "ALTER TABLE commandes_lignecommande ALTER COLUMN ral TYPE varchar(100);",
    "ALTER TABLE commandes_lignecommande ALTER COLUMN couleur_ral TYPE varchar(100);",
    "ALTER TABLE commandes_fournisseur ALTER COLUMN tva_intracommunautaire TYPE varchar(100);",
    "ALTER TABLE core_contact ALTER COLUMN type_contact TYPE varchar(100);",
    "ALTER TABLE core_contact ALTER COLUMN telephone TYPE varchar(100);",
    "ALTER TABLE commandes_client ALTER COLUMN telephone_client TYPE varchar(100);",
    "ALTER TABLE commandes_client ALTER COLUMN tva_intracommunautaire TYPE varchar(100);",
    "ALTER TABLE commandes_client ALTER COLUMN type_tiers TYPE varchar(100);",
    "ALTER TABLE commandes_affaire ALTER COLUMN statut TYPE varchar(100);",
    "ALTER TABLE commandes_commande ALTER COLUMN statut TYPE varchar(100);",
    "ALTER TABLE commandes_article ALTER COLUMN unite TYPE varchar(100);"
]

with connection.cursor() as cursor:
    for q in queries:
        try:
            print(f"Executing: {q}")
            cursor.execute(q)
        except Exception as e:
            print(f"Error executing {q}: {e}")

print("Schema update attempt finished.")
