-- Ajouter colonne technicien_id manquante
ALTER TABLE `metrage_interventions` 
ADD COLUMN `technicien_id` INT DEFAULT NULL AFTER `statut`,
ADD FOREIGN KEY (`technicien_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL;
