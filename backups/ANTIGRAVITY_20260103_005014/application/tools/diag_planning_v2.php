<?php
// tools/diag_planning_v2.php
require_once __DIR__ . '/../db.php';

echo "<h1>🔍 Diagnostic Planning V2 (Enriched Query)</h1>";

$startStr = '2026-01-01';
$endStr = '2026-01-31';

try {
    // Exact Query from API V3 (CORRECTED)
    $sql = "
            SELECT r.*, a.nom_affaire, 
                   c.nom_principal as client_nom, c.ville as client_ville, 
                   COALESCE(c.telephone_mobile, c.telephone_fixe) as client_tel, 
                   c.adresse_postale as client_adresse,
                   u.nom_complet as technicien_nom
            FROM rendez_vous r
            LEFT JOIN affaires a ON r.affaire_id = a.id
            LEFT JOIN clients c ON a.client_id = c.id
            LEFT JOIN utilisateurs u ON r.technicien_id = u.id
            WHERE r.date_rdv BETWEEN ? AND ?
        ";
        
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startStr . ' 00:00:00', $endStr . ' 23:59:59']);
    
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Count: " . count($res) . "</p>";
    
    if(count($res) > 0) {
        echo "<pre>" . print_r($res[0], true) . "</pre>"; // Show first item
    } else {
        echo "Aucun résultat trouvé.";
    }

} catch (Exception $e) {
    echo "<h1>❌ ERREUR SQL CRITIQUE : " . $e->getMessage() . "</h1>";
    echo "<pre>$sql</pre>";
}
