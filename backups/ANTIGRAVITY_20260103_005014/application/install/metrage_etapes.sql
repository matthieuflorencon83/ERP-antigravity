-- =========================================
-- MODULE METRAGE INTELLIGENT V3
-- Table des étapes par type de produit
-- =========================================

SET NAMES utf8mb4;

-- Suppression si existe
DROP TABLE IF EXISTS `metrage_etapes`;

CREATE TABLE `metrage_etapes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `metrage_type_id` INT DEFAULT NULL COMMENT 'NULL = étape commune à tous les produits',
  `categorie` VARCHAR(50) DEFAULT NULL COMMENT 'Alternative: filtrer par catégorie',
  `ordre` INT NOT NULL DEFAULT 0,
  `code_etape` VARCHAR(50) NOT NULL COMMENT 'Identifiant unique de l étape',
  `nom_etape` VARCHAR(100) NOT NULL,
  `message_assistant` TEXT NOT NULL COMMENT 'Ce que dit l assistant',
  `type_saisie` ENUM('texte', 'nombre', 'mm', 'liste', 'binaire', 'photo', 'multi_mm') DEFAULT 'texte',
  `options_json` JSON DEFAULT NULL COMMENT 'Options pour les listes',
  `champs_json` JSON DEFAULT NULL COMMENT 'Définition des champs à afficher',
  `schema_url` VARCHAR(255) DEFAULT NULL,
  `rappel` TEXT DEFAULT NULL COMMENT 'Warning/Rappel professionnel',
  `est_obligatoire` TINYINT(1) DEFAULT 1,
  `condition_json` JSON DEFAULT NULL COMMENT 'Condition pour afficher cette étape',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- ÉTAPES COMMUNES (toutes catégories)
-- =========================================
INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, schema_url, rappel, est_obligatoire) VALUES
(NULL, 1, 'localisation', 'Localisation', 
 'Où se trouve cet ouvrage dans le bâtiment ?', 
 'texte', NULL, 
 '💡 Soyez précis : Cuisine RDC, Chambre 1 étage, etc.', 1);

-- =========================================
-- FENÊTRE / PORTE-FENÊTRE
-- =========================================
INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, options_json, schema_url, rappel, est_obligatoire) VALUES
('FENETRE', 2, 'type_pose', 'Type de pose',
 'Quel est le <strong>type de pose</strong> prévu ?',
 'liste',
 '["Tunnel (dans l''épaisseur du mur)", "Applique intérieure", "Applique extérieure", "Feuillure", "Rénovation sur dormant existant"]',
 'schemas/pose_types.png',
 '⚠️ En rénovation, vérifiez l''état du dormant existant avec une sonde.', 1),

('FENETRE', 3, 'forme', 'Forme de l''ouvrage',
 'Quelle est la <strong>forme</strong> de l''ouvrage ?',
 'liste',
 '["Rectangle (standard)", "Cintre (arc en haut)", "Trapèze", "Triangle", "Œil de bœuf"]',
 'schemas/formes.png',
 '💡 Les formes spéciales nécessitent un gabarit carton.', 1),

('FENETRE', 4, 'dimensions_largeur', 'Largeur tableau',
 'Mesurez la <strong>LARGEUR</strong> du tableau maçonnerie.<br>Prenez <strong>3 mesures</strong> : en haut, au milieu, en bas.',
 'multi_mm',
 NULL,
 'schemas/dimensions_largeur.png',
 '⚠️ RÈGLE D''OR : Gardez toujours la plus petite des 3 mesures !', 1),

('FENETRE', 5, 'dimensions_hauteur', 'Hauteur tableau',
 'Mesurez la <strong>HAUTEUR</strong> du tableau.<br>Prenez <strong>3 mesures</strong> : à gauche, au centre, à droite.',
 'multi_mm',
 NULL,
 'schemas/dimensions_hauteur.png',
 '⚠️ Mesurez du seuil fini jusqu''au linteau. Gardez la plus petite !', 1),

('FENETRE', 6, 'equerrage', 'Équerrage (diagonales)',
 'Vérifiez l''<strong>équerrage</strong> en mesurant les 2 diagonales.',
 'multi_mm',
 NULL,
 'schemas/equerrage.png',
 '⚠️ Si différence > 5mm, signalez-le. Une équerre défectueuse impacte la pose !', 1),

('FENETRE', 7, 'profondeur_dormant', 'Profondeur dormant',
 'Mesurez la <strong>profondeur du dormant existant</strong> (si rénovation).',
 'mm',
 NULL,
 'schemas/profondeur_dormant.png',
 '💡 Cette mesure détermine le choix de l''aile de recouvrement.', 0),

('FENETRE', 8, 'seuil', 'Type de seuil',
 'Quel type de <strong>seuil</strong> souhaitez-vous ?',
 'liste',
 '["Seuil aluminium standard", "Seuil PMMA (PVC)", "Seuil bois", "Pas de seuil (appui alu)"]',
 'schemas/seuils.png',
 '💡 Pour les portes-fenêtres PMR, le seuil doit être ≤ 20mm.', 1),

('FENETRE', 9, 'vmc', 'Aération / VMC',
 'Y a-t-il besoin d''une <strong>entrée d''air VMC</strong> ?',
 'binaire',
 '["Oui", "Non"]',
 NULL,
 '⚠️ Obligatoire dans les pièces principales si VMC. Vérifiez la réglementation !', 1),

('FENETRE', 10, 'obstacles', 'Obstacles',
 'Y a-t-il des <strong>obstacles</strong> à signaler ?',
 'liste',
 '["Aucun obstacle", "Radiateur sous fenêtre", "Plinthe haute", "Meuble fixe", "Autre"]',
 NULL,
 '💡 Un obstacle peut impacter le sens d''ouverture ou les dimensions.', 0),

('FENETRE', 11, 'coloris_ext', 'Coloris extérieur',
 'Quel <strong>coloris extérieur</strong> ?',
 'liste',
 '["Blanc 9016", "Gris Anthracite 7016", "Noir 9005", "Gris Clair 7035", "Autre RAL"]',
 NULL,
 NULL, 1),

('FENETRE', 12, 'coloris_int', 'Coloris intérieur',
 'Quel <strong>coloris intérieur</strong> ?',
 'liste',
 '["Identique extérieur", "Blanc 9016", "Chêne doré", "Autre"]',
 NULL,
 '💡 Le bicoloration est possible en aluminium.', 1),

('FENETRE', 13, 'vitrage', 'Type de vitrage',
 'Quel type de <strong>vitrage</strong> ?',
 'liste',
 '["Double vitrage 4/16/4 standard", "Double vitrage acoustique", "Triple vitrage", "Vitrage sécurité", "Vitrage opaque"]',
 NULL,
 '💡 Triple vitrage recommandé pour façade Nord et zones froides.', 1),

('FENETRE', 14, 'photo_tableau', 'Photo du tableau',
 'Prenez une <strong>photo du tableau</strong> (vue intérieure).',
 'photo',
 NULL,
 'schemas/photo_exemple.png',
 '📸 La photo permet de valider les infos et d''identifier les spécificités.', 1),

('FENETRE', 15, 'notes', 'Notes complémentaires',
 'Avez-vous des <strong>remarques</strong> à ajouter ?',
 'texte',
 NULL,
 NULL,
 '💡 Signalez tout ce qui sort de l''ordinaire.', 0);

-- =========================================
-- VOLET ROULANT
-- =========================================
INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, options_json, schema_url, rappel, est_obligatoire) VALUES
('VOLET', 2, 'type_coffre', 'Type de coffre',
 'Quel type de <strong>coffre</strong> ?',
 'liste',
 '["Coffre tunnel (dans le linteau)", "Coffre rénovation (sur la fenêtre)", "Bloc-baie (intégré à la menuiserie)", "Coffre extérieur"]',
 'schemas/coffres_volet.png',
 '⚠️ Le coffre tunnel nécessite une réservation dans la maçonnerie.', 1),

('VOLET', 3, 'largeur_tablier', 'Largeur tablier',
 'Mesurez la <strong>largeur du tablier</strong> (zone à couvrir).',
 'mm',
 NULL,
 'schemas/volet_largeur.png',
 '💡 Ajoutez les débords si nécessaire.', 1),

('VOLET', 4, 'hauteur_tablier', 'Hauteur tablier',
 'Mesurez la <strong>hauteur du tablier</strong>.',
 'mm',
 NULL,
 'schemas/volet_hauteur.png',
 '💡 Du haut du coffre jusqu''au rejingot.', 1),

('VOLET', 5, 'manoeuvre', 'Type de manœuvre',
 'Quel type de <strong>manœuvre</strong> ?',
 'liste',
 '["Sangle", "Treuil / Manivelle", "Moteur électrique"]',
 NULL,
 NULL, 1),

('VOLET', 6, 'type_moteur', 'Type de moteur',
 'Quel type de <strong>motorisation</strong> ?',
 'liste',
 '["Filaire (interrupteur)", "Radio (télécommande)", "Solaire", "Connecté (domotique)"]',
 NULL,
 '💡 Vérifiez la présence d''une alimentation électrique.',
 0),

('VOLET', 7, 'coloris_volet', 'Coloris',
 'Quel <strong>coloris</strong> pour les lames ?',
 'liste',
 '["Blanc", "Gris Anthracite 7016", "Beige", "Marron", "Autre RAL"]',
 NULL,
 NULL, 1),

('VOLET', 8, 'photo_volet', 'Photo',
 'Prenez une <strong>photo</strong> de l''existant.',
 'photo',
 NULL,
 NULL,
 '📸 Photo du coffre et de la fenêtre.', 1);

-- =========================================
-- PORTE D'ENTRÉE / SERVICE
-- =========================================
INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, options_json, schema_url, rappel, est_obligatoire) VALUES
('PORTE', 2, 'type_porte', 'Type de porte',
 'Quel type de <strong>porte</strong> ?',
 'liste',
 '["Pleine (opaque)", "Semi-vitrée", "Vitrée", "Avec imposte"]',
 NULL,
 NULL, 1),

('PORTE', 3, 'sens_ouverture', 'Sens d''ouverture',
 'Quel est le <strong>sens d''ouverture</strong> ?',
 'liste',
 '["Poussant gauche (vue ext.)", "Poussant droit (vue ext.)", "Tirant gauche", "Tirant droit"]',
 'schemas/sens_ouverture.png',
 '⚠️ Toujours se placer DEHORS pour déterminer le sens !', 1),

('PORTE', 4, 'largeur_passage', 'Largeur passage',
 'Mesurez la <strong>largeur du passage</strong>.',
 'mm',
 NULL,
 NULL,
 '💡 Pour l''accessibilité PMR : minimum 900mm.', 1),

('PORTE', 5, 'hauteur_passage', 'Hauteur passage',
 'Mesurez la <strong>hauteur du passage</strong>.',
 'mm',
 NULL,
 NULL,
 NULL, 1),

('PORTE', 6, 'seuil_pmr', 'Seuil PMR',
 'Le seuil doit-il être <strong>accessible PMR</strong> ?',
 'binaire',
 '["Oui (≤20mm)", "Non"]',
 NULL,
 '⚠️ Seuil PMR obligatoire pour les ERP et recommandé pour les maisons.', 1),

('PORTE', 7, 'serrure', 'Type de serrure',
 'Quel type de <strong>serrure</strong> ?',
 'liste',
 '["3 points", "5 points", "Serrure automatique", "Serrure connectée"]',
 NULL,
 '💡 5 points recommandé pour les portes donnant sur l''extérieur.', 1),

('PORTE', 8, 'coloris_porte', 'Coloris',
 'Quel <strong>coloris</strong> ?',
 'liste',
 '["Blanc 9016", "Gris Anthracite 7016", "Noir 9005", "Chêne doré", "Autre RAL"]',
 NULL,
 NULL, 1),

('PORTE', 9, 'photo_porte', 'Photo',
 'Prenez une <strong>photo</strong> de l''existant.',
 'photo',
 NULL,
 NULL,
 '📸 Photo de face, en incluant le seuil.', 1);

-- Index pour performance
CREATE INDEX idx_metrage_etapes_categorie ON metrage_etapes(categorie);
CREATE INDEX idx_metrage_etapes_type ON metrage_etapes(metrage_type_id);
