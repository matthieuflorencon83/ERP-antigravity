<?php
require_once 'db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stock_mouvements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_id INT NOT NULL,
            user_id INT,
            quantite DECIMAL(10,2) NOT NULL,
            type_mouvement ENUM('ENTREE', 'SORTIE', 'INVENTAIRE') NOT NULL,
            date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP,
            commentaire VARCHAR(255),
            FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table 'stock_mouvements' créée avec succès.";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
