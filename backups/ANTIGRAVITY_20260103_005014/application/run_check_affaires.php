<?php
// run_check_affaires.php
require_once 'db.php';

$output = "=== STRUCTURE TABLE AFFAIRES ===\n\n";

try {
    $cols = $pdo->query("DESCRIBE affaires")->fetchAll(PDO::FETCH_ASSOC);
    
    $output .= "Colonnes existantes :\n";
    $output .= str_repeat("-", 50) . "\n";
    foreach ($cols as $col) {
        $output .= sprintf("%-25s %-20s\n", $col['Field'], $col['Type']);
    }
    
    $has_adresse = false;
    foreach ($cols as $col) {
        if ($col['Field'] === 'adresse_chantier') {
            $has_adresse = true;
            break;
        }
    }
    
    $output .= "\n";
    if ($has_adresse) {
        $output .= "✓ Colonne 'adresse_chantier' existe\n";
    } else {
        $output .= "❌ Colonne 'adresse_chantier' MANQUANTE\n";
        $output .= "\nColonnes contenant 'adresse' ou 'lieu' :\n";
        foreach ($cols as $col) {
            if (stripos($col['Field'], 'adresse') !== false || 
                stripos($col['Field'], 'lieu') !== false ||
                stripos($col['Field'], 'chantier') !== false) {
                $output .= "  - " . $col['Field'] . "\n";
            }
        }
    }
    
} catch (PDOException $e) {
    $output .= "❌ ERREUR: " . $e->getMessage() . "\n";
}

file_put_contents('affaires_schema_check.txt', $output);
echo $output;
