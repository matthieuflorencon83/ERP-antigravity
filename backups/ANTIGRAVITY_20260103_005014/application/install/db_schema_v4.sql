-- =====================================================
-- ANTIGRAVITY METRAGE V4.0 - SCHEMA OPTIMISÉ
-- Date: 2026-01-01
-- Architecture: JSON-First avec colonnes virtuelles
-- =====================================================

-- Table principale: Interventions métrage
CREATE TABLE IF NOT EXISTS `metrage_interventions` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `affaire_id` INT NULL COMMENT 'NULL = Métrage Libre',
  `statut` ENUM('A_PLANIFIER','PLANIFIE','EN_COURS','VALIDE','A_REVOIR','TERMINE') DEFAULT 'A_PLANIFIER',
  `date_prevue` DATETIME NULL,
  `date_realisee` DATETIME NULL,
  `technicien_id` INT NULL,
  `infos_acces` TEXT NULL COMMENT 'Code portail, instructions',
  `notes_generales` TEXT NULL,
  `gps_lat` DECIMAL(10,8) NULL,
  `gps_lon` DECIMAL(11,8) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Index pour performance
  INDEX `idx_affaire` (`affaire_id`),
  INDEX `idx_statut_date` (`statut`, `created_at`),
  INDEX `idx_technicien` (`technicien_id`),
  
  -- Foreign keys
  FOREIGN KEY (`affaire_id`) REFERENCES `affaires`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`technicien_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table lignes: Produits mesurés
CREATE TABLE IF NOT EXISTS `metrage_lignes` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `intervention_id` INT NOT NULL,
  `metrage_type_id` INT NOT NULL COMMENT 'Référence vers metrage_types',
  `localisation` VARCHAR(255) NOT NULL COMMENT 'Ex: Salon, Chambre 1',
  `ordre` INT DEFAULT 0 COMMENT 'Ordre d\'affichage',
  
  -- ===== DONNÉES JSON V3 (Source de vérité) =====
  `donnees_json` JSON NOT NULL COMMENT 'Structure V3 complète',
  
  -- ===== COLONNES VIRTUELLES (Calculées depuis JSON) =====
  -- Dimensions
  `largeur_mm` INT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(donnees_json, '$.dimensions.largeur'))) STORED,
  `hauteur_mm` INT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(donnees_json, '$.dimensions.hauteur'))) STORED,
  `surface_m2` DECIMAL(10,2) GENERATED ALWAYS AS (
    CAST(JSON_UNQUOTE(JSON_EXTRACT(donnees_json, '$.dimensions.largeur')) AS DECIMAL) * 
    CAST(JSON_UNQUOTE(JSON_EXTRACT(donnees_json, '$.dimensions.hauteur')) AS DECIMAL) / 1000000
  ) STORED,
  
  -- Métier
  `type_pose` VARCHAR(50) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(donnees_json, '$.technique.type_pose'))) STORED,
  `forme` VARCHAR(50) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(donnees_json, '$.dimensions.forme'))) STORED,
  
  -- Sécurité
  `alerte_amiante` BOOLEAN GENERATED ALWAYS AS (
    JSON_EXTRACT(donnees_json, '$.securite.diag_amiante') IS NOT NULL
  ) STORED,
  
  -- Timestamps
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Index pour recherche rapide
  INDEX `idx_intervention` (`intervention_id`),
  INDEX `idx_type` (`metrage_type_id`),
  INDEX `idx_surface` (`surface_m2`),
  INDEX `idx_type_pose` (`type_pose`),
  INDEX `idx_amiante` (`alerte_amiante`),
  
  -- Foreign keys
  FOREIGN KEY (`intervention_id`) REFERENCES `metrage_interventions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`metrage_type_id`) REFERENCES `metrage_types`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table types: Catalogue produits
CREATE TABLE IF NOT EXISTS `metrage_types` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Ex: porte_garage_sectionnelle',
  `nom` VARCHAR(255) NOT NULL,
  `categorie` ENUM('menuiserie','garage','portail','pergola','store','volet','veranda','moustiquaire','tav') NOT NULL,
  `description` TEXT NULL,
  `schema_svg` TEXT NULL COMMENT 'SVG du schéma pédagogique',
  `workflow_json` JSON NULL COMMENT 'Définition des étapes de saisie',
  `actif` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_categorie` (`categorie`),
  INDEX `idx_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table photos: Médias du chantier
