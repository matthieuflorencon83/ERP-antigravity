-- Database erp_arts_alu
DROP DATABASE IF EXISTS erp_arts_alu;
CREATE DATABASE IF NOT EXISTS erp_arts_alu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE erp_arts_alu;

-- Drop existing tables for fresh init (Dev mode)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS commande;
DROP TABLE IF EXISTS affaire;
DROP TABLE IF EXISTS article_fournisseur;
DROP TABLE IF EXISTS prix_article;
DROP TABLE IF EXISTS article;
DROP TABLE IF EXISTS image;
DROP TABLE IF EXISTS couleur_finition;
DROP TABLE IF EXISTS fournisseur;
DROP TABLE IF EXISTS client;
DROP TABLE IF EXISTS unite;
SET FOREIGN_KEY_CHECKS = 1;

-- Désactivation des vérifications pour l'ordre de création
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Table : Client
CREATE TABLE IF NOT EXISTS client (
    code_cli VARCHAR(50) PRIMARY KEY,
    nom_client VARCHAR(255),
    adresse TEXT,
    tel VARCHAR(50),
    mail VARCHAR(255),
    type VARCHAR(50)
) ENGINE=InnoDB;

-- 2. Table : Fournisseur
CREATE TABLE IF NOT EXISTS fournisseur (
    code_fou VARCHAR(50) PRIMARY KEY,
    nom_client VARCHAR(255), -- Note: "nom_client" est dans votre image pour fournisseur, je le garde tel quel
    nom_court VARCHAR(100),
    adresse TEXT,
    tel VARCHAR(50),
    mail VARCHAR(255),
    type VARCHAR(50),
    remise VARCHAR(50) -- Peut être un % ou un texte
) ENGINE=InnoDB;

-- 3. Table : Unité (unité)
CREATE TABLE IF NOT EXISTS unite (
    code VARCHAR(50) PRIMARY KEY,
    unite_1 VARCHAR(50),
    unite_2 VARCHAR(50),
    coeff_conv DECIMAL(15, 6),
    commentaire TEXT
) ENGINE=InnoDB;

-- 3.bis Table : Image
CREATE TABLE IF NOT EXISTS image (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemin VARCHAR(255) NOT NULL, -- "clemin" corrigé en chemin
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Table : Article
CREATE TABLE IF NOT EXISTS article (
    code_art VARCHAR(100) PRIMARY KEY,
    designation VARCHAR(255),
    desi_courte VARCHAR(150),
    type VARCHAR(50),
    famille VARCHAR(100),
    ssfamille VARCHAR(100),
    Fabricant VARCHAR(100),
    tenu_en_stock BOOLEAN,
    Conditionnement VARCHAR(100),
    unite VARCHAR(50),
    poid DECIMAL(10, 3),
    dimension VARCHAR(100),
    id_image INT, -- Lien vers l'image principale
    FOREIGN KEY (unite) REFERENCES unite(code),
    FOREIGN KEY (id_image) REFERENCES image(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Table : Couleur / Finition (couleur_finition)
CREATE TABLE IF NOT EXISTS couleur_finition (
    ral VARCHAR(50),
    finition VARCHAR(100),
    code_fou VARCHAR(50),
    PRIMARY KEY (ral, code_fou), -- Clé composite supposée
    FOREIGN KEY (code_fou) REFERENCES fournisseur(code_fou)
) ENGINE=InnoDB;

-- 6. Table : Prix Article (prix_article)
CREATE TABLE IF NOT EXISTS prix_article (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Ajout technique pour unicité historique
    code_art VARCHAR(100),
    prix_u_ht DECIMAL(15, 2),
    date_validite DATE, -- "date_validité"
    date_saisie DATE,
    commentaire TEXT,
    FOREIGN KEY (code_art) REFERENCES article(code_art)
) ENGINE=InnoDB;

-- 7. Table : Article Fournisseur (article_fournisseur)
CREATE TABLE IF NOT EXISTS article_fournisseur (
    code_art VARCHAR(100),
    code_fou VARCHAR(50),
    prix_u_ht DECIMAL(15, 2),
    code_art_fou VARCHAR(100),
    Multiple_cde VARCHAR(50),
    code_unite_qte VARCHAR(50),
    code_unite_prix VARCHAR(50),
    PRIMARY KEY (code_art, code_fou),
    FOREIGN KEY (code_art) REFERENCES article(code_art),
    FOREIGN KEY (code_fou) REFERENCES fournisseur(code_fou)
) ENGINE=InnoDB;

-- 8. Table : Affaire
CREATE TABLE IF NOT EXISTS affaire (
    num_cde_vente VARCHAR(100) PRIMARY KEY,
    code_cli VARCHAR(50),
    Statut VARCHAR(50),
    date_creation DATE,
    date_cde DATE,
    date_pose DATE,
    date_fin DATE,
    montant_ht DECIMAL(15, 2),
    pr_theo DECIMAL(15, 2),
    date_liv DATE,
    FOREIGN KEY (code_cli) REFERENCES client(code_cli)
) ENGINE=InnoDB;

-- 9. Table : Commande
CREATE TABLE IF NOT EXISTS commande (
    num_oa VARCHAR(100) PRIMARY KEY,
    num_cde_fou VARCHAR(100),
    num_cde_vente VARCHAR(100),
    code_fou VARCHAR(50), -- "code fou" dans l'image
    designation VARCHAR(255),
    Statut VARCHAR(50),
    date_a_cde DATE,
    date_cde DATE,
    date_souh DATE,
    date_conf DATE,
    date_liv DATE,
    montant_ht DECIMAL(15, 2),
    commentaire TEXT,
    FOREIGN KEY (code_fou) REFERENCES fournisseur(code_fou),
    FOREIGN KEY (num_cde_vente) REFERENCES affaire(num_cde_vente)
) ENGINE=InnoDB;

-- 10. Table : GED
CREATE TABLE IF NOT EXISTS GED (
    id VARCHAR(100) PRIMARY KEY, -- UUID probable ou INT
    object_type VARCHAR(50),
    object_id VARCHAR(100),
    nom_fichier_org VARCHAR(255),
    nom_fichier_ged VARCHAR(255),
    chemin_complet TEXT,
    categorie_doc VARCHAR(100),
    tags TEXT,
    meta_donnee JSON, -- "meta donnee"
    date_ajout DATE,
    ref_affaire VARCHAR(100),
    FOREIGN KEY (ref_affaire) REFERENCES affaire(num_cde_vente)
) ENGINE=InnoDB;

-- 11. Module 2 : Besoin
CREATE TABLE IF NOT EXISTS besoin_ligne (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groupe_calcul VARCHAR(255), -- Ex: "7016_ALU"
    date_calcul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    config_calcul JSON -- Le résultat du calepinage Python
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
