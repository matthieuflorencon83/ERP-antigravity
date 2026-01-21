<?php
require_once __DIR__ . '/../db.php';

echo "--- FOURNISSEURS ---\n";
$stmt = $pdo->query("SELECT id, nom FROM fournisseurs LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | Nom: " . $row['nom'] . "\n";
}

echo "\n--- COMMANDES ACHATS (TEST) ---\n";
$stmt = $pdo->query("SELECT id, ref_interne, fournisseur_id, affaire_id, statut FROM commandes_achats WHERE ref_interne LIKE 'CMD-%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | Ref: " . $row['ref_interne'] . " | FournID: " . $row['fournisseur_id'] . " | Aff.ID: " . $row['affaire_id'] . "\n";
}
