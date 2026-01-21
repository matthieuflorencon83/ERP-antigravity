<?php
// diagnostic_deep_metrage.php
require_once '../db.php';

header('Content-Type: text/plain');

echo "=== DIAGNOSTIC PROFOND ANTIGRAVITY (Métrage) ===\n\n";

try {
    // 1. Check Tables Existence
    $tables = ['affaires', 'clients', 'metrage_interventions', 'utilisateurs'];
    foreach ($tables as $t) {
        $check = $pdo->query("SHOW TABLES LIKE '$t'")->fetch();
        echo "Table '$t' : " . ($check ? "OK" : "MISSING") . "\n";
    }
    echo "\n";

    // 2. Analyze 'get_affaires_sans_metrage' Query Performance
    echo "=== ANALYSE QUERY DU DROPDOWN ===\n";
    $sql = "
        SELECT 
            a.id, 
            a.nom_affaire, 
            c.nom_principal as client
        FROM affaires a
        JOIN clients c ON a.client_id = c.id
        LEFT JOIN metrage_interventions mi ON a.id = mi.affaire_id
        WHERE (a.statut = 'Devis' OR a.statut = 'Signé')
        AND mi.id IS NULL
        ORDER BY a.date_creation DESC
        LIMIT 50
    ";
    
    // EXPLAIN
    $stmt = $pdo->query("EXPLAIN " . $sql);
    $explain = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "EXPLAIN PLAN :\n";
    print_r($explain);
    echo "\n";

    // EXECUTION TIME
    $start = microtime(true);
    $stmt = $pdo->query($sql);
    $res = $stmt->fetchAll();
    $end = microtime(true);
    echo "Temps d'exécution : " . number_format(($end - $start) * 1000, 2) . " ms\n";
    echo "Nombre de résultats : " . count($res) . "\n";
    
    if (count($res) > 0) {
        echo "Exemple de résultat : " . json_encode($res[0]) . "\n";
    }
    echo "\n";

    // 3. Check for specific dangerous characters in data
    echo "=== VÉRIFICATION DONNÉES CORROMPUES ===\n";
    $bad_chars_sql = "SELECT id, nom_affaire FROM affaires WHERE nom_affaire NOT REGEXP '^[[:print:]]*$' LIMIT 5";
    $bad = $pdo->query($bad_chars_sql)->fetchAll();
    if ($bad) {
        echo "WARNING: Caractères non-imprimables détectés dans 'affaires' !\n";
        print_r($bad);
    } else {
        echo "Table 'affaires' : Encodage OK (pas de charactères binaires suspects détectés)\n";
    }

} catch (Exception $e) {
    echo "ERREUR FATALE : " . $e->getMessage();
}
