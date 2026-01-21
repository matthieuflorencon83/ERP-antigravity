-- Migration: Permettre affaire_id NULL pour les métrages libres
-- Date: 2026-01-01

ALTER TABLE `metrage_interventions` 
MODIFY COLUMN `affaire_id` INT NULL;

-- Vérification
SELECT 
    COLUMN_NAME, 
    IS_NULLABLE, 
    COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'metrage_interventions' 
  AND COLUMN_NAME = 'affaire_id';
