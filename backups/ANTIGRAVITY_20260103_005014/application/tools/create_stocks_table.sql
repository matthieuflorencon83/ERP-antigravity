-- ============================================
-- STOCKS_MOUVEMENTS - Table des entrées/sorties
-- ============================================

CREATE TABLE IF NOT EXISTS stocks_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    type_mouvement ENUM('ENTREE', 'SORTIE') NOT NULL,
    quantite DECIMAL(10,2) NOT NULL,
    date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    commentaire VARCHAR(255),
    commande_achat_id INT DEFAULT NULL,
    affaire_id INT DEFAULT NULL,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    FOREIGN KEY (commande_achat_id) REFERENCES commandes_achats(id) ON DELETE SET NULL,
    FOREIGN KEY (affaire_id) REFERENCES affaires(id) ON DELETE SET NULL,
    INDEX idx_article_date (article_id, date_mouvement),
    INDEX idx_type (type_mouvement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajouter colonnes stock à la table articles
ALTER TABLE articles ADD COLUMN IF NOT EXISTS stock_physique DECIMAL(10,2) DEFAULT 0;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS unite_stock VARCHAR(20) DEFAULT 'BARRE';
