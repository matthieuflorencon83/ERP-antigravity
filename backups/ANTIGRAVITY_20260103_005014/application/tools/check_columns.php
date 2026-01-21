<?php
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM commandes_achats LIKE 'mode_commande'");
    $col = $stmt->fetch();
    
    if (!$col) {
        $pdo->exec("ALTER TABLE commandes_achats ADD COLUMN mode_commande ENUM('EMAIL', 'PORTAIL_WEB', 'TELEPHONE') DEFAULT 'EMAIL'");
        echo "✅ Column 'mode_commande' added.\n";
    } else {
        echo "ℹ️ Column 'mode_commande' already exists.\n";
    }

    $stmt2 = $pdo->query("SHOW COLUMNS FROM commandes_achats LIKE 'designation'");
    $col2 = $stmt2->fetch();
    
    if (!$col2) {
        $pdo->exec("ALTER TABLE commandes_achats ADD COLUMN designation VARCHAR(255) AFTER ref_interne");
        echo "✅ Column 'designation' added.\n";
    } else {
        echo "ℹ️ Column 'designation' already exists.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
