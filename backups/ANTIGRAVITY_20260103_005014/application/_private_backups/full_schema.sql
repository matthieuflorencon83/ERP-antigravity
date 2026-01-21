-- ANTIGRAVITY V2 - FULL SCHEMA
-- Generated based on "Prompt Maître" Parts 1, 2, 3
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- BLOC 1 : SYSTÈME & ADMIN
-- ==========================================

DROP TABLE IF EXISTS utilisateurs;
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifiant VARCHAR(50) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    nom_complet VARCHAR(100),
    role ENUM('ADMIN', 'ATELIER', 'POSEUR') NOT NULL DEFAULT 'ATELIER',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS parametres_generaux;
CREATE TABLE parametres_generaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle_config VARCHAR(50) NOT NULL UNIQUE,
    valeur_config TEXT,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS modeles_courriers;
CREATE TABLE modeles_courriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('EMAIL', 'PDF_TEXTE', 'SMS') NOT NULL,
    code_interne VARCHAR(50) NOT NULL UNIQUE,
    nom_modele VARCHAR(100),
    sujet_email VARCHAR(255),
    corps_texte TEXT,
    variables_dispo TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- BLOC 2 : PARTENAIRES (FOURNISSEURS)
-- ==========================================

DROP TABLE IF EXISTS fabricants;
CREATE TABLE fabricants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    site_web_technique VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS fournisseurs;
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

DROP TABLE IF EXISTS fournisseur_contacts;
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

-- ==========================================
-- BLOC 3 : CLASSEMENT & FINITIONS
-- ==========================================

DROP TABLE IF EXISTS familles;
CREATE TABLE familles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS sous_familles;
CREATE TABLE sous_familles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    famille_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    FOREIGN KEY (famille_id) REFERENCES familles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS finitions;
CREATE TABLE finitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_ral VARCHAR(20), -- ex: 7016
    nom_couleur VARCHAR(100),
    aspect VARCHAR(50) -- Mat, Brillant, Fine Structure
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- BLOC 4 : ARTICLES & TECHNIQUE
-- ==========================================

