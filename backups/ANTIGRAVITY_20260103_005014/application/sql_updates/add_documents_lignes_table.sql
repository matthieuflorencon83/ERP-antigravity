-- Table pour stocker les lignes extraites des documents (BDC, ARC, BL)
CREATE TABLE IF NOT EXISTS commandes_documents_lignes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    doc_type ENUM('BDC', 'ARC', 'BL') NOT NULL,
    ref_fournisseur VARCHAR(100),
    designation TEXT,
    quantite DECIMAL(10,2),
    prix_unitaire DECIMAL(10,2),
    total_ligne DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_commande_type (commande_id, doc_type),
    FOREIGN KEY (commande_id) REFERENCES commandes_achats(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