CREATE TABLE IF NOT EXISTS `metrage_photos` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `intervention_id` INT NOT NULL,
  `ligne_id` INT NULL COMMENT 'NULL = Photo générale',
  `type_media` ENUM('photo','video','audio') DEFAULT 'photo',
  `chemin_fichier` VARCHAR(500) NOT NULL,
  `legende` VARCHAR(255) NULL,
  `gps_lat` DECIMAL(10,8) NULL,
  `gps_lon` DECIMAL(11,8) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_intervention` (`intervention_id`),
  INDEX `idx_ligne` (`ligne_id`),
  
  FOREIGN KEY (`intervention_id`) REFERENCES `metrage_interventions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ligne_id`) REFERENCES `metrage_lignes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table guides: Images pédagogiques
CREATE TABLE IF NOT EXISTS `metrage_guides_full` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `metrage_type_id` INT NOT NULL,
  `etape_slug` VARCHAR(100) NOT NULL COMMENT 'Ex: choix_forme, mesure_largeur',
  `titre` VARCHAR(255) NOT NULL,
  `image_path` VARCHAR(500) NOT NULL,
  `description` TEXT NULL,
  `ordre` INT DEFAULT 0,
  
  INDEX `idx_type_etape` (`metrage_type_id`, `etape_slug`),
  
  FOREIGN KEY (`metrage_type_id`) REFERENCES `metrage_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STRUCTURE JSON V3 (Documentation)
-- =====================================================
/*
{
  "dimensions": {
    "forme": "rectangle|trapeze|cintre",
    "largeur": 1200,
    "hauteur": 2100,
    "largeur_haut": 1000,  // Si trapèze
    "rayon": 600           // Si cintre
  },
  "technique": {
    "type_pose": "renovation|neuf|applique",
    "couleur": "blanc|ral_7016",
    "materiau": "alu|pvc",
    "vitrage": "simple|double|triple"
  },
  "accessoires": [
    {"slug": "poignee_premium", "quantite": 1},
    {"slug": "serrure_3points", "quantite": 1}
  ],
  "securite": {
    "annee_construction": 1985,
    "diag_amiante": true,
    "diag_amiante_date": "2025-12-15",
    "epi_requis": ["masque_ffp3", "combinaison"]
  },
  "logistique": {
    "poids_estime_kg": 45,
    "moyen_levage": "grue|monte_charge",
    "volume_dechets_m3": 0.5,
    "type_benne": "gravats"
  },
  "business": {
    "score_surcout": 3,  // 1-5
    "marge_estimee_pct": 35,
    "opportunites": ["store_assorti", "motorisation"]
  },
  "metadata": {
    "saisie_par": "user_id",
    "saisie_date": "2026-01-01T10:30:00Z",
    "version_schema": "3.0"
  }
}
*/

-- =====================================================
-- VUES UTILES
-- =====================================================

-- Vue: Métrages avec infos affaire
CREATE OR REPLACE VIEW `v_metrages_complets` AS
SELECT 
    i.id,
    i.statut,
    i.date_prevue,
    a.nom_affaire,
    c.nom_principal AS client_nom,
    COUNT(l.id) AS nb_produits,
    SUM(l.surface_m2) AS surface_totale_m2,
    i.created_at
FROM metrage_interventions i
LEFT JOIN affaires a ON i.affaire_id = a.id
LEFT JOIN clients c ON a.client_id = c.id
LEFT JOIN metrage_lignes l ON i.id = l.intervention_id
GROUP BY i.id;

-- Vue: Alertes Amiante
CREATE OR REPLACE VIEW `v_alertes_amiante` AS
SELECT 
    i.id AS intervention_id,
    a.nom_affaire,
    l.localisation,
    l.donnees_json->>'$.securite.annee_construction' AS annee_construction,
    l.alerte_amiante
FROM metrage_lignes l
JOIN metrage_interventions i ON l.intervention_id = i.id
LEFT JOIN affaires a ON i.affaire_id = a.id
WHERE l.alerte_amiante = TRUE;
