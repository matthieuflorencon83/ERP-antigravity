-- sql/update_v5_pro.sql
-- Migration V5 Pro : Configurateur Industriel & Commande Rapide

-- 1. ENRICHISSEMENT ARTICLES (Pour le Calepinage V2)
-- On vérifie si les colonnes existent avant de les ajouter (MariaDB style IF NOT EXISTS ou via procedure, ici simple ALTER IGNORE like logic via PHP usually better but direct SQL fine if specific)

ALTER TABLE `articles_catalogue`
ADD COLUMN `longueurs_possibles_json` JSON DEFAULT NULL COMMENT 'Ex: [4700, 6500, 7000]',
ADD COLUMN `poids_metre_lineaire` DECIMAL(10,3) DEFAULT NULL COMMENT 'kg/ml',
ADD COLUMN `inertie_lx` DECIMAL(10,2) DEFAULT NULL COMMENT 'cm4 pour calcul flèche',
ADD COLUMN `articles_lies_json` JSON DEFAULT NULL COMMENT 'Ids accessoires suggérés';

-- 2. TABLE BESOINS V2 (Plus technique que la V1)
CREATE TABLE IF NOT EXISTS `besoins_lignes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `affaire_id` INT NOT NULL,
    `zone_chantier` VARCHAR(50) DEFAULT 'Non défini' COMMENT 'Façade, Toiture...',
    
    -- Le Besoin Brut
    `designation_besoin` VARCHAR(255) NOT NULL,
    `quantite_brute` INT NOT NULL DEFAULT 1,
    `longueur_unitaire_brute_mm` INT NOT NULL,
    
    -- La Solution
    `article_catalogue_id` INT DEFAULT NULL,
    `modele_profil_id` INT DEFAULT NULL,
    `finition_id` INT DEFAULT NULL,
    
    -- Résultat Optimisation (Après moulinette)
    `longueur_barre_choisie_mm` INT DEFAULT NULL,
    `taux_chute` DECIMAL(5,2) DEFAULT NULL,
    
    `statut` ENUM('BROUILLON', 'OPTIMISE', 'VALIDE', 'COMMANDE') DEFAULT 'BROUILLON',
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`affaire_id`) REFERENCES `affaires`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABLE COMMANDE RAPIDE (Imputation Stricte)
CREATE TABLE IF NOT EXISTS `commandes_express` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- IMPUTATION
    `type_imputation` ENUM('STOCK', 'AFFAIRE') NOT NULL COMMENT 'Stock = Interne, Affaire = Client',
    `affaire_id` INT DEFAULT NULL COMMENT 'NULL si Stock',
    
    -- CONTENU FLUIDE
    `type_module` ENUM('VITRAGE', 'PLIAGE', 'PROFIL', 'PANNEAU', 'OUTILLAGE', 'LIBRE') NOT NULL,
    `fournisseur_nom` VARCHAR(100) NOT NULL,
    
    -- DATA (Tout le technique est ici en JSON pour flexibilité max)
    `details_json` JSON NOT NULL COMMENT 'Dimensions, Couleurs, Croquis Canvas encoded...',
    
    `statut` ENUM('BROUILLON', 'ENVOYEE', 'RECUE', 'ANNULEE') DEFAULT 'BROUILLON',
    
    FOREIGN KEY (`affaire_id`) REFERENCES `affaires`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

