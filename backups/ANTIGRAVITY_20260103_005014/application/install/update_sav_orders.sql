-- Migration: Lien SAV -> Commandes
SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `commandes_achats` 
ADD COLUMN `ticket_id` INT NULL AFTER `affaire_id`,
ADD INDEX `idx_ticket_id` (`ticket_id`),
ADD CONSTRAINT `fk_cmd_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `sav_tickets`(`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS=1;
