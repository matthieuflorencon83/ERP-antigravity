<?php
require_once '../db.php';
header('Content-Type: text/plain');

try {
    // 1. Trouver une affaire candidate
    $stmt = $pdo->query("SELECT id FROM affaires WHERE statut = 'Devis' LIMIT 1");
    $affaire = $stmt->fetch();
    
    if (!$affaire) die("Aucune affaire 'Devis' trouvée.");

    // 2. Insérer intervention
    $stmt = $pdo->prepare("
        INSERT INTO metrage_interventions (affaire_id, statut, created_at)
        VALUES (?, 'A_PLANIFIER', NOW())
    ");
    $stmt->execute([$affaire['id']]);
    
    echo "SUCCESS: Intervention créée pour affaire ID " . $affaire['id'];

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
