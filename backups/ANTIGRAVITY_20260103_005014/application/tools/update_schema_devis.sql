-- update_schema_devis.sql
-- Tables pour la gestion des Devis Clients

CREATE TABLE IF NOT EXISTS devis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    numero_devis VARCHAR(50) NOT NULL UNIQUE, -- ex: DEV-2025-001
    nom_projet VARCHAR(100), -- Titre optionnel (ex: Véranda Sud)
    date_creation DATE DEFAULT (CURRENT_DATE),
    date_validite DATE,
    statut ENUM('Brouillon', 'Envoyé', 'Accepté', 'Refusé', 'Facturé') DEFAULT 'Brouillon',
    
    -- Totaux (Stockés pour perf, recalculés à chaque save)
    total_ht DECIMAL(10, 2) DEFAULT 0.00,
    total_tva DECIMAL(10, 2) DEFAULT 0.00,
    total_ttc DECIMAL(10, 2) DEFAULT 0.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS devis_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devis_id INT NOT NULL,
    article_id INT, -- Null si ligne libre (texte seul)
    
    designation VARCHAR(255) NOT NULL,
    description TEXT, -- Détail technique
    
    quantite DECIMAL(10, 2) DEFAULT 1.00,
    unite VARCHAR(20) DEFAULT 'U', -- U, m, m2
    prix_unitaire_ht DECIMAL(10, 2) DEFAULT 0.00,
    remise_pourcentage DECIMAL(5, 2) DEFAULT 0.00,
    
    total_ligne_ht DECIMAL(10, 2) DEFAULT 0.00,
    
    position INT DEFAULT 0, -- Ordre d'affichage
    
    FOREIGN KEY (devis_id) REFERENCES devis(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