DROP TABLE IF EXISTS modeles_profils;
CREATE TABLE modeles_profils (
    id INT AUTO_INCREMENT PRIMARY KEY,
    designation_interne VARCHAR(100) NOT NULL,
    poids_metre_lineaire DECIMAL(10,3),
    inertie_ix DECIMAL(10,2),
    inertie_iy DECIMAL(10,2),
    image_coupe VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS articles_catalogue;
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

DROP TABLE IF EXISTS historique_prix;
CREATE TABLE historique_prix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    prix_ht DECIMAL(10,2),
    date_changement DATE,
    commentaire VARCHAR(255),
    FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- BLOC 5 : CLIENTS & AFFAIRES
-- ==========================================

DROP TABLE IF EXISTS clients;
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_principal VARCHAR(100) NOT NULL,
    commentaire TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS client_coordonnees;
CREATE TABLE client_coordonnees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type_contact ENUM('Mobile', 'Email', 'Adresse Chantier') NOT NULL,
    libelle VARCHAR(100),
    valeur VARCHAR(255),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS affaires;
CREATE TABLE affaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    numero_prodevis VARCHAR(50) UNIQUE,
    nom_affaire VARCHAR(100),
    designation VARCHAR(255),
    statut ENUM('Devis', 'Signé', 'Terminé') DEFAULT 'Devis',
    chemin_dossier_ged VARCHAR(255),
    
    -- PLANNING POSE
    date_pose_debut DATE,
    date_pose_fin DATE,
    equipe_pose VARCHAR(100), -- Ex: "Equipe A", "Sous-B"
    statut_chantier ENUM('A Planifier', 'Planifié', 'En Cours', 'Terminé', 'Facturé') DEFAULT 'A Planifier',
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- BLOC 6 : BESOINS (CALEPINAGE)
-- ==========================================

DROP TABLE IF EXISTS besoins_chantier;
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

-- ==========================================
-- BLOC 7b : STOCKS (MANQUANT DANS V1)
-- ==========================================
DROP TABLE IF EXISTS stocks;
CREATE TABLE stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    finition_id INT,
    quantite DECIMAL(10,2) DEFAULT 0,
    emplacement VARCHAR(50) DEFAULT 'Atelier',
    date_derniere_maj DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE CASCADE,
    FOREIGN KEY (finition_id) REFERENCES finitions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_stock (article_id, finition_id, emplacement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- BLOC 7 : COMMANDES ACHATS (IA READY)
-- ==========================================

DROP TABLE IF EXISTS commandes_achats;
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

DROP TABLE IF EXISTS lignes_achat;
CREATE TABLE lignes_achat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    article_id INT,
    designation VARCHAR(255), -- Fallback si article_id est NULL
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


-- ==========================================
-- BLOC 8 : DEVIS CLIENTS (NEW)
-- ==========================================

DROP TABLE IF EXISTS devis_details;
DROP TABLE IF EXISTS devis;

CREATE TABLE devis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    numero_devis VARCHAR(50) NOT NULL UNIQUE, -- ex: DEV-2025-001
    nom_projet VARCHAR(100), -- Titre optionnel
    date_creation DATE DEFAULT (CURRENT_DATE),
    date_validite DATE,
    statut ENUM('Brouillon', 'Envoyé', 'Accepté', 'Refusé', 'Facturé') DEFAULT 'Brouillon',
    
    -- Totaux
    total_ht DECIMAL(10, 2) DEFAULT 0.00,
    total_tva DECIMAL(10, 2) DEFAULT 0.00,
    total_ttc DECIMAL(10, 2) DEFAULT 0.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE devis_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devis_id INT NOT NULL,
    article_id INT, -- Null si ligne libre
    
    designation VARCHAR(255) NOT NULL,
    description TEXT,
    
    quantite DECIMAL(10, 2) DEFAULT 1.00,
    unite VARCHAR(20) DEFAULT 'U',
    prix_unitaire_ht DECIMAL(10, 2) DEFAULT 0.00,
    remise_pourcentage DECIMAL(5, 2) DEFAULT 0.00,
    
    total_ligne_ht DECIMAL(10, 2) DEFAULT 0.00,
    
    position INT DEFAULT 0,
    
    FOREIGN KEY (devis_id) REFERENCES devis(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- DATA: DONNÉES DE TEST & CONFIGURATION
-- ==========================================

-- 1. Params
INSERT INTO parametres_generaux (cle_config, valeur_config, description) VALUES 
('PATH_CLIENTS', 'C:/ARTSALU/CLIENTS', 'Dossier racine des clients'),
('SMTP_HOST', 'smtp.gmail.com', 'Serveur mail');

-- 2. Familles / Sous-Familles
INSERT INTO familles (nom) VALUES ('Profils Alu'), ('Visserie'), ('Vitrage');
INSERT INTO sous_familles (famille_id, nom) VALUES (1, 'Dormant'), (1, 'Ouvrant'), (1, 'Poteau'), (2, 'Vis Inox');

-- 3. Finitions
INSERT INTO finitions (code_ral, nom_couleur, aspect) VALUES 
('7016', 'Gris Anthracite', 'Fine Structure'),
('9010', 'Blanc Pur', 'Brillant');

-- 4. Fournisseurs / Fabricants
INSERT INTO fabricants (nom) VALUES ('Sepalumic'), ('Wurth');
INSERT INTO fournisseurs (nom, email_commande, categorie_cout) VALUES 
('Sepalumic Distribution', 'commande@sepa.com', 'PROFIL_ALU'),
('Wurth France', 'adv@wurth.fr', 'QUINCAILLERIE');

-- 5. Articles (Exemple)
-- Modèle technique
INSERT INTO modeles_profils (designation_interne, poids_metre_lineaire) VALUES ('Dormant 50mm', 1.250);
-- Article de vente
INSERT INTO articles_catalogue (fournisseur_id, fabricant_id, sous_famille_id, modele_id, ref_fournisseur, designation_commerciale, type_vente, longueur_mm) 
VALUES (1, 1, 1, 1, 'D50-7016', 'Barre Dormant 50mm 7016', 'BARRE', 6500);

-- 6. Clients / Affaires
INSERT INTO clients (nom_principal) VALUES ('M. Dupont');
INSERT INTO affaires (client_id, numero_prodevis, nom_affaire) VALUES (1, 'DEV-2025-001', 'Maison Dupont - Rénovation');

-- 7. Commande Test
INSERT INTO commandes_achats (fournisseur_id, affaire_id, ref_interne, date_commande, statut)
VALUES (1, 1, 'CMD-TEST-01', CURDATE(), 'Commandé');

SET FOREIGN_KEY_CHECKS = 1;
