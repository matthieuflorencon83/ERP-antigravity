<?php
// tools/migrate_clients_v4.php
require_once __DIR__ . '/../db.php';

try {
    echo "Démarrage migration Clients V4...\n";
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Table client_contacts
    $sqlContacts = "CREATE TABLE IF NOT EXISTS client_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        nom VARCHAR(255) NOT NULL,
        role VARCHAR(100) NULL,
        email VARCHAR(255) NULL,
        telephone_mobile VARCHAR(20) NULL,
        telephone_fixe VARCHAR(20) NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sqlContacts);
    echo "✅ Table 'client_contacts' créée.\n";

    // 2. Table client_adresses
    $sqlAdresses = "CREATE TABLE IF NOT EXISTS client_adresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        type_adresse VARCHAR(100) NOT NULL DEFAULT 'Chantier',
        adresse TEXT NOT NULL,
        code_postal VARCHAR(10) NULL,
        ville VARCHAR(100) NULL,
        contact_sur_place VARCHAR(255) NULL,
        telephone VARCHAR(20) NULL,
        commentaires TEXT NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sqlAdresses);
    echo "✅ Table 'client_adresses' créée.\n";

    echo "Migration terminée avec succès. 🏎️\n";

} catch (PDOException $e) {
    echo "❌ Erreur SQL : " . $e->getMessage() . "\n";
    exit(1);
}
