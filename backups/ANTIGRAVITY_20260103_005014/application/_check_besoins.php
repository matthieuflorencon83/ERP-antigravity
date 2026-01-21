<?php
require_once 'auth.php';

echo "=== VERIFICATION BESOINS_CHANTIER ===\n\n";

// 1. Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'besoins_chantier'");
    $tableExists = $stmt->fetch();
    echo "Table besoins_chantier exists: " . ($tableExists ? "YES" : "NO") . "\n\n";
} catch (Exception $e) {
    echo "Error checking table: " . $e->getMessage() . "\n\n";
}

// 2. Check all tables with 'besoin' in name
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '%besoin%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables with 'besoin' in name:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// 3. Count total besoins
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM besoins_chantier");
    $count = $stmt->fetch();
    echo "Total besoins in database: " . $count['total'] . "\n\n";
} catch (Exception $e) {
    echo "Error counting: " . $e->getMessage() . "\n\n";
}

// 4. Count besoins for affaire 999
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM besoins_chantier WHERE affaire_id = ?");
    $stmt->execute([999]);
    $count = $stmt->fetch();
    echo "Besoins for affaire_id=999: " . $count['total'] . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// 5. Show structure
try {
    $stmt = $pdo->query("DESCRIBE besoins_chantier");
    $columns = $stmt->fetchAll();
    echo "Table structure:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// 6. Sample data
try {
    $stmt = $pdo->query("SELECT * FROM besoins_chantier LIMIT 5");
    $samples = $stmt->fetchAll();
    echo "Sample data (first 5 rows):\n";
    if (count($samples) > 0) {
        foreach ($samples as $row) {
            echo "  ID: {$row['id']}, Affaire: {$row['affaire_id']}, Modele: {$row['modele_profil_id']}, Qte: {$row['quantite']}\n";
        }
    } else {
        echo "  (no data)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
