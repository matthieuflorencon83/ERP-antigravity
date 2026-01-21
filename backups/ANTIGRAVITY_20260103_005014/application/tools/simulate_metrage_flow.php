<?php
// tools/simulate_metrage_flow.php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php'; // For h() if needed

echo "🚀 DÉMARRAGE DE LA SIMULATION DU FLUX MÉTRAGE...\n\n";

$logs = [];

function logTest($step, $success, $msg) {
    global $logs;
    $status = $success ? "✅ SUCCESS" : "❌ FAILURE";
    echo "[$step] $status : $msg\n";
    $logs[] = ['step' => $step, 'success' => $success, 'msg' => $msg];
    if (!$success) exit(1);
}

try {
    // --- ETAPE 0 : NETTOYAGE (Pour ne pas polluer) ---
    // On va créer une affaire de test, donc pas besoin de truncate tout, mais on nettoie les tests précédents
    $test_ref = "TEST-METRAGE-" . time();
    
    // --- ETAPE 1 : CRÉATION AVANT-PROJET (Simulation Interface Bureau / Affaires) ---
    echo "--- PHASE 1 : PLANNING (BUREAU) ---\n";
    
    // 1.1 Créer Client Test
    $stmt_client = $pdo->prepare("INSERT INTO clients (nom_principal, ville) VALUES (?, ?)");
    $stmt_client->execute(['Client Test Simulation', 'Paris']);
    $client_id = $pdo->lastInsertId();
    logTest("Creation Client", $client_id > 0, "Client ID $client_id créé.");

    // 1.2 Créer Affaire Test
    $pdo->prepare("INSERT INTO affaires (client_id, nom_affaire, numero_prodevis, statut) VALUES (?, ?, ?, ?)")
        ->execute([$client_id, "Affaire Simulation $test_ref", $test_ref, 'Devis']);
    $affaire_id = $pdo->lastInsertId();
    logTest("Creation Affaire", $affaire_id > 0, "Affaire ID $affaire_id créée (Ref: $test_ref).");

    // 1.3 Planifier Métrage (Simulation gestion_metrage_planning.php)
    $stmt = $pdo->prepare("INSERT INTO metrage_interventions (affaire_id, date_prevue, statut, notes_generales) VALUES (?, NOW(), 'A_PLANIFIER', 'Note test bureau')");
    $stmt->execute([$affaire_id]);
    $mission_id = $pdo->lastInsertId();
    logTest("Planification Intervention", $mission_id > 0, "Mission ID $mission_id créée (Statut: A_PLANIFIER).");


    // --- ETAPE 2 : TERRAIN - DÉMARRAGE (Simulation Interface Mobile) ---
    echo "\n--- PHASE 2 : TERRAIN (MOBILE) ---\n";

    // 2.1 Démarrer Mission (AJAX: start_mission)
    $pdo->prepare("UPDATE metrage_interventions SET statut = 'EN_COURS' WHERE id = ?")->execute([$mission_id]);
    
    // Vérif
    $check = $pdo->query("SELECT statut FROM metrage_interventions WHERE id = $mission_id")->fetchColumn();
    logTest("Start Mission", $check === 'EN_COURS', "Statut passé à 'EN_COURS'.");

    // 2.2 Récupérer Types (Simulation affichage Wizard)
    $types = $pdo->query("SELECT id, nom FROM metrage_types WHERE nom LIKE 'Fenêtre%' LIMIT 1")->fetch();
    $type_id = $types['id'];
    logTest("Fetch Type", $type_id > 0, "Type récupéré : {$types['nom']} (ID: $type_id).");


    // --- ETAPE 3 : SAISIE DONNÉES EXPERT (Simulation AJAX Save) ---
    echo "\n--- PHASE 3 : SAISIE EXPERT ---\n";
    
    // 3.1 Récupérer Points de contrôle pour ce type
    $points = $pdo->prepare("SELECT id, label FROM metrage_points_controle WHERE metrage_type_id = ?");
    $points->execute([$type_id]);
    $pts = $points->fetchAll();
    
    // Construire payload JSON fictif
    $data_payload = [];
    foreach($pts as $p) {
        $data_payload[$p['id']] = "Valeur Test pour " . $p['label'];
    }
    $json_data = json_encode($data_payload);
    
    // 3.2 Insert Ligne (Simulation save_ligne)
    $stmt_ligne = $pdo->prepare("INSERT INTO metrage_lignes (intervention_id, metrage_type_id, localisation, donnees_json, notes_observateur) VALUES (?, ?, ?, ?, ?)");
    $stmt_ligne->execute([$mission_id, $type_id, "Salon", $json_data, "Ras, tout est ok."]);
    $ligne_id = $pdo->lastInsertId();
    
    logTest("Save Ligne", $ligne_id > 0, "Ligne de métrage insérée (ID: $ligne_id).");
    
    // 3.3 Vérif Data JSON
    $saved_data = $pdo->query("SELECT donnees_json FROM metrage_lignes WHERE id = $ligne_id")->fetchColumn();
    $decoded = json_decode($saved_data, true);
    logTest("Check JSON", count($decoded) === count($pts), "JSON intègre (" . count($decoded) . " champs stockés).");


    // --- ETAPE 4 : VALIDATION FINALE ---
    echo "\n--- PHASE 4 : VALIDATION ---\n";
    
    // 4.1 Terminer Mission
    $pdo->prepare("UPDATE metrage_interventions SET statut = 'VALIDE', date_realisee = NOW() WHERE id = ?")->execute([$mission_id]);
    
    $final_status = $pdo->query("SELECT statut, date_realisee FROM metrage_interventions WHERE id = $mission_id")->fetch();
    logTest("Finish Mission", $final_status['statut'] === 'VALIDE', "Mission validée.");
    logTest("Check Date", !empty($final_status['date_realisee']), "Date réalisation enregistrée.");

    echo "\n🏆 FLUX COMPLET VÉRIFIÉ AVEC SUCCÈS !\n";
    
    // Clean up (Optional, maybe keep for manual inspection)
    // $pdo->exec("DELETE FROM metrage_interventions WHERE id = $mission_id");
    // $pdo->exec("DELETE FROM affaires WHERE id = $affaire_id");
    
} catch (Exception $e) {
    logTest("EXCEPTION", false, $e->getMessage());
}
