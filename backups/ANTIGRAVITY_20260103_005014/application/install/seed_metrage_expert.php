<?php
// install/seed_metrage_expert.php (V3)
require_once __DIR__ . '/../db.php';

echo "SEEDING METRAGE EXPERT (V3 - MASTER)...";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("TRUNCATE TABLE metrage_points_controle");
    $pdo->exec("TRUNCATE TABLE metrage_types");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    // --- TYPES DE CHAMPS INTELLIGENTS ---
    // 'mm' -> Champ numérique simple (Active Keypad)
    // 'mm_3_points_min' -> Affiche 3 cases, retient le MIN
    // 'mm_3_points_max' -> Affiche 3 cases, retient le MAX

    // 1. MENUISERIE
    $bloc_menuiserie_v3 = [
        ['label' => '--- COTE & POSE MENUISERIE (V3) ---', 'type' => 'texte', 'aide' => 'Expert Mode'],
        
        ['label' => 'Type de Pose', 'type' => 'liste', 'options' => ['Rénovation (Dormant conservé)', 'Neuf (Tunnel)', 'Neuf (Applique)', 'Feuillure'], 'image_url' => 'assets/img/schemas/types_pose.png'],
        
        // 3 POINTS AUTO
        ['label' => 'Largeur Retenue (mm)', 'type' => 'mm_3_points_min', 'aide' => 'Relevé en 3 points (H/M/B)', 'image_url' => 'assets/img/schemas/regle_3_points.png'],
        ['label' => 'Hauteur Retenue (mm)', 'type' => 'mm_3_points_min', 'aide' => 'Relevé en 3 points (G/C/D)'],
        
        // REGLE SECURITE
        ['label' => 'Hauteur Allège (mm)', 'type' => 'mm', 'aide' => 'Sol fini au rejingot.', 
         'validation_rules' => json_encode(['min' => 900, 'msg_min' => "DANGER CHUTE ! Si étage, prévoir verre feuilleté ou garde-corps."])],
         
        ['label' => 'Jeu Suggéré (mm)', 'type' => 'mm', 'aide' => 'Jeu de pose à déduire.'],
    ];
    
    // ... (Je garde les autres blocs simplifiés pour cet exemple V3) ...
    $bloc_vr_renov_v3 = [
         ['label' => 'Pose VR', 'type' => 'liste', 'options' => ['A', 'B', 'C']],
         ['label' => 'Largeur Tableau', 'type' => 'mm_3_points_min'],
         ['label' => 'Hauteur Tableau', 'type' => 'mm_3_points_min'],
         ['label' => 'Profondeur Poignée Fenêtre (mm)', 'type' => 'mm', 
          'validation_rules' => json_encode(['max' => 30, 'msg_max' => "ATTENTION CONFLIT ! La poignée va toucher le tablier."])]
    ];

    // DEFINITION DES TYPES
    $types = [
        ['nom' => 'Fenêtre (Expert V3)', 'cat' => 'Menuiserie', 'bloc' => $bloc_menuiserie_v3, 'icone' => 'fas fa-window-maximize'],
        ['nom' => 'VR Rénovation (Expert V3)', 'cat' => 'Fermeture', 'bloc' => $bloc_vr_renov_v3, 'icone' => 'fas fa-blinds'],
    ];

    // INSERTION
    $type_stmt = $pdo->prepare("INSERT INTO metrage_types (nom, categorie, icone, description_technique) VALUES (?, ?, ?, 'V3 Expert')");
    $pt_stmt = $pdo->prepare("INSERT INTO metrage_points_controle (metrage_type_id, label, type_saisie, options_liste, message_aide, ordre, image_url, validation_rules) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($types as $t) {
        $type_stmt->execute([$t['nom'], $t['cat'], $t['icone']]);
        $type_id = $pdo->lastInsertId();
        
        $ordre = 1;
        foreach ($t['bloc'] as $p) {
            $opts = isset($p['options']) ? json_encode($p['options']) : null;
            $aide = $p['aide'] ?? null;
            $img = $p['image_url'] ?? null;
            $rules = $p['validation_rules'] ?? null;
            
            $pt_stmt->execute([$type_id, $p['label'], $p['type'], $opts, $aide, $ordre, $img, $rules]);
            $ordre++;
        }
    }

    echo "✅ SEEDING V3 TERMINÉ.\n";

} catch (Exception $e) {
    die("❌ Erreur : " . $e->getMessage() . "\n");
}
?>
