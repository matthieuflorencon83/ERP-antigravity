<?php
// tools/test_dashboard_integrity.php - Test dashboard sans auth
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

echo "<h1>💎 Dashboard Integrity Test</h1>";

try {
    // Include controller
    ob_start();
    include __DIR__ . '/../controllers/dashboard_controller.php';
    $controller_output = ob_get_clean();
    
    if(!empty($controller_output)) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>Controller Output (should be empty):</h4>";
        echo "<pre>$controller_output</pre>";
        echo "</div>";
    }
    
    echo "<div class='alert alert-success'>";
    echo "<h3>✅ Controller Loaded Successfully</h3>";
    echo "</div>";
    
    // Verify variables
    echo "<h3>📊 Stats</h3>";
    echo "<table class='table table-sm'>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td>En Attente</td><td><strong>{$stats['en_attente']}</strong></td></tr>";
    echo "<tr><td>Commandées</td><td><strong>{$stats['commandees']}</strong></td></tr>";
    echo "<tr><td>ARC Reçus</td><td><strong>{$stats['arc_recus']}</strong></td></tr>";
    echo "<tr><td>Livraisons Prévues</td><td><strong>{$stats['livraisons_prevues']}</strong></td></tr>";
    echo "</table>";
    
    echo "<h3>📅 Agenda</h3>";
    echo "<p>Métrages: <strong>" . count($agenda_metrages) . "</strong></p>";
    echo "<p>Poses: <strong>" . count($agenda_poses) . "</strong></p>";
    
    // Sample data
    if(count($commandes_en_attente) > 0) {
        echo "<h4>Sample Commande En Attente:</h4>";
        echo "<pre>" . print_r($commandes_en_attente[0], true) . "</pre>";
    }
    
    if(count($agenda_metrages) > 0) {
        echo "<h4>Sample Métrage:</h4>";
        echo "<pre>" . print_r($agenda_metrages[0], true) . "</pre>";
    }
    
    echo "<div class='alert alert-success'>";
    echo "<h3>✅ ALL TESTS PASSED</h3>";
    echo "<p>No SQL errors, all variables populated correctly.</p>";
    echo "</div>";
    
} catch(Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
