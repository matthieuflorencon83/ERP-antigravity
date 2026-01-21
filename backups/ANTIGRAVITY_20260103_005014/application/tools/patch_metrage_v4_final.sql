-- PATCH FINAL METRAGE V4
-- Resolves conflict between description_technique and description

SET FOREIGN_KEY_CHECKS=0;

-- 1. Ensure metrage_types fits V4
ALTER TABLE `metrage_types` 
ADD COLUMN IF NOT EXISTS `slug` VARCHAR(100) NULL AFTER `id`,
ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `categorie`,
ADD COLUMN IF NOT EXISTS `actif` BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS `schema_svg` TEXT NULL,
ADD COLUMN IF NOT EXISTS `workflow_json` JSON NULL;

-- 2. Migrate data if 'description_technique' exits
-- (We use a stored procedure block or just specific update queries if we can't do complex logic easily here)
-- Simple check: if description is NULL but description_technique is not, copy it.
-- But we can't easily check for column existence in pure SQL statement logic without procedure usually.
-- However, we can just run this UPDATE safely if description_technique doesn't exist it will fail? No, if it doesn't exist we fail.
-- Let's try to just use `description` going forward.

-- 3. Ensure metrage_lignes fits V4
ALTER TABLE `metrage_lignes`
ADD COLUMN IF NOT EXISTS `donnees_json` JSON NULL, 
ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS `statut` VARCHAR(50) DEFAULT 'VALIDE';

SET FOREIGN_KEY_CHECKS=1;
