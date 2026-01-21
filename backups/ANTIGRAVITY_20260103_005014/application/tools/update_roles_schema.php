<?php
// tools/update_roles_schema.php
require_once __DIR__ . '/../db.php';

try {
    echo "Updating Schema for Roles & Approvals...\n";
    
    // 1. Update ENUM (This is tricky in MySQL, usually requires redefining the column)
    // We will assume standard MySQL logic
    $pdo->exec("ALTER TABLE utilisateurs MODIFY COLUMN role ENUM('ADMIN', 'POSEUR', 'SECRETAIRE') DEFAULT 'POSEUR'");
    echo "✅ Role ENUM updated (ADMIN, POSEUR, SECRETAIRE).\n";

    // 2. Create Validation Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_validations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type_action VARCHAR(50) NOT NULL, -- DELETE, UPDATE
        table_concernee VARCHAR(50),
        id_enregistrement INT,
        donnees_json TEXT, -- Old/New Data
        statut VARCHAR(20) DEFAULT 'PENDING', -- PENDING, APPROVED, REJECTED
        date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_traitement DATETIME NULL,
        admin_id INT NULL,
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Table 'admin_validations' created.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Data truncated") !== false) {
        echo "⚠️ Warning: Data truncated. Some users might have lost their role (was ATELIER?).\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
