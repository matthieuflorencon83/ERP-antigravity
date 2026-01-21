<?php
require_once 'db.php';

try {
    // Add affaire_id
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS affaire_id INT DEFAULT NULL");
    
    // Add commande_id
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS commande_id INT DEFAULT NULL");

    // Add indexes
    try {
        $pdo->exec("CREATE INDEX idx_tasks_affaire ON tasks(affaire_id)");
        $pdo->exec("CREATE INDEX idx_tasks_commande ON tasks(commande_id)");
    } catch (Exception $e) {
        // Indexes might already exist, ignore
    }

    echo "Columns 'affaire_id' and 'commande_id' added successfully.<br>";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
