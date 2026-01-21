-- Création de la table 'articles' pour le Module Catalogue
-- Remplace ou complète 'articles_catalogue'
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_interne VARCHAR(50) NOT NULL UNIQUE,
    designation VARCHAR(255) NOT NULL,
    famille VARCHAR(100), -- Profils Alu, Accessoires...
    sous_famille VARCHAR(100),
    
    fournisseur_prefere_id INT,
    ref_fournisseur VARCHAR(100),
    
    prix_achat_ht DECIMAL(10, 2) DEFAULT 0.00,
    unite_stock VARCHAR(20) DEFAULT 'U', -- U, M, M2, KG
    
    -- Données Techniques
    poids_kg DECIMAL(10, 3) DEFAULT 0.000,
    longueur_barre_mm INT DEFAULT 0,
    couleur_ral VARCHAR(20),
    
    image_path VARCHAR(255),
    stock_actuel DECIMAL(10, 2) DEFAULT 0.00,
    seuil_alerte DECIMAL(10, 2) DEFAULT 0.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_prefere_id) REFERENCES fournisseurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
