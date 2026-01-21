<?php
// sav_search_ajax.php - Moteur de recherche unifié pour le SAV
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// 1. Recherche CLIENTS (Nom, Ville, Tel)
$stmt = $pdo->prepare("
    SELECT id, nom_principal as nom, ville, telephone_mobile as telephone, 'CLIENT' as type 
    FROM clients 
    WHERE nom_principal LIKE ? OR ville LIKE ? OR telephone_mobile LIKE ? 
    LIMIT 5
");
$term = "%$q%";
$stmt->execute([$term, $term, $term]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($clients as $c) {
    $results[] = [
        'id' => $c['id'],
        'label' => "👤 " . $c['nom'] . " (" . $c['ville'] . ") - " . $c['telephone'],
        'value' => $c['id'],
        'type' => 'client',
        'data' => $c
    ];
}

// 2. Recherche TICKETS SAV (Numéro)
$stmt = $pdo->prepare("
    SELECT t.id, t.numero_ticket, t.statut, COALESCE(c.nom, t.prospect_nom) as nom_display, 'TICKET' as type
    FROM sav_tickets t
    LEFT JOIN clients c ON t.client_id = c.id
    WHERE t.numero_ticket LIKE ? OR t.prospect_nom LIKE ?
    LIMIT 5
");
$stmt->execute([$term, $term]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($tickets as $t) {
    $results[] = [
        'id' => $t['id'],
        'label' => "🎫 " . $t['numero_ticket'] . " - " . $t['nom_display'] . " [" . $t['statut'] . "]",
        'value' => $t['id'],
        'type' => 'ticket',
        'data' => $t
    ];
}

// 3. Recherche AFFAIRES (Numéro ou Nom) - Optionnel mais utile pour lier
$stmt = $pdo->prepare("
    SELECT id, nom_affaire, ville_chantier, 'AFFAIRE' as type
    FROM affaires
    WHERE nom_affaire LIKE ? OR ville_chantier LIKE ?
    LIMIT 3
");
$stmt->execute([$term, $term]);
$affaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($affaires as $a) {
    $results[] = [
        'id' => $a['id'],
        'label' => "📁 " . $a['nom_affaire'] . " (" . $a['ville_chantier'] . ")",
        'value' => $a['id'],
        'type' => 'affaire',
        'data' => $a
    ];
}

echo json_encode($results);
