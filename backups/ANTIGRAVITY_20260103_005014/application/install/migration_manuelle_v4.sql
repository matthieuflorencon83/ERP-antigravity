-- Migration manuelle V4.0 - Compatible MySQL 8.0
-- Exécution: HeidiSQL

USE antigravity;

-- Vérifier tables existantes
SELECT 'Tables métrage existantes:' AS info;
SHOW TABLES LIKE 'metrage%';

-- Compter données
SELECT 'Interventions:' AS info, COUNT(*) AS total FROM metrage_interventions;
SELECT 'Lignes:' AS info, COUNT(*) AS total FROM metrage_lignes;
SELECT 'Types:' AS info, COUNT(*) AS total FROM metrage_types;

-- Vérifier si colonnes existent déjà
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'antigravity' 
    AND TABLE_NAME = 'metrage_lignes' 
    AND COLUMN_NAME = 'largeur_mm'
);

-- Ajouter colonnes virtuelles seulement si elles n'existent pas
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE metrage_lignes 
     ADD COLUMN largeur_mm INT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(donnees_json, "$.dimensions.largeur"))) STORED,
     ADD COLUMN hauteur_mm INT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(donnees_json, "$.dimensions.hauteur"))) STORED,
     ADD COLUMN surface_m2 DECIMAL(10,2) GENERATED ALWAYS AS (
         CAST(JSON_UNQUOTE(JSON_EXTRACT(donnees_json, "$.dimensions.largeur")) AS DECIMAL) * 
         CAST(JSON_UNQUOTE(JSON_EXTRACT(donnees_json, "$.dimensions.hauteur")) AS DECIMAL) / 1000000
     ) STORED',
    'SELECT "Colonnes déjà existantes, migration ignorée" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✅ Migration V4.0 terminée !' AS resultat;
