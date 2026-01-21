<?php
// debug_metrage_load.php - Diagnostic chargement métrage existant
require_once 'db.php';

$metrage_id = $_GET['id'] ?? 1;

echo "=== DIAGNOSTIC CHARGEMENT MÉTRAGE ID=$metrage_id ===\n\n";

// 1. Vérifier données intervention
echo "1. DONNÉES INTERVENTION :\n";
echo str_repeat("-", 50) . "\n";
try {
    $stmt = $pdo->prepare("SELECT i.*, a.nom_affaire, a.adresse_chantier, c.nom_principal as client_nom 
        FROM metrage_interventions i 
        LEFT JOIN affaires a ON i.affaire_id = a.id 
        LEFT JOIN clients c ON a.client_id = c.id 
        WHERE i.id = ?");
    $stmt->execute([$metrage_id]);
    $intervention = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($intervention) {
        echo "✓ Intervention trouvée\n";
        echo "affaire_id: " . ($intervention['affaire_id'] ?? 'NULL') . "\n";
        echo "nom_affaire: " . ($intervention['nom_affaire'] ?? 'NULL') . "\n";
        echo "client_nom: " . ($intervention['client_nom'] ?? 'NULL') . "\n";
        echo "statut: " . $intervention['statut'] . "\n";
    } else {
        echo "❌ Aucune intervention trouvée pour id=$metrage_id\n";
    }
} catch (PDOException $e) {
    echo "❌ ERREUR SQL: " . $e->getMessage() . "\n";
}

// 2. Vérifier lignes métrage
echo "\n2. LIGNES MÉTRAGE :\n";
echo str_repeat("-", 50) . "\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM metrage_lignes WHERE intervention_id = ?");
    $stmt->execute([$metrage_id]);
    $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Nombre de lignes: " . count($lignes) . "\n";
    foreach ($lignes as $i => $ligne) {
        echo "Ligne $i: type_id={$ligne['metrage_type_id']}, localisation={$ligne['localisation']}\n";
    }
} catch (PDOException $e) {
    echo "❌ ERREUR SQL: " . $e->getMessage() . "\n";
}

// 3. Vérifier types disponibles
echo "\n3. TYPES DISPONIBLES :\n";
echo str_repeat("-", 50) . "\n";
try {
    $types = $pdo->query("SELECT id, nom, categorie FROM metrage_types ORDER BY categorie, nom")->fetchAll(PDO::FETCH_ASSOC);
    echo "Nombre de types: " . count($types) . "\n";
} catch (PDOException $e) {
    echo "❌ ERREUR SQL: " . $e->getMessage() . "\n";
}

// 4. Simuler injection JavaScript
echo "\n4. SIMULATION INJECTION JAVASCRIPT :\n";
echo str_repeat("-", 50) . "\n";
echo "<script>\n";
echo "const METRAGE_ID = " . json_encode($metrage_id) . ";\n";
echo "const INTERVENTION = " . json_encode($intervention ?? []) . ";\n";
echo "const LIGNES = " . json_encode($lignes ?? []) . ";\n";
echo "const TYPES = " . json_encode($types ?? []) . ";\n";
echo "console.log('Variables injectées:', {METRAGE_ID, INTERVENTION, LIGNES, TYPES});\n";
echo "</script>\n";

// 5. Vérifier structure HTML critique
echo "\n5. ÉLÉMENTS DOM CRITIQUES (à vérifier dans metrage_studio.php) :\n";
echo str_repeat("-", 50) . "\n";
$required_ids = ['assistant_messages', 'input_container', 'input_zone_wrapper', 'tree_products', 'knowledge_memos'];
foreach ($required_ids as $id) {
    echo "- #$id\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
