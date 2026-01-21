<?php
// tools/diag_planning.php
require_once __DIR__ . '/../db.php';

echo "<h1>🔍 Diagnostic Planning</h1>";

$startStr = '2026-01-01';
$endStr = '2026-01-31';

echo "<p>Période : <strong>$startStr</strong> au <strong>$endStr</strong></p>";

// 1. RENDEZ-VOUS
echo "<h3>1. RENDEZ_VOUS (Metrage/Pose)</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.type, r.date_rdv, a.nom_affaire 
        FROM rendez_vous r
        LEFT JOIN affaires a ON r.affaire_id = a.id
        WHERE r.date_rdv BETWEEN ? AND ?
    ");
    $stmt->execute([$startStr . ' 00:00:00', $endStr . ' 23:59:59']);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Count: " . count($res) . "</p>";
    if(count($res) > 0) {
        echo "<pre>" . print_r($res, true) . "</pre>";
    } else {
        // Check sans date
        $all = $pdo->query("SELECT COUNT(*) FROM rendez_vous")->fetchColumn();
        echo "<p>Total dans la table : $all</p>";
        // Show sample dates
        $dates = $pdo->query("SELECT date_rdv FROM rendez_vous LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        echo "Exemples de dates : " . implode(", ", $dates);
    }
} catch (Exception $e) {
    echo "❌ Erreur SQL: " . $e->getMessage();
}

// 2. SAV
echo "<h3>2. SAV (sav_interventions)</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.date_intervention 
        FROM sav_interventions s
        WHERE s.date_intervention BETWEEN ? AND ?
    ");
    $stmt->execute([$startStr . ' 00:00:00', $endStr . ' 23:59:59']);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Count: " . count($res) . "</p>";
    if(count($res) == 0) {
        $all = $pdo->query("SELECT COUNT(*) FROM sav_interventions")->fetchColumn();
        echo "<p>Total dans la table : $all</p>";
    }
} catch (Exception $e) {
    echo "❌ Erreur SQL: " . $e->getMessage();
}

// 3. LIVRAISONS
echo "<h3>3. LIVRAISONS (commandes_achats)</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT id, date_livraison_reelle, date_livraison_prevue 
        FROM commandes_achats 
        WHERE (date_livraison_reelle BETWEEN ? AND ?) 
           OR (date_livraison_prevue BETWEEN ? AND ? AND date_livraison_reelle IS NULL)
    ");
    $stmt->execute([$startStr, $endStr, $startStr, $endStr]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Count: " . count($res) . "</p>";
     if(count($res) == 0) {
        $dates = $pdo->query("SELECT date_livraison_prevue FROM commandes_achats WHERE date_livraison_prevue IS NOT NULL LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        echo "Exemples dates prevues : " . implode(", ", $dates);
    }
} catch (Exception $e) {
    echo "❌ Erreur SQL: " . $e->getMessage();
}
