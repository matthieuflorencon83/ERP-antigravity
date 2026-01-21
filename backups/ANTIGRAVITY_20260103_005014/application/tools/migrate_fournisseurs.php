<?php
require_once __DIR__ . '/../db.php';

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    try {
        // Check if column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($stmt->fetch()) {
            echo "ℹ️  La colonne '$column' existe déjà dans '$table'.\n";
            return;
        }
        
        // Add column
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "✅ Colonne '$column' ajoutée à '$table'.\n";
        
    } catch (PDOException $e) {
        echo "❌ Erreur ajout '$column': " . $e->getMessage() . "\n";
    }
}

echo "--- Démarrage Migration Fournisseurs ---\n";

// 1. Colonnes Fournisseurs
$cols = [
    'adresse_postale' => 'VARCHAR(255) NULL',
    'code_postal' => 'VARCHAR(10) NULL',
    'ville' => 'VARCHAR(100) NULL',
    'pays' => 'VARCHAR(50) DEFAULT "France"',
    'siret' => 'VARCHAR(50) NULL',
    'tva_intra' => 'VARCHAR(50) NULL',
    'condition_paiement' => 'VARCHAR(100) NULL',
    'site_web' => 'VARCHAR(255) NULL',
    'notes' => 'TEXT NULL'
];

foreach ($cols as $col => $def) {
    addColumnIfNotExists($pdo, 'fournisseurs', $col, $def);
}

// 2. Table Fournisseur Contacts
try {
    $sql = "CREATE TABLE IF NOT EXISTS fournisseur_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fournisseur_id INT NOT NULL,
        nom VARCHAR(100) NOT NULL,
        role VARCHAR(100) NULL COMMENT 'Ex: Commercial, Comptabilité',
        email VARCHAR(150) NULL,
        telephone VARCHAR(50) NULL,
        mobile VARCHAR(50) NULL,
        est_principal TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "✅ Table 'fournisseur_contacts' vérifiée/créée.\n";
} catch (PDOException $e) {
    echo "❌ Erreur Table Contacts: " . $e->getMessage() . "\n";
}

echo "--- Migration Terminée ---\n";
?>
