<?php
// run_diagnostic.php - Execute le diagnostic et sauvegarde dans un fichier
require_once 'db.php';

$output = "=== DIAGNOSTIC BASE DE DONNÉES METRAGE ===\n\n";

// 1. Tables existantes
$output .= "1. TABLES EXISTANTES :\n" . str_repeat("-", 50) . "\n";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        if (stripos($table, 'metrage') !== false || stripos($table, 'intervention') !== false) {
            $output .= "✓ $table\n";
        }
    }
} catch (PDOException $e) {
    $output .= "❌ ERREUR: " . $e->getMessage() . "\n";
}

// 2. Structure metrage_interventions
$output .= "\n2. STRUCTURE metrage_interventions :\n" . str_repeat("-", 50) . "\n";
try {
    $cols = $pdo->query("DESCRIBE metrage_interventions")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        $output .= sprintf("%-25s %-20s %s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
    }
} catch (PDOException $e) {
    $output .= "❌ ERREUR: " . $e->getMessage() . "\n";
}

// 3. Données
$output .= "\n3. DONNÉES metrage_interventions :\n" . str_repeat("-", 50) . "\n";
try {
    $data = $pdo->query("SELECT * FROM metrage_interventions")->fetchAll(PDO::FETCH_ASSOC);
    $output .= "Nombre de lignes: " . count($data) . "\n";
    foreach ($data as $i => $row) {
        $output .= "Ligne $i: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (PDOException $e) {
    $output .= "❌ ERREUR: " . $e->getMessage() . "\n";
}

// 4. Structure clients
$output .= "\n4. COLONNES DE LA TABLE clients :\n" . str_repeat("-", 50) . "\n";
try {
    $cols = $pdo->query("DESCRIBE clients")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        $output .= $col['Field'] . "\n";
    }
} catch (PDOException $e) {
    $output .= "❌ ERREUR: " . $e->getMessage() . "\n";
}

// Sauvegarder
file_put_contents('diagnostic_metrage.txt', $output);
echo "Diagnostic sauvegardé dans diagnostic_metrage.txt\n";
echo $output;
