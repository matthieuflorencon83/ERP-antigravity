<?php
// tools/restore_dashboard_postits.php
require_once __DIR__ . '/../db.php';

echo "<h2>🔧 Restauration Dashboard Memo</h2>";

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'dashboard_postits'");
    $exists = $stmt->fetch();
    
    if($exists) {
        echo "<p class='text-info'>✓ Table 'dashboard_postits' existe déjà</p>";
    } else {
        echo "<p>Création de la table...</p>";
        
        // Create table
        $pdo->exec("
            CREATE TABLE `dashboard_postits` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `content` TEXT NOT NULL,
                `color` VARCHAR(20) DEFAULT 'yellow',
                `position_x` INT DEFAULT 0,
                `position_y` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        echo "<p class='text-success'>✓ Table 'dashboard_postits' créée</p>";
    }
    
    // Check for existing data
    $count = $pdo->query("SELECT COUNT(*) FROM dashboard_postits")->fetchColumn();
    echo "<p>Mémos existants: <strong>$count</strong></p>";
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Dashboard Memo Restauré</h4>";
    echo "<p>La fonctionnalité mémo est maintenant opérationnelle.</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}
