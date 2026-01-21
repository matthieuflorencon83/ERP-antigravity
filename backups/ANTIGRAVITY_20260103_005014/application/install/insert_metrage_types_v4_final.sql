-- Insertion types de produits pour Métrage Studio V4 (FINAL)
-- Maps 'categorie' to 'famille' to satisfy schema constraints.

INSERT INTO metrage_types (slug, nom, famille, categorie, description, actif) VALUES
-- MENUISERIE
('fenetre-pvc', 'Fenêtre PVC', 'menuiserie', 'menuiserie', 'Fenêtre standard PVC', 1),
('fenetre-alu', 'Fenêtre Aluminium', 'menuiserie', 'menuiserie', 'Fenêtre aluminium', 1),
('porte-fenetre', 'Porte-fenêtre', 'menuiserie', 'menuiserie', 'Porte-fenêtre 2 vantaux', 1),
('baie-coulissante', 'Baie coulissante', 'menuiserie', 'menuiserie', 'Baie vitrée coulissante', 1),

-- GARAGE
('porte-garage-sectionnelle', 'Porte Garage Sectionnelle', 'garage', 'garage', 'Porte garage sectionnelle motorisée', 1),
('porte-garage-basculante', 'Porte Garage Basculante', 'garage', 'garage', 'Porte garage basculante', 1),

-- PORTAIL
('portail-coulissant', 'Portail Coulissant', 'portail', 'portail', 'Portail coulissant motorisé', 1),
('portail-battant', 'Portail Battant', 'portail', 'portail', 'Portail 2 vantaux battants', 1),

-- PERGOLA
('pergola-bioclimatique', 'Pergola Bioclimatique', 'pergola', 'pergola', 'Pergola lames orientables', 1),
('pergola-fixe', 'Pergola Fixe', 'pergola', 'pergola', 'Pergola toiture fixe', 1),

-- STORE
('store-banne', 'Store Banne', 'store', 'store', 'Store banne motorisé', 1),
('store-vertical', 'Store Vertical', 'store', 'store', 'Store screen vertical', 1),

-- VOLET
('volet-roulant', 'Volet Roulant', 'volet', 'volet', 'Volet roulant motorisé', 1),
('volet-battant', 'Volet Battant', 'volet', 'volet', 'Volet battant alu', 1)
ON DUPLICATE KEY UPDATE 
nom=VALUES(nom), famille=VALUES(famille), description=VALUES(description), actif=VALUES(actif);
