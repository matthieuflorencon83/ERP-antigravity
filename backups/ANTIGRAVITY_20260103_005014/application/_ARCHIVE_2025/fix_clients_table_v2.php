<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Correction Table Clients</title></head><body>";
echo "<h1>Correction de la table clients</h1>";

try {
    // Désactiver les vérifications de clés étrangères temporairement
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "<h2>Étape 1 : Ajout des colonnes manquantes</h2>";
    
    $alterations = [
        "ALTER TABLE clients ADD COLUMN civilite ENUM('M.', 'Mme') DEFAULT 'M.' AFTER id",
        "ALTER TABLE clients ADD COLUMN prenom VARCHAR(100) DEFAULT NULL AFTER nom_principal",
        "ALTER TABLE clients ADD COLUMN code_client VARCHAR(50) AFTER prenom",
        "ALTER TABLE clients ADD COLUMN email_principal VARCHAR(255) AFTER code_client",
        "ALTER TABLE clients ADD COLUMN telephone_fixe VARCHAR(20) AFTER email_principal",
        "ALTER TABLE clients ADD COLUMN telephone_mobile VARCHAR(20) AFTER telephone_fixe",
        "ALTER TABLE clients ADD COLUMN adresse_postale TEXT AFTER telephone_mobile",
        "ALTER TABLE clients ADD COLUMN code_postal VARCHAR(5) AFTER adresse_postale",
        "ALTER TABLE clients ADD COLUMN ville VARCHAR(100) AFTER code_postal",
        "ALTER TABLE clients ADD COLUMN pays VARCHAR(100) DEFAULT 'France' AFTER ville",
        "ALTER TABLE clients ADD COLUMN notes TEXT AFTER pays"
    ];
    
    foreach ($alterations as $sql) {
        try {
            $pdo->exec($sql);
            // Extraire le nom de la colonne
            preg_match('/ADD COLUMN (\w+)/', $sql, $matches);
            $colonne = $matches[1] ?? 'inconnue';
            echo "<p style='color: green;'>✅ Colonne <strong>$colonne</strong> ajoutée</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                preg_match('/ADD COLUMN (\w+)/', $sql, $matches);
                $colonne = $matches[1] ?? 'inconnue';
                echo "<p style='color: orange;'>⚠️ Colonne <strong>$colonne</strong> existe déjà</p>";
            } else {
                echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Réactiver les vérifications
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<hr>";
    echo "<h2>Étape 2 : Vérification finale</h2>";
    
    $stmt = $pdo->query("DESCRIBE clients");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #333; color: white;'><th>Colonne</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2 style='color: #155724;'>✅ Correction terminée !</h2>";
    echo "<p>La table clients a été mise à jour avec succès.</p>";
    echo "<p><a href='clients_detail.php?new=1' style='display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>→ Créer un nouveau client</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h2 style='color: #721c24;'>❌ Erreur critique</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
