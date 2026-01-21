-- Insertion types de produits pour Métrage Studio V4
-- À exécuter dans HeidiSQL

INSERT INTO metrage_types (slug, nom, categorie, description, actif) VALUES
-- MENUISERIE
('fenetre-pvc', 'Fenêtre PVC', 'menuiserie', 'Fenêtre standard PVC', TRUE),
('fenetre-alu', 'Fenêtre Aluminium', 'menuiserie', 'Fenêtre aluminium', TRUE),
('porte-fenetre', 'Porte-fenêtre', 'menuiserie', 'Porte-fenêtre 2 vantaux', TRUE),
('baie-coulissante', 'Baie coulissante', 'menuiserie', 'Baie vitrée coulissante', TRUE),

-- GARAGE
('porte-garage-sectionnelle', 'Porte Garage Sectionnelle', 'garage', 'Porte garage sectionnelle motorisée', TRUE),
('porte-garage-basculante', 'Porte Garage Basculante', 'garage', 'Porte garage basculante', TRUE),

-- PORTAIL
('portail-coulissant', 'Portail Coulissant', 'portail', 'Portail coulissant motorisé', TRUE),
('portail-battant', 'Portail Battant', 'portail', 'Portail 2 vantaux battants', TRUE),

-- PERGOLA
('pergola-bioclimatique', 'Pergola Bioclimatique', 'pergola', 'Pergola lames orientables', TRUE),
('pergola-fixe', 'Pergola Fixe', 'pergola', 'Pergola toiture fixe', TRUE),

-- STORE
('store-banne', 'Store Banne', 'store', 'Store banne motorisé', TRUE),
('store-vertical', 'Store Vertical', 'store', 'Store screen vertical', TRUE),

-- VOLET
('volet-roulant', 'Volet Roulant', 'volet', 'Volet roulant motorisé', TRUE),
('volet-battant', 'Volet Battant', 'volet', 'Volet battant alu', TRUE);
