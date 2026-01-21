<?php
require_once 'auth.php';

echo "=== STRUCTURE BESOINS_LIGNES ===\n\n";

// Check structure
try {
    $stmt = $pdo->query("DESCRIBE besoins_lignes");
    $cols = $stmt->fetchAll();
    echo "Colonnes:\n";
    foreach ($cols as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check if there's a grouping concept
echo "=== ANALYSE GROUPEMENT ===\n";
try {
    $stmt = $pdo->query("SELECT affaire_id, zone_chantier, COUNT(*) as nb_lignes, MIN(date_creation) as date_creation 
                         FROM besoins_lignes 
                         GROUP BY affaire_id, zone_chantier 
                         ORDER BY date_creation DESC 
                         LIMIT 10");
    $groups = $stmt->fetchAll();
    echo "Groupes trouvés (par affaire + zone):\n";
    if (count($groups) > 0) {
        foreach ($groups as $g) {
            echo "  Affaire {$g['affaire_id']}, Zone: '{$g['zone_chantier']}', {$g['nb_lignes']} lignes, créé le {$g['date_creation']}\n";
        }
    } else {
        echo "  (aucun groupe)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
