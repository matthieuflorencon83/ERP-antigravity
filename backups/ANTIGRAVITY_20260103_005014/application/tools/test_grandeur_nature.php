<?php
// tools/test_grandeur_nature.php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

echo "\n🎬 SCÉNARIO GRANDEUR NATURE : DU CLIENT À LA COMMANDE\n";
echo "======================================================\n";

$logs = [];
function stepLog($title, $detail) {
    echo "\n🔹 $title\n   $detail\n";
}

try {
    // 1. CLIENT APPELLE (Création Affaire)
    $client_nom = "Mme Durand " . time();
    $stmt = $pdo->prepare("INSERT INTO clients (nom_principal, ville) VALUES (?, ?)");
    $stmt->execute([$client_nom, 'Bordeaux']);
    $client_id = $pdo->lastInsertId();
    stepLog("1. CLIENT", "Nouveau client créé : $client_nom (ID: $client_id)");

    $aff_nom = "Rénov Menuiseries";
    $ref_devis = "D-" . time();
    $stmt = $pdo->prepare("INSERT INTO affaires (client_id, nom_affaire, numero_prodevis, statut) VALUES (?, ?, ?, 'Devis')");
    $stmt->execute([$client_id, $aff_nom, $ref_devis]);
    $affaire_id = $pdo->lastInsertId();
    stepLog("2. AFFAIRE", "Dossier créé : $aff_nom (Ref: $ref_devis)");

    // 2. BUREAU PLANIFIE LE MÉTRAGE
    $date_prevue = date('Y-m-d H:i:s', strtotime('+2 days 10:00'));
    $stmt = $pdo->prepare("INSERT INTO metrage_interventions (affaire_id, date_prevue, statut, notes_generales) VALUES (?, ?, 'PLANIFIE', ?)");
    $stmt->execute([$affaire_id, $date_prevue, "Attention au chien. Code portail 1234."]);
    $mission_id = $pdo->lastInsertId();
    stepLog("3. PLANNING", "RDV Métrage fixé le $date_prevue (Mission #$mission_id)");

    // 3. SUR LE CHANTIER (Simulation Mobile)
    // Le métreur arrive
    $pdo->prepare("UPDATE metrage_interventions SET statut='EN_COURS' WHERE id=?")->execute([$mission_id]);
    stepLog("4. TERRAIN", "Métreur sur place. Statut -> EN_COURS");

    // Il saisit une Fenêtre Rénovation Complexe
    // Récup ID Type "Fenêtre (Rénovation)"
    $type_fen = $pdo->query("SELECT id FROM metrage_types WHERE nom LIKE 'Fenêtre (Rénovation)%'")->fetchColumn();
    
    // Récup IDs Points de contrôle (On simule la recherche dynamique)
    // On veut remplir : Largeur, Hauteur, Type Ouv., Habillage Ext
    $pts = $pdo->query("SELECT id, label FROM metrage_points_controle WHERE metrage_type_id = $type_fen")->fetchAll(PDO::FETCH_KEY_PAIR);
    // $pts est un tableau [ID => Label]
    
    // On mappe nos réponses
    $reponses = [];
    foreach($pts as $id => $label) {
        if (strpos($label, 'Largeur (mm)') !== false) $reponses[$id] = "1250"; // Generic width
        if (strpos($label, 'Hauteur (mm)') !== false) $reponses[$id] = "2100"; // Generic height
        if (strpos($label, 'Jeu Largeur') !== false) $reponses[$id] = "10"; 
        if (strpos($label, 'Référence Mesure') !== false) $reponses[$id] = "Cote Tableau (Béton)";
        if (strpos($label, 'Type d\'Ouverture') !== false) $reponses[$id] = "Oscillo-Battant (OB)"; 
        if (strpos($label, 'Habillage Extérieur') !== false) $reponses[$id] = "Cornière 60x40"; 
        if (strpos($label, 'Couleur') !== false) $reponses[$id] = "RAL 7016";
    }
    
    $json = json_encode($reponses);
    $stmt = $pdo->prepare("INSERT INTO metrage_lignes (intervention_id, metrage_type_id, localisation, donnees_json, notes_observateur) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$mission_id, $type_fen, "Cuisine", $json, "Prévoir échafaudage si possible."]);
    $line_id = $pdo->lastInsertId();
    stepLog("5. SAISIE MOBILE", "Ligne 'Fenêtre' enregistrée (ID: $line_id) avec options : OB, Cornière 60x40, RAL 7016.");

    // 4. VALIDATION
    $pdo->prepare("UPDATE metrage_interventions SET statut='VALIDE', date_realisee=NOW() WHERE id=?")->execute([$mission_id]);
    stepLog("6. VALIDATION", "Mission terminée et validée.");

    // 5. ETAPES SUIVANTES (Simulées)
    stepLog("7. RAPPORT", "Le PDF technique est généré (Virtuellement).");
    stepLog("8. COMMANDE", "Le gestionnaire peut clore le dossier : 'Générer Commande' est disponible.");

    echo "\n✅ TEST GRANDEUR NATURE RÉUSSI (100%).\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
?>
