<?php
require_once __DIR__ . '/../db.php';

echo "=== CRÉATION TABLE STOCKS_MOUVEMENTS ===\n\n";

$statements = [
    // Créer la table stocks_mouvements
    "CREATE TABLE IF NOT EXISTS stocks_mouvements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_id INT NOT NULL,
        type_mouvement ENUM('ENTREE', 'SORTIE') NOT NULL,
        quantite DECIMAL(10,2) NOT NULL,
        date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP,
        user_id INT,
        commentaire VARCHAR(255),
        commande_achat_id INT DEFAULT NULL,
        affaire_id INT DEFAULT NULL,
        INDEX idx_article_date (article_id, date_mouvement),
        INDEX idx_type (type_mouvement)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // Ajouter colonnes stock à articles
    "ALTER TABLE articles ADD COLUMN stock_physique DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE articles ADD COLUMN unite_stock VARCHAR(20) DEFAULT 'BARRE'",
];

foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ OK\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'already exists') || 
            str_contains($e->getMessage(), 'Duplicate column')) {
            echo "⏭️ Déjà fait\n";
        } else {
            echo "⚠️ " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Table stocks_mouvements prête.\n";
