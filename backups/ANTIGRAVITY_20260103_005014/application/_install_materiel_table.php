<?php
require_once 'auth.php';
require_once 'db.php';

echo "=== CRÉATION TABLE AFFAIRES_MATERIEL ===\n\n";

$sql = "
CREATE TABLE IF NOT EXISTS affaires_materiel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    affaire_id INT NOT NULL,
    designation VARCHAR(255) NOT NULL,
    quantite INT DEFAULT 1,
    unite VARCHAR(50) DEFAULT 'unité',
    statut ENUM('A_PREVOIR', 'COMMANDE', 'SUR_SITE', 'RETOURNE') DEFAULT 'A_PREVOIR',
    priorite ENUM('BASSE', 'NORMALE', 'HAUTE', 'URGENTE') DEFAULT 'NORMALE',
    commentaire TEXT,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME ON UPDATE CURRENT_TIMESTAMP,
    user_id INT,
    
    FOREIGN KEY (affaire_id) REFERENCES affaires(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    
    INDEX idx_affaire (affaire_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql);
    echo "Table 'affaires_materiel' créée (ou déjà existante) avec succès.\n";
    
    // Check structure
    $stmt = $pdo->query("DESCRIBE affaires_materiel");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Colonnes:\n";
    foreach ($cols as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }

} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
