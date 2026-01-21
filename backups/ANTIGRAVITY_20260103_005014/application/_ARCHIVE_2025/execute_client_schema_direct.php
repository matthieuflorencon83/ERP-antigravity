<?php
/**
 * Exécution directe du schéma Client CRM
 */

// Connexion directe
$host = 'localhost';
$dbname = 'antigravity';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // 1. MODIFICATION TABLE CLIENTS
    echo "📝 Modification de la table clients...\n";
    
    $alterQueries = [
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS civilite ENUM('M.', 'Mme', 'Société', 'Autre') DEFAULT 'M.' AFTER id",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS prenom VARCHAR(100) DEFAULT NULL AFTER nom_principal",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS code_client VARCHAR(50) UNIQUE COMMENT 'Code unique client' AFTER prenom",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS email_principal VARCHAR(255) AFTER code_client",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS telephone_fixe VARCHAR(20) AFTER email_principal",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS telephone_mobile VARCHAR(20) AFTER telephone_fixe",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS adresse_postale TEXT AFTER telephone_mobile",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS code_postal VARCHAR(5) AFTER adresse_postale",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS ville VARCHAR(100) AFTER code_postal",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS pays VARCHAR(100) DEFAULT 'France' AFTER ville",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS siret VARCHAR(14) AFTER pays",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS tva_intra VARCHAR(20) AFTER siret",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS notes TEXT COMMENT 'Code porte, étage, instructions spéciales' AFTER tva_intra",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS commentaire_livraison TEXT AFTER notes",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS date_creation DATETIME DEFAULT CURRENT_TIMESTAMP AFTER commentaire_livraison",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER date_creation",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS actif BOOLEAN DEFAULT TRUE AFTER date_modification"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "  ✅ Colonne ajoutée\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "  ⚠️  Colonne déjà existante\n";
            } else {
                echo "  ❌ Erreur: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // 2. CRÉATION TABLE CLIENT_CONTACTS
    echo "\n📝 Création de la table client_contacts...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        civilite ENUM('M.', 'Mme', 'Autre') DEFAULT 'M.',
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100),
        role VARCHAR(100) COMMENT 'Conjoint, Assistant, Comptable, etc.',
        email VARCHAR(255),
        telephone_fixe VARCHAR(20),
        telephone_mobile VARCHAR(20),
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        INDEX idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  ✅ Table client_contacts créée\n";
    
    // 3. CRÉATION TABLE CLIENT_ADRESSES
    echo "\n📝 Création de la table client_adresses...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_adresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        type_adresse ENUM('Domicile', 'Travail', 'Chantier', 'Facturation', 'Livraison', 'Autre') DEFAULT 'Domicile',
        adresse TEXT NOT NULL,
        code_postal VARCHAR(5),
        ville VARCHAR(100),
        pays VARCHAR(100) DEFAULT 'France',
        contact_sur_place VARCHAR(100),
        telephone VARCHAR(20),
        instructions TEXT COMMENT 'Code porte, étage, digicode, etc.',
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        INDEX idx_client (client_id),
        INDEX idx_type (type_adresse)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  ✅ Table client_adresses créée\n";
    
    // 4. CRÉATION TABLE CLIENT_TELEPHONES
    echo "\n📝 Création de la table client_telephones...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_telephones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        type_telephone ENUM('Bureau', 'Domicile', 'Portable', 'Fax', 'Autre') DEFAULT 'Portable',
        numero VARCHAR(20) NOT NULL,
        libelle VARCHAR(100) COMMENT 'Ex: Portable Pro, Tel Chantier',
        principal BOOLEAN DEFAULT FALSE,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        INDEX idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  ✅ Table client_telephones créée\n";
    
    // 5. CRÉATION TABLE CLIENT_EMAILS
    echo "\n📝 Création de la table client_emails...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_emails (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        type_email ENUM('Principal', 'Secondaire', 'Professionnel', 'Facturation', 'Autre') DEFAULT 'Principal',
        libelle VARCHAR(100),
        principal BOOLEAN DEFAULT FALSE,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        INDEX idx_client (client_id),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  ✅ Table client_emails créée\n";
    
    // VÉRIFICATION FINALE
    echo "\n=================================\n";
    echo "✅ MIGRATION TERMINÉE AVEC SUCCÈS !\n";
    echo "=================================\n\n";
    
    echo "📊 Vérification des tables...\n\n";
    
    $tables = ['clients', 'client_contacts', 'client_adresses', 'client_telephones', 'client_emails'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "✅ Table '$table' : " . count($columns) . " colonnes\n";
        } else {
            echo "❌ Table '$table' MANQUANTE\n";
        }
    }
    
    echo "\n🎉 Module Client CRM prêt à l'emploi !\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
