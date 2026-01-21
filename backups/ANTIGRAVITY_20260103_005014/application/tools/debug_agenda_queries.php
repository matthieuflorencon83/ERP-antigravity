<?php
// tools/debug_agenda_queries.php
require_once __DIR__ . '/../db.php';

echo "<h2>🔍 Debug Agenda Queries</h2>";

try {
    // Check rendez_vous data
    echo "<h4>1. Rendez-vous créés</h4>";
    $stmt = $pdo->query("SELECT * FROM rendez_vous ORDER BY date_rdv");
    $rdvs = $stmt->fetchAll();
    
    echo "<p>Total: " . count($rdvs) . " rendez-vous</p>";
    echo "<table class='table table-sm'>";
    echo "<tr><th>ID</th><th>Affaire</th><th>Type</th><th>Date</th><th>Statut</th></tr>";
    foreach($rdvs as $r) {
        echo "<tr><td>{$r['id']}</td><td>{$r['affaire_id']}</td><td>{$r['type']}</td><td>{$r['date_rdv']}</td><td>{$r['statut']}</td></tr>";
    }
    echo "</table>";
    
    // Check clients table structure
    echo "<h4>2. Structure table clients</h4>";
    $stmt = $pdo->query("DESCRIBE clients");
    $clientCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Colonnes: " . implode(', ', $clientCols) . "</p>";
    
    // Check affaires table structure
    echo "<h4>3. Structure table affaires</h4>";
    $stmt = $pdo->query("DESCRIBE affaires");
    $affaireCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Colonnes: " . implode(', ', $affaireCols) . "</p>";
    
    // Test the actual query
    echo "<h4>4. Test Query Métrage</h4>";
    $stmt = $pdo->query("
        SELECT r.*, a.reference as nom_affaire, a.designation, c.nom_principal as client_nom, c.ville as ville_chantier,
               DATEDIFF(r.date_rdv, CURDATE()) as jours_restants
        FROM rendez_vous r
        JOIN affaires a ON r.affaire_id = a.id
        JOIN clients c ON a.client_id = c.id
        WHERE r.date_rdv BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND r.type = 'metrage'
        AND r.statut != 'termine'
        ORDER BY r.date_rdv ASC
        LIMIT 15
    ");
    $metrages = $stmt->fetchAll();
    
    echo "<p>Résultats: " . count($metrages) . " métrages</p>";
    
    if(count($metrages) > 0) {
        echo "<table class='table table-sm'>";
        echo "<tr><th>Date</th><th>Affaire</th><th>Client</th><th>Ville</th></tr>";
        foreach($metrages as $m) {
            echo "<tr><td>{$m['date_rdv']}</td><td>{$m['nom_affaire']}</td><td>{$m['client_nom']}</td><td>{$m['ville_chantier']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Test query poses
    echo "<h4>5. Test Query Poses</h4>";
    $stmt = $pdo->query("
        SELECT r.*, a.reference as nom_affaire, a.designation, c.nom_principal as client_nom,
               DATEDIFF(r.date_rdv, CURDATE()) as jours_avant_debut
        FROM rendez_vous r
        JOIN affaires a ON r.affaire_id = a.id
        JOIN clients c ON a.client_id = c.id
        WHERE r.date_rdv BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND r.type = 'pose'
        AND r.statut IN ('planifie', 'en_cours')
        ORDER BY r.date_rdv ASC
        LIMIT 15
    ");
    $poses = $stmt->fetchAll();
    
    echo "<p>Résultats: " . count($poses) . " poses</p>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}
