-- MISE A JOUR PLANNING 2.0
SET FOREIGN_KEY_CHECKS=0;

-- 1. Table : sav_interventions (Planification SAV)
DROP TABLE IF EXISTS `sav_interventions`;
CREATE TABLE `sav_interventions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `technicien_id` INT DEFAULT NULL, -- Lien vers utilisateurs
    `date_debut` DATETIME DEFAULT NULL,
    `date_fin` DATETIME DEFAULT NULL,
    `commentaire` TEXT,
    `statut` ENUM('PLANIFIE', 'EN_COURS', 'REALISE', 'ANNULE') DEFAULT 'PLANIFIE',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `sav_tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`technicien_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Mise à jour : metrage_interventions (Ajout Technicien)
-- On vérifie si la colonne existe avant (MariaDB supporte IF NOT EXISTS dans ADD COLUMN dans les versions récentes, sinon on ignore l'erreur ou on fait une procédure, ici on y va direct)
-- ALTER TABLE `metrage_interventions` ADD COLUMN `technicien_id` INT DEFAULT NULL AFTER `affaire_id`;
-- ALTER TABLE `metrage_interventions` ADD CONSTRAINT `fk_metrage_tech` FOREIGN KEY (`technicien_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL;

-- Utilisation d'une procédure stockée simple pour éviter les erreurs si la colonne existe
DROP PROCEDURE IF EXISTS UpgradeMetrage;
DELIMITER $$
CREATE PROCEDURE UpgradeMetrage()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'metrage_interventions' AND COLUMN_NAME = 'technicien_id'
    ) THEN
        ALTER TABLE `metrage_interventions` ADD COLUMN `technicien_id` INT DEFAULT NULL AFTER `affaire_id`;
        ALTER TABLE `metrage_interventions` ADD CONSTRAINT `fk_metrage_tech` FOREIGN KEY (`technicien_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL;
    END IF;
END$$
DELIMITER ;
CALL UpgradeMetrage();
DROP PROCEDURE UpgradeMetrage;

-- 3. Mise à jour : utilisateurs (Couleur Calendrier)
DROP PROCEDURE IF EXISTS UpgradeUsers;
DELIMITER $$
CREATE PROCEDURE UpgradeUsers()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'utilisateurs' AND COLUMN_NAME = 'calendar_color'
    ) THEN
        ALTER TABLE `utilisateurs` ADD COLUMN `calendar_color` VARCHAR(20) DEFAULT '#3788d8';
    END IF;
END$$
DELIMITER ;
CALL UpgradeUsers();
DROP PROCEDURE UpgradeUsers;

SET FOREIGN_KEY_CHECKS=1;
