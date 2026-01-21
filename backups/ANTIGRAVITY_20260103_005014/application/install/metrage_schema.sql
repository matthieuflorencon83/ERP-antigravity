-- MODULE METRAGE V2 - SCHEMA SQL

SET FOREIGN_KEY_CHECKS=0;

-- 1. Table : metrage_types (La Bibliothèque)
DROP TABLE IF EXISTS `metrage_types`;
CREATE TABLE `metrage_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL,
  `categorie` VARCHAR(50) NOT NULL, -- Menuiserie, Fermeture, Extérieur, Protection Solaire
  `icone` VARCHAR(50) DEFAULT 'fas fa-ruler',
  `description_technique` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table : metrage_points_controle (Le Savoir)
DROP TABLE IF EXISTS `metrage_points_controle`;
CREATE TABLE `metrage_points_controle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `metrage_type_id` INT NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `type_saisie` ENUM('mm', 'texte', 'photo', 'choix_binaire', 'liste', 'nombre') DEFAULT 'mm',
  `options_liste` JSON DEFAULT NULL, -- Pour les select
  `is_obligatoire` TINYINT(1) DEFAULT 1,
  `message_aide` TEXT, -- Le "Warning" expert
  `ordre` INT DEFAULT 0,
  FOREIGN KEY (`metrage_type_id`) REFERENCES `metrage_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table : metrage_interventions (La Mission)
DROP TABLE IF EXISTS `metrage_interventions`;
CREATE TABLE `metrage_interventions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `affaire_id` INT NOT NULL,
  `date_prevue` DATETIME DEFAULT NULL,
  `date_realisee` DATETIME DEFAULT NULL,
  `statut` ENUM('A_PLANIFIER', 'PLANIFIE', 'EN_COURS', 'VALIDE', 'A_REVOIR', 'TERMINE') DEFAULT 'A_PLANIFIER',
  `infos_acces` TEXT,
  `notes_generales` TEXT,
  `gps_lat` DECIMAL(10, 8) DEFAULT NULL,
  `gps_lon` DECIMAL(11, 8) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`affaire_id`) REFERENCES `affaires`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table : metrage_lignes (La Saisie)
DROP TABLE IF EXISTS `metrage_lignes`;
CREATE TABLE `metrage_lignes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `intervention_id` INT NOT NULL,
  `metrage_type_id` INT NOT NULL,
  `localisation` VARCHAR(100) DEFAULT NULL, -- Ex: Chambre 1
  `donnees_json` JSON DEFAULT NULL, -- Stockage Key-Value (point_controle_id -> valeur)
  `notes_observateur` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`intervention_id`) REFERENCES `metrage_interventions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`metrage_type_id`) REFERENCES `metrage_types`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table : metrage_photos (Les Preuves)
DROP TABLE IF EXISTS `metrage_photos`;
CREATE TABLE `metrage_photos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `intervention_id` INT DEFAULT NULL, -- Photo globale
  `ligne_id` INT DEFAULT NULL, -- Photo détail
  `chemin_fichier` VARCHAR(255) NOT NULL,
  `commentaire` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`intervention_id`) REFERENCES `metrage_interventions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ligne_id`) REFERENCES `metrage_lignes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
