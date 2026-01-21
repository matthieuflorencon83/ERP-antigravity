-- FORCE UTF-8
SET NAMES utf8mb4;

-- 1. On vide tout (si on est en phase de dev)
DROP DATABASE IF EXISTS antigravity;
CREATE DATABASE antigravity CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE antigravity;

-- 2. SCHÉMA COMPLET
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifiant VARCHAR(50) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    nom_complet VARCHAR(100),
    role ENUM('ADMIN', 'ATELIER', 'POSEUR') NOT NULL DEFAULT 'ATELIER',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE parametres_generaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle_config VARCHAR(50) NOT NULL UNIQUE,
    valeur_config TEXT,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE modeles_courriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('EMAIL', 'PDF_TEXTE', 'SMS') NOT NULL,
    code_interne VARCHAR(50) NOT NULL UNIQUE,
    nom_modele VARCHAR(100),
    sujet_email VARCHAR(255),
    corps_texte TEXT,
    variables_dispo TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fabricants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    site_web_technique VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    code_client_chez_eux VARCHAR(50),
    email_commande VARCHAR(100),
    delai_habituel_jours INT DEFAULT 0,
    jour_livraison_fixe VARCHAR(20),
    franco_port DECIMAL(10, 2),
    categorie_cout VARCHAR(50), -- EX: PROFIL_ALU, TRANSPORT
    adresse_enlevement TEXT,
    site_web_pro VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fournisseur_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    nom VARCHAR(100),
    role VARCHAR(50), -- Commercial, ADV, Compta
    email VARCHAR(100),
    telephone_fixe VARCHAR(20),
    telephone_mobile VARCHAR(20),
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE familles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sous_familles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    famille_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    FOREIGN KEY (famille_id) REFERENCES familles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE finitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_ral VARCHAR(20), -- ex: 7016
    nom_couleur VARCHAR(100),
    aspect VARCHAR(50) -- Mat, Brillant, Fine Structure
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE modeles_profils (
    id INT AUTO_INCREMENT PRIMARY KEY,
    designation_interne VARCHAR(100) NOT NULL,
    poids_metre_lineaire DECIMAL(10,3),
    inertie_ix DECIMAL(10,2),
    inertie_iy DECIMAL(10,2),
    image_coupe VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE articles_catalogue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT,
    fabricant_id INT,
    sous_famille_id INT,
    modele_id INT, -- Nullable car tout n'est pas un profil
    ref_fournisseur VARCHAR(100),
    designation_commerciale VARCHAR(255),
    tenu_en_stock BOOLEAN DEFAULT FALSE,
    type_vente ENUM('BARRE', 'METRE', 'M2', 'PIECE', 'BOITE') DEFAULT 'BARRE',
    longueur_mm INT DEFAULT 0,
    conditionnement_qte INT DEFAULT 1,
    poids_kg DECIMAL(10,3),
    prix_achat_actuel DECIMAL(10,2),
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL,
    FOREIGN KEY (fabricant_id) REFERENCES fabricants(id) ON DELETE SET NULL,
    FOREIGN KEY (sous_famille_id) REFERENCES sous_familles(id) ON DELETE SET NULL,
    FOREIGN KEY (modele_id) REFERENCES modeles_profils(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historique_prix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    prix_ht DECIMAL(10,2),
    date_changement DATE,
    commentaire VARCHAR(255),
    FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_principal VARCHAR(100) NOT NULL,
    commentaire TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE client_coordonnees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type_contact ENUM('Mobile', 'Email', 'Adresse Chantier') NOT NULL,
    libelle VARCHAR(100),
    valeur VARCHAR(255),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE affaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    numero_prodevis VARCHAR(50) UNIQUE,
    nom_affaire VARCHAR(100),
    designation VARCHAR(255),
    statut ENUM('Devis', 'Signé', 'Terminé') DEFAULT 'Devis',
    chemin_dossier_ged VARCHAR(255),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE besoins_chantier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    affaire_id INT NOT NULL,
    modele_profil_id INT,
    finition_id INT,
    longueur_mm INT,
    quantite INT,
    statut ENUM('A_CALCULER', 'OPTIMISE', 'COMMANDE') DEFAULT 'A_CALCULER',
    article_catalogue_id_choisi INT,
    FOREIGN KEY (affaire_id) REFERENCES affaires(id) ON DELETE CASCADE,
    FOREIGN KEY (modele_profil_id) REFERENCES modeles_profils(id) ON DELETE SET NULL,
    FOREIGN KEY (finition_id) REFERENCES finitions(id) ON DELETE SET NULL,
    FOREIGN KEY (article_catalogue_id_choisi) REFERENCES articles_catalogue(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE commandes_achats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    affaire_id INT, -- Peut être une commande de stock sans affaire
    ref_interne VARCHAR(50),
    ref_arc_fournisseur VARCHAR(50),
    date_commande DATE,

    precision_date ENUM('JOUR', 'SEMAINE') DEFAULT 'SEMAINE',
    date_livraison_reelle DATE,
    statut ENUM('Brouillon', 'Commandé', 'ARC Reçu', 'Partiel', 'Livré') DEFAULT 'Brouillon',
    lieu_livraison ENUM('ATELIER', 'CHANTIER') DEFAULT 'ATELIER',
    chemin_pdf_bdc VARCHAR(255),
    chemin_pdf_arc VARCHAR(255),
    
    -- CHAMPS IA
    source_donnees ENUM('MANUEL', 'IA_SCAN') DEFAULT 'MANUEL',
    statut_ia ENUM('A_SCANNER', 'A_VERIFIER', 'VALIDE') DEFAULT 'A_SCANNER',
    raw_text_analyse TEXT,
    
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
    FOREIGN KEY (affaire_id) REFERENCES affaires(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lignes_achat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    article_id INT,
    finition_id INT,
    qte_commandee DECIMAL(10,2), 
    qte_recue DECIMAL(10,2) DEFAULT 0,
    prix_unitaire_achat DECIMAL(10,2),
    taux_tva DECIMAL(5,2) DEFAULT 20.00,
    
    -- CHAMPS IA
    statut_verification ENUM('OK', 'MISMATCH_COULEUR', 'MISMATCH_REF', 'MISMATCH_QTE') DEFAULT 'OK',
    message_erreur_ia VARCHAR(255),
    
    FOREIGN KEY (commande_id) REFERENCES commandes_achats(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE SET NULL,
    FOREIGN KEY (finition_id) REFERENCES finitions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- 3. INJECTION DE DONNÉES TEST
INSERT INTO utilisateurs (identifiant, mot_de_passe_hash, nom_complet, role) VALUES 
('admin', '$2y$10$C.W.L... (votre hash)', 'Administrateur', 'ADMIN');

INSERT INTO clients (nom_principal) VALUES ('M. DUPONT Test');

INSERT INTO affaires (client_id, numero_prodevis, nom_affaire, statut) VALUES 
(1, 'PDV-2025-001', 'Véranda Sud', 'Signé');

INSERT INTO fournisseurs (nom, email_commande) VALUES ('SEPALUMIC', 'commande@sepa.com');

INSERT INTO commandes_achats (fournisseur_id, affaire_id, ref_interne, statut, statut_ia) VALUES 
(1, 1, 'CMD-TEST-01', DATE_ADD(CURDATE(), INTERVAL -2 DAY), 'Commandé', 'A_VERIFIER');
