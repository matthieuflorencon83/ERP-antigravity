<?php
// tools/repair_commande_express.php
require_once __DIR__ . '/../db.php';

try {
    echo "Checking table commands_express...\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'commandes_express'");
    if ($stmt->rowCount() == 0) {
        echo "Table does not exist. Creating it...\n";
        $sql = "CREATE TABLE commandes_express (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type_imputation VARCHAR(20) NOT NULL,
            affaire_id INT DEFAULT NULL,
            module_type VARCHAR(50) NOT NULL,
            fournisseur_nom VARCHAR(100),
            details_json TEXT,
            created_by INT,
            created_at DATETIME,
            statut VARCHAR(20) DEFAULT 'EN_ATTENTE'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
        echo "Table created.\n";
    } else {
        echo "Table exists. Checking columns...\n";
        
        // Add module_type if missing
        try {
            $pdo->query("SELECT module_type FROM commandes_express LIMIT 1");
        } catch (PDOException $e) {
            echo "Adding column module_type...\n";
            $pdo->exec("ALTER TABLE commandes_express ADD COLUMN module_type VARCHAR(50) NOT NULL AFTER affaire_id");
        }

        // Add fournisseur_nom if missing
        try {
            $pdo->query("SELECT fournisseur_nom FROM commandes_express LIMIT 1");
        } catch (PDOException $e) {
            echo "Adding column fournisseur_nom...\n";
            $pdo->exec("ALTER TABLE commandes_express ADD COLUMN fournisseur_nom VARCHAR(100) AFTER module_type");
        }

        // Add details_json if missing
        try {
            $pdo->query("SELECT details_json FROM commandes_express LIMIT 1");
        } catch (PDOException $e) {
            echo "Adding column details_json...\n";
            $pdo->exec("ALTER TABLE commandes_express ADD COLUMN details_json TEXT AFTER fournisseur_nom");
        }

        // Add created_by if missing
        try {
            $pdo->query("SELECT created_by FROM commandes_express LIMIT 1");
        } catch (PDOException $e) {
            echo "Adding column created_by...\n";
            $pdo->exec("ALTER TABLE commandes_express ADD COLUMN created_by INT AFTER details_json");
        }

        // Add created_at if missing
        try {
            $pdo->query("SELECT created_at FROM commandes_express LIMIT 1");
        } catch (PDOException $e) {
            echo "Adding column created_at...\n";
            $pdo->exec("ALTER TABLE commandes_express ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER created_by");
        }

        // Add statut if missing
        try {
            $pdo->query("SELECT statut FROM commandes_express LIMIT 1");
        } catch (PDOException $e) {
            echo "Adding column statut...\n";
            $pdo->exec("ALTER TABLE commandes_express ADD COLUMN statut VARCHAR(20) DEFAULT 'EN_ATTENTE' AFTER created_at");
        }
    }
    
    echo "Done.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
