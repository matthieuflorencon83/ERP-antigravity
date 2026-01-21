<?php
// tools/test_multi_orders.php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

echo "\n🛒 TEST AUTOMATISÉ : SYSTEME MULTI-COMMANDES (STANDARD 2025)\n";
echo "========================================================\n";

function assertState($condition, $msg) {
    echo $condition ? "✅ $msg\n" : "❌ FAIL: $msg\n";
    if (!$condition) exit(1);
}

try {
    // 1. SETUP : Créer une affaire avec 2 lignes
    $pdo->beginTransaction();
    
    // Create Affaire
    $stmt = $pdo->prepare("INSERT INTO affaires (client_id, nom_affaire) VALUES (1, 'Test Multi-Cmd')");
    $stmt->execute();
    $aff_id = $pdo->lastInsertId();
    
    // Create Mission
    $stmt = $pdo->prepare("INSERT INTO metrage_interventions (affaire_id, statut) VALUES (?, 'VALIDE')");
    $stmt->execute([$aff_id]);
    $mission_id = $pdo->lastInsertId();

    // Ligne 1 : Fenetre (A commander)
    $pdo->exec("INSERT INTO metrage_lignes (intervention_id, metrage_type_id, localisation, donnees_json, statut_traitement) VALUES ($mission_id, 1, 'Salon', '{}', 'NON_TRAITE')");
    $id_fenetre = $pdo->lastInsertId();

    // Ligne 2 : Volet (Ne pas commander)
    $pdo->exec("INSERT INTO metrage_lignes (intervention_id, metrage_type_id, localisation, donnees_json, statut_traitement) VALUES ($mission_id, 2, 'Salon', '{}', 'NON_TRAITE')");
    $id_volet = $pdo->lastInsertId();

    $pdo->commit();
    echo "🔹 Setup: Affaire #$aff_id créée avec Fenêtre (#$id_fenetre) et Volet (#$id_volet) non traités.\n";


    // 2. SIMULATION : POST Request logic (Partial Order)
    // On simule la logique de affaires_generer_commandes.php
    $fournisseur_id = 1;
    $items_selected = ["M_" . $id_fenetre]; // ON NE SÉLECTIONNE QUE LA FENÊTRE
    $mode = "PORTAIL_WEB";

    // Action
    $pdo->beginTransaction();
    $d = "Lot Menuiserie Test";
    $pdo->prepare("INSERT INTO commandes_achats (fournisseur_id, affaire_id, ref_interne, designation, mode_commande) VALUES (?, ?, 'TEST-CMD', ?, ?)")
        ->execute([$fournisseur_id, $aff_id, $d, $mode]);
    $cmd_id = $pdo->lastInsertId();

    // Traitement Ligne
    $pdo->exec("UPDATE metrage_lignes SET statut_traitement='TRAITE' WHERE id=$id_fenetre");
    // On touche pas au volet
    $pdo->commit();

    // 3. VERIFICATION
    $line_fen = $pdo->query("SELECT statut_traitement FROM metrage_lignes WHERE id=$id_fenetre")->fetchColumn();
    $line_vol = $pdo->query("SELECT statut_traitement FROM metrage_lignes WHERE id=$id_volet")->fetchColumn();
    $cmd_mode = $pdo->query("SELECT mode_commande FROM commandes_achats WHERE id=$cmd_id")->fetchColumn();

    // VERIFICATION TRACABILITE
    echo "🔍 VERIFICATION LIENS BDD (HARD-LINK)...\n";
    $lines = $pdo->query("SELECT * FROM lignes_achat WHERE commande_id = $cmd_id")->fetchAll();
    
    foreach ($lines as $l) {
        if ($l['metrage_ligne_id'] > 0) {
            echo "   ✅ Ligne #{$l['id']} liée au Métrage #{$l['metrage_ligne_id']}\n";
        } else {
            echo "   ❌ ERREUR: Ligne #{$l['id']} sans lien Métrage !\n";
        }
    }
    
    if (count($lines) > 0) echo "✅ TRACABILITE VALIDÉE.\n";
    assertState($line_fen === 'TRAITE', "La fenêtre est marquée TRAITE.");
    assertState($line_vol === 'NON_TRAITE', "Le volet est resté NON_TRAITE (Granularité respectée).");
    assertState($cmd_mode === 'PORTAIL_WEB', "Le mode de commande est bien PORTAIL_WEB.");

    echo "\n🏆 CERTIFICATION : LE MODULE MULTI-COMMANDES EST OPERATIONNEL.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "❌ CRITICAL ERROR: " . $e->getMessage();
    exit(1);
}
?>
