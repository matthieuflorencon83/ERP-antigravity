<?php
// tools/diag_planning_v4.php
require_once __DIR__ . '/../db.php';

echo "<h1>🔍 Diagnostic Planning V4 (FINAL CHECK)</h1>";

$startStr = '2026-01-01';
$endStr = '2026-01-31';

try {
    // 1. RENDEZ-VOUS (Corrected Query without technicien_id JOIN)
    echo "<h3>1. Rendez-Vous</h3>";
    $sql1 = "
            SELECT r.*, a.nom_affaire, 
                   c.nom_principal as client_nom, c.ville as client_ville, 
                   COALESCE(c.telephone_mobile, c.telephone_fixe) as client_tel, 
                   c.adresse_postale as client_adresse
            FROM rendez_vous r
            LEFT JOIN affaires a ON r.affaire_id = a.id
            LEFT JOIN clients c ON a.client_id = c.id
            WHERE r.date_rdv BETWEEN ? AND ?
        ";
    $stmt = $pdo->prepare($sql1);
    $stmt->execute([$startStr . ' 00:00:00', $endStr . ' 23:59:59']);
    $res1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Count: " . count($res1) . "</p>";
    if(count($res1) > 0) echo "<pre>" . print_r($res1[0], true) . "</pre>";

    // 2. SAV (With technicien_id JOIN)
    echo "<h3>2. SAV</h3>";
    $sql2 = "
            SELECT s.id, s.date_intervention,
                   u.nom_complet as technicien_nom
            FROM sav_interventions s
            LEFT JOIN utilisateurs u ON s.technicien_id = u.id
            WHERE s.date_intervention BETWEEN ? AND ?
        ";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$startStr . ' 00:00:00', $endStr . ' 23:59:59']);
    $res2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Count: " . count($res2) . "</p>";

} catch (Exception $e) {
    echo "<h1>❌ ERREUR SQL : " . $e->getMessage() . "</h1>";
}
