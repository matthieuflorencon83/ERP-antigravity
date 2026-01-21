<?php
// debug_metrage_schema.php - Diagnostic complet de la base de données
require_once 'db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC BASE DE DONNÉES METRAGE ===\n\n";

// 1. Vérifier quelles tables existent
echo "1. TABLES EXISTANTES :\n";
echo str_repeat("-", 50) . "\n";
$tables = $pdo->query("SHOW TABLES LIKE '%metrage%' OR SHOW TABLES LIKE '%intervention%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "✓ $table\n";
}

// 2. Structure de metrage_interventions
echo "\n2. STRUCTURE DE metrage_interventions :\n";
echo str_repeat("-", 50) . "\n";
try {
    $cols = $pdo->query("DESCRIBE metrage_interventions")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo sprintf("%-20s %-15s %s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
    }
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

// 3. Données test
echo "\n3. DONNÉES DANS metrage_interventions :\n";
echo str_repeat("-", 50) . "\n";
try {
    $data = $pdo->query("SELECT * FROM metrage_interventions LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($data)) {
        echo "⚠ Table vide\n";
    } else {
        foreach ($data as $row) {
            echo "ID: {$row['id']}, affaire_id: " . ($row['affaire_id'] ?? 'NULL') . ", statut: {$row['statut']}\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

// 4. Structure de affaires
echo "\n4. STRUCTURE DE affaires :\n";
echo str_repeat("-", 50) . "\n";
try {
    $cols = $pdo->query("DESCRIBE affaires")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo sprintf("%-20s %-15s\n", $col['Field'], $col['Type']);
    }
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

// 5. Structure de clients
echo "\n5. STRUCTURE DE clients :\n";
echo str_repeat("-", 50) . "\n";
try {
    $cols = $pdo->query("DESCRIBE clients")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo sprintf("%-20s %-15s\n", $col['Field'], $col['Type']);
    }
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

// 6. Test de la requête utilisée dans metrage_studio.php
echo "\n6. TEST REQUÊTE metrage_studio.php :\n";
echo str_repeat("-", 50) . "\n";
try {
    $stmt = $pdo->prepare("SELECT i.*, a.nom_affaire, a.adresse_chantier, c.nom_principal as client_nom 
        FROM metrage_interventions i 
        LEFT JOIN affaires a ON i.affaire_id = a.id 
        LEFT JOIN clients c ON a.client_id = c.id 
        WHERE i.id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "✓ Requête OK\n";
        echo "Colonnes retournées: " . implode(", ", array_keys($result)) . "\n";
    } else {
        echo "⚠ Aucun résultat pour id=1\n";
    }
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU DIAGNOSTIC ===\n";
