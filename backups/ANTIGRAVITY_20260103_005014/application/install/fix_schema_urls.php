<?php
// install/fix_schema_urls.php
// Update schema URLs to match actual files
require_once '../db.php';

header('Content-Type: application/json');

try {
    // Update schema_url to match existing files
    $updates = [
        ['type_pose', 'schemas/pose_types.png'],
        ['forme', 'schemas/formes.png'],
        ['dimensions_largeur', 'schemas/dimensions_largeur.png'],
        ['dimensions_hauteur', 'schemas/dimensions_largeur.png'], // reuse same
        ['equerrage', 'schemas/regle_3_points.png'],
        ['profondeur_dormant', 'schemas/pose_types.png'],
        ['seuil', 'schemas/pose_types.png'],
        ['type_coffre', 'schemas/vr_coffre_tunnel.png'],
        ['largeur_tablier', 'schemas/vr_pose_renov.png'],
        ['hauteur_tablier', 'schemas/vr_pose_renov.png'],
        ['manoeuvre', 'schemas/vr_manoeuvre.png'],
        ['sens_ouverture', 'schemas/pose_types.png'],
    ];

    $stmt = $pdo->prepare("UPDATE metrage_etapes SET schema_url = ? WHERE code_etape = ?");
    $updated = 0;
    
    foreach ($updates as $u) {
        $stmt->execute([$u[1], $u[0]]);
        $updated += $stmt->rowCount();
    }

    echo json_encode([
        'success' => true,
        'updated' => $updated
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
