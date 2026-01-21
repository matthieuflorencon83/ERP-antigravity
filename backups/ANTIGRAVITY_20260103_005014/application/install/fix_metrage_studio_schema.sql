-- Fix: Add missing columns to metrage_types for Studio compatibility
-- Run this script to update the schema

-- 1. Add 'slug' column if not exists
ALTER TABLE metrage_types ADD COLUMN IF NOT EXISTS `slug` VARCHAR(50) DEFAULT NULL AFTER `nom`;

-- 2. Add 'description' column if not present (alias for description_technique)
-- Already exists as description_technique, no change needed

-- 3. Add missing columns to metrage_lignes for V3 compatibility
ALTER TABLE metrage_lignes ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) DEFAULT NULL AFTER `localisation`;
ALTER TABLE metrage_lignes ADD COLUMN IF NOT EXISTS `statut` VARCHAR(20) DEFAULT 'VALIDÉ' AFTER `description`;
ALTER TABLE metrage_lignes ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- 4. Update slugs based on nom (quick fix)
UPDATE metrage_types SET slug = LOWER(REPLACE(REPLACE(nom, ' ', '_'), '/', '_')) WHERE slug IS NULL;
