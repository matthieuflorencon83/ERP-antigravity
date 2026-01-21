<?php
// tools/execute_seeding.php
require_once __DIR__ . '/../db.php';

echo "<h1>🧪 EXÉCUTION SEEDING PRODUCTION</h1>";

try {
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/seeding_production_data.sql');
    
    // Remove comments and split by semicolon
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;[\r\n]+/', $sql)
        ),
        function($stmt) {
            return !empty($stmt) && 
                   strpos($stmt, '--') !== 0 && 
                   strpos($stmt, 'TRUNCATE') === false;
        }
    );
    
    echo "<p>Statements à exécuter: " . count($statements) . "</p>";
    
    $pdo->beginTransaction();
    
    $executed = 0;
    foreach($statements as $stmt) {
        if(empty(trim($stmt))) continue;
        
        try {
            $pdo->exec($stmt);
            $executed++;
        } catch(PDOException $e) {
            echo "<div class='alert alert-warning'>";
            echo "<strong>Warning:</strong> " . $e->getMessage();
            echo "<pre>" . substr($stmt, 0, 200) . "...</pre>";
            echo "</div>";
        }
    }
    
    $pdo->commit();
    
    echo "<div class='alert alert-success'>";
    echo "<h3>✅ SEEDING TERMINÉ</h3>";
    echo "<p>$executed statements exécutés avec succès</p>";
    echo "</div>";
    
    // Verification
    echo "<h2>📊 VÉRIFICATION DES DONNÉES</h2>";
    
    // Clients
    $count = $pdo->query("SELECT COUNT(*) FROM clients WHERE nom_principal IN ('DUPONT', 'DESIGN & CO ARCHITECTES', 'MICHU')")->fetchColumn();
    echo "<p>✓ Clients créés: <strong>$count</strong> (attendu: 3)</p>";
    
    // Affaires
    $count = $pdo->query("SELECT COUNT(*) FROM affaires WHERE numero_prodevis LIKE 'PRO-2026-%'")->fetchColumn();
    echo "<p>✓ Affaires créées: <strong>$count</strong> (attendu: 3)</p>";
    
    // Interventions
    $count = $pdo->query("SELECT COUNT(*) FROM metrage_interventions WHERE affaire_id IN (SELECT id FROM affaires WHERE numero_prodevis LIKE 'PRO-2026-%')")->fetchColumn();
    echo "<p>✓ Interventions créées: <strong>$count</strong> (attendu: 3)</p>";
    
    // Lignes
    $count = $pdo->query("SELECT COUNT(*) FROM metrage_lignes WHERE intervention_id IN (SELECT id FROM metrage_interventions WHERE affaire_id IN (SELECT id FROM affaires WHERE numero_prodevis LIKE 'PRO-2026-%'))")->fetchColumn();
    echo "<p>✓ Lignes métrage créées: <strong>$count</strong> (attendu: 9)</p>";
    
    // Détails par scénario
    echo "<h3>Détails par scénario</h3>";
    
    $scenarios = $pdo->query("
        SELECT 
            c.nom_principal as client,
            a.nom_affaire,
            COUNT(ml.id) as nb_lignes,
            a.montant_estime
        FROM clients c
        JOIN affaires a ON c.id = a.client_id
        JOIN metrage_interventions mi ON a.id = mi.affaire_id
        JOIN metrage_lignes ml ON mi.id = ml.intervention_id
        WHERE a.numero_prodevis LIKE 'PRO-2026-%'
        GROUP BY c.id, a.id
        ORDER BY a.id
    ")->fetchAll();
    
    echo "<table class='table table-bordered'>";
    echo "<tr><th>Client</th><th>Affaire</th><th>Lignes</th><th>Montant</th></tr>";
    foreach($scenarios as $s) {
        echo "<tr>";
        echo "<td>{$s['client']}</td>";
        echo "<td>{$s['nom_affaire']}</td>";
        echo "<td>{$s['nb_lignes']}</td>";
        echo "<td>" . number_format($s['montant_estime'], 2, ',', ' ') . " €</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Sample JSON
    echo "<h3>Exemple JSON (Baie coulissante luxe)</h3>";
    $sample = $pdo->query("
        SELECT data_json 
        FROM metrage_lignes 
        WHERE designation LIKE '%Baie Coulissante%' 
        LIMIT 1
    ")->fetchColumn();
    
    if($sample) {
        $json = json_decode($sample, true);
        echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }
    
    echo "<div class='alert alert-info'>";
    echo "<h4>🎯 PROCHAINES ÉTAPES</h4>";
    echo "<ul>";
    echo "<li>Ouvrir le Wizard V4</li>";
    echo "<li>Charger l'affaire 'PRO-2026-003' (Mme Michu)</li>";
    echo "<li>Vérifier le rendu Canvas des formes spéciales</li>";
    echo "<li>Tester le calcul de marge sur 'PRO-2026-002' (Design & Co)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>";
    echo "<h3>❌ ERREUR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
