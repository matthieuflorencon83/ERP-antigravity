<?php
require_once 'db.php';

echo "<h2>Vérification et Correction de la Table Clients</h2>";

try {
    // Vérifier les colonnes actuelles
    echo "<h3>Colonnes actuelles :</h3>";
    $stmt = $pdo->query("DESCRIBE clients");
    $columns = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> - {$col['Type']}</li>";
    }
    echo "</ul>";
    
    // Vérifier si civilite existe
    $civilite_exists = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'civilite') {
            $civilite_exists = true;
            break;
        }
    }
    
    if (!$civilite_exists) {
        echo "<div style='background: #fff3cd; padding: 20px; margin: 20px 0;'>";
        echo "<h3>⚠️ Colonne 'civilite' manquante - Ajout en cours...</h3>";
        
        // Ajouter toutes les colonnes manquantes
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS civilite ENUM('M.', 'Mme', 'Société', 'Autre') DEFAULT 'M.' AFTER id");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS prenom VARCHAR(100) DEFAULT NULL AFTER nom_principal");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS code_client VARCHAR(50) UNIQUE AFTER prenom");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS email_principal VARCHAR(255) AFTER code_client");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS telephone_fixe VARCHAR(20) AFTER email_principal");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS telephone_mobile VARCHAR(20) AFTER telephone_fixe");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS adresse_postale TEXT AFTER telephone_mobile");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS code_postal VARCHAR(5) AFTER adresse_postale");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS ville VARCHAR(100) AFTER code_postal");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS pays VARCHAR(100) DEFAULT 'France' AFTER ville");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS notes TEXT AFTER pays");
        
        echo "<p style='color: green;'><strong>✅ Colonnes ajoutées avec succès !</strong></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 20px; margin: 20px 0;'>";
        echo "<h3>✅ Toutes les colonnes sont présentes !</h3>";
        echo "</div>";
    }
    
    // Afficher la structure finale
    echo "<h3>Structure finale de la table clients :</h3>";
    $stmt = $pdo->query("DESCRIBE clients");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='clients_detail.php?new=1' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>→ Créer un nouveau client</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; color: #721c24;'>";
    echo "<h3>❌ Erreur :</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
