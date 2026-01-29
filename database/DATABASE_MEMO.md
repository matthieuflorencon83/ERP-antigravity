# Mémoire de la Base de Données (ERP Arts alu)

Ce fichier sert de référence vivante pour la structure de la base de données `erp_arts_alu`.
Il doit être consulté avant toute modification SQL.

## Structure Globale

- **Moteur** : MySQL (InnoDB)
- **Charset** : `utf8mb4_unicode_ci`
- **Convention** : Noms de tables et colonnes en **Français** (strictement identiques aux maquettes utilisateur).

## Liste des Tables

### 1. Entités Principales

- **`client`** : Gestion des clients (Pro/Particulier).
  - Clé : `code_cli`
- **`fournisseur`** : Gestion des fournisseurs.
  - Clé : `code_fou`
- **`article`** : Le catalogue central des produits.
  - Clé : `code_art`
  - Liaison : `unite` -> `unite(code)`
  - Liaison : `id_image` -> `image(id)`
- **`affaire`** : Les dossiers/chantiers clients.
  - Clé : `num_cde_vente`
  - Liaison : `code_cli` -> `client(code_cli)`
- **`commande`** : Les commandes d'achat fournisseurs.
  - Clé : `num_oa`
  - Liaison : `num_cde_vente` -> `affaire` (Lien Affaire)
  - Liaison : `code_fou` -> `fournisseur`

### 2. Données Techniques & Support

- **`image`** : Stockage des chemins d'accès aux images.
  - Clé : `id`
  - Colonnes : `chemin`, `date_creation`
- **`unite`** : Table de conversion des unités (ml, m2, pce...).
  - Clé : `code`
- **`couleur_finition`** : Finitions par fournisseur (RAL...).
  - Clé Composite : `(ral, code_fou)`
- **`article_fournisseur`** : Liaison "N-N" enrichie entre Article et Fournisseur (Code fournisseur, prix spécifique, conditionnement).
  - Clé Composite : `(code_art, code_fou)`
- **`prix_article`** : Historique des prix de vente/achat standards.
  - Clé : `id` (Auto-inc)
- **`GED`** : Gestion Électronique des Documents (Plans, Devis, Factures).
  - Clé : `id`
  - Liaison : `ref_affaire` -> `affaire`
  - **Spécificité** : Utilise une colonne `meta_donnee` (JSON) pour la flexibilité.

### 3. Module 2 (Besoins)

- **`besoin_ligne`** : Stockage des résultats de calepinage (Optimisation).
  - Clé : `id`
  - Colonnes : `groupe_calcul` (RAL_Matière), `config_calcul` (JSON - Plan de coupe).

## Règles d'Implémentation

1. **Hybride SQL/JSON** : Les champs fixes (prix, dates, status) sont en colonnes SQL. Les données variables (configs techniques) vont dans des champs JSON.
2. **Foreign Keys** : Toutes les relations sont strictes (InnoDB).
3. **Reset Dev** : Le script d'init commence par `DROP DATABASE` pour garantir un état propre en dév.
