-- MODULE SAV (Service Après-Vente)

SET FOREIGN_KEY_CHECKS=0;

-- 1. TICKET SAV (Le dossier central)
CREATE TABLE IF NOT EXISTS `sav_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `numero_ticket` VARCHAR(20) NOT NULL UNIQUE, -- Format SAV-2025-12-001
    
    -- Identification FLOU (Soit un client existant, soit un prospect/inconnu)
    `client_id` INT NULL,
    `affaire_id` INT NULL, -- Complémentaire (si lié à une vieille affaire)
    `prospect_nom` VARCHAR(100) NULL, -- Si client introuvable dans la base
    `prospect_telephone` VARCHAR(20) NULL,
    `prospect_ville` VARCHAR(100) NULL, -- Utile pour le groupage géographique

    -- Qualification de la demande
    `type_panne` VARCHAR(50) NULL, -- Ex: "Moteur VR", "Serrure", "Vitrage"
    `description_initiale` TEXT NULL, -- Prise de note secrétaire
    
    -- Analyse (Rempli lors du diagnostic ou clôture)
    `origine_panne` ENUM('USURE', 'CASSE_CLIENT', 'DEFAUT_PRODUIT', 'POSE', 'INCONNU') DEFAULT 'INCONNU',
    
    -- Workflow
    `statut` ENUM('OUVERT', 'EN_DIAGNOSTIC', 'PIECE_A_COMMANDER', 'A_PLANIFIER', 'EN_COURS', 'RESOLU', 'FACTURE', 'CLASS_SANS_SUITE') DEFAULT 'OUVERT',
    `urgence` TINYINT DEFAULT 1, -- 1=Normal, 2=Urgent, 3=Critique
    
    -- Meta
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT NULL, -- Secrétaire qui a pris l'appel
    
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`affaire_id`) REFERENCES `affaires`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. LIGNES DIAGNOSTIC (Le détail technique terrain)
CREATE TABLE IF NOT EXISTS `sav_lignes_diagnostic` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    
    -- Lien Pièce (Si identifiée)
    `article_id` INT NULL,
    `designation_piece` VARCHAR(255) NOT NULL, -- "Moteur Somfy" ou saisie libre
    `quantite` DECIMAL(10,2) DEFAULT 1.00,
    
    -- Décision
    `action_requise` ENUM('REMPLACEMENT', 'REGLAGE', 'DEVIS_A_FAIRE', 'A_DEFINIR') DEFAULT 'A_DEFINIR',
    
    -- Preuve visuelle (Essentiel pour garantie fourn.)
    `photo_preuve_path` VARCHAR(255) NULL,
    
    FOREIGN KEY (`ticket_id`) REFERENCES `sav_tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. INTERVENTIONS (Les rendez-vous)
CREATE TABLE IF NOT EXISTS `sav_interventions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `technicien_id` INT NOT NULL, -- Utilisateur (Poseur/Tech)
    `date_intervention` DATETIME NOT NULL,
    `duree_prevue` INT DEFAULT 60, -- Minutes
    `statut` ENUM('PLANIFIE', 'REALISE', 'RATE', 'ANNULE') DEFAULT 'PLANIFIE',
    `rapport_intervention` TEXT NULL,
    
    FOREIGN KEY (`ticket_id`) REFERENCES `sav_tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`technicien_id`) REFERENCES `utilisateurs`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS=1;
