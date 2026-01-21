<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

echo "<h1>📊 Planning Data Check</h1>";

$start = date('Y-m-01');
$end = date('Y-m-t');

echo "<h3>Period: $start to $end</h3>";

// 1. RENDEZ-VOUS
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE date_rdv BETWEEN ? AND ?");
$stmt->execute([$start, $end]);
$count_rdv = $stmt->fetchColumn();
echo "<p>🔵/🟢 Rendez-Vous (Metrage/Pose): <strong>$count_rdv</strong></p>";

// 2. SAV
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sav_interventions WHERE date_debut BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $count_sav = $stmt->fetchColumn();
    echo "<p>🔴 SAV Interventions: <strong>$count_sav</strong></p>";
} catch(Exception $e) {
    echo "<p>🔴 SAV Error: " . $e->getMessage() . "</p>";
}

// 3. LIVRAISONS
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes_achats WHERE date_livraison_reelle BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $count_liv = $stmt->fetchColumn();
    echo "<p>🟡 Livraisons (Reelles): <strong>$count_liv</strong></p>";
    
    // Check pending deliveries
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes_achats WHERE date_livraison_prevue BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $count_liv_prev = $stmt->fetchColumn();
    echo "<p>⚠️ Livraisons (Prévues): <strong>$count_liv_prev</strong> (Not displayed currently? Code checks 'reelle')</p>";
    
} catch(Exception $e) {
    echo "<p>🟡 Livraison Error: " . $e->getMessage() . "</p>";
}
