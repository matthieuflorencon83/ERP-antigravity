<?php
// install/add_all_categories_etapes.php
// Add etapes for all missing categories
require_once '../db.php';

header('Content-Type: application/json');

try {
    $sql = "INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, options_json, schema_url, rappel, est_obligatoire) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $inserted = 0;

    // =====================
    // GARAGE (Porte de garage)
    // =====================
    $garage = [
        ['GARAGE', 2, 'type_garage', 'Type de porte', 'Quel <strong>type de porte de garage</strong> ?', 'liste', '["Sectionnelle plafond", "Sectionnelle latérale", "Basculante débordante", "Basculante non débordante", "Enroulable", "Battante (traditionnelle)"]', NULL, NULL, 1],
        ['GARAGE', 3, 'largeur_passage', 'Largeur passage', 'Mesurez la <strong>largeur du passage</strong> libre.', 'mm', NULL, NULL, '⚠️ Mesurez entre les maçonneries nues.', 1],
        ['GARAGE', 4, 'hauteur_passage', 'Hauteur passage', 'Mesurez la <strong>hauteur du passage</strong>.', 'mm', NULL, NULL, '⚠️ Du sol fini au linteau.', 1],
        ['GARAGE', 5, 'retombee_linteau', 'Retombée linteau', 'Hauteur de la <strong>retombée linteau</strong> (espace au-dessus).', 'mm', NULL, NULL, '💡 Minimum 200mm pour sectionnelle.', 1],
        ['GARAGE', 6, 'ecoincon_gauche', 'Écoinçon gauche', 'Largeur de l\'<strong>écoinçon gauche</strong>.', 'mm', NULL, NULL, '💡 Minimum 100mm pour motorisation.', 1],
        ['GARAGE', 7, 'ecoincon_droit', 'Écoinçon droit', 'Largeur de l\'<strong>écoinçon droit</strong>.', 'mm', NULL, NULL, NULL, 1],
        ['GARAGE', 8, 'profondeur_refoulement', 'Profondeur refoulement', 'Quelle <strong>profondeur</strong> disponible pour le refoulement ?', 'mm', NULL, NULL, '⚠️ Pour sectionnelle: hauteur passage + 500mm minimum.', 1],
        ['GARAGE', 9, 'motorisation', 'Motorisation', 'Quel type de <strong>motorisation</strong> ?', 'liste', '["Déportée plafond", "Intégrée à l\'axe", "Motorisation latérale", "Manuelle (sans moteur)"]', NULL, NULL, 1],
        ['GARAGE', 10, 'portillon', 'Portillon intégré', 'Faut-il un <strong>portillon piéton intégré</strong> ?', 'binaire', '["Oui", "Non"]', NULL, '💡 Pratique pour passage sans ouvrir toute la porte.', 0],
        ['GARAGE', 11, 'hublots', 'Hublots', 'Faut-il des <strong>hublots</strong> (fenêtres) ?', 'liste', '["Sans hublots", "1 rangée de hublots", "2 rangées de hublots"]', NULL, NULL, 0],
        ['GARAGE', 12, 'coloris_garage', 'Coloris', 'Quel <strong>coloris</strong> ?', 'liste', '["Blanc 9016", "Gris Anthracite 7016", "Marron 8014", "Bois (imitation chêne)", "Autre RAL"]', NULL, NULL, 1],
    ];

    // =====================
    // PERGOLA
    // =====================
    $pergola = [
        ['PERGOLA', 2, 'type_pergola', 'Type de pergola', 'Quel <strong>type de pergola</strong> ?', 'liste', '["Bioclimatique (lames orientables)", "Toile rétractable", "Toile fixe", "Polycarbonate"]', NULL, NULL, 1],
        ['PERGOLA', 3, 'adossee_autoportee', 'Configuration', 'La pergola est-elle <strong>adossée</strong> ou <strong>autoportée</strong> ?', 'liste', '["Adossée au mur", "Autoportée (4 poteaux)"]', NULL, NULL, 1],
        ['PERGOLA', 4, 'largeur_pergola', 'Largeur', 'Quelle <strong>largeur</strong> de pergola ?', 'mm', NULL, NULL, '💡 Projection horizontale.', 1],
        ['PERGOLA', 5, 'avancee_pergola', 'Avancée / Profondeur', 'Quelle <strong>avancée</strong> (profondeur) ?', 'mm', NULL, NULL, NULL, 1],
        ['PERGOLA', 6, 'hauteur_poteau', 'Hauteur poteaux', 'Quelle <strong>hauteur sous poutre</strong> souhaitée ?', 'mm', NULL, NULL, '💡 Standard: 2200 à 2800mm.', 1],
        ['PERGOLA', 7, 'pente_pergola', 'Sens de pente', 'Quel <strong>sens de pente</strong> pour l\'évacuation des eaux ?', 'liste', '["Vers l\'arrière (mur)", "Vers l\'avant", "Latérale gauche", "Latérale droite"]', NULL, '⚠️ Important pour l\'écoulement des eaux.', 1],
        ['PERGOLA', 8, 'eclairage', 'Éclairage', 'Faut-il un <strong>éclairage intégré</strong> ?', 'liste', '["Sans éclairage", "Spots LED", "Bandeau LED périphérique"]', NULL, NULL, 0],
        ['PERGOLA', 9, 'coloris_pergola', 'Coloris', 'Quel <strong>coloris</strong> ?', 'liste', '["Blanc 9016", "Gris Anthracite 7016", "Noir 9005", "Autre RAL"]', NULL, NULL, 1],
    ];

    // =====================
    // PORTAIL
    // =====================
    $portail = [
        ['PORTAIL', 2, 'type_portail', 'Type de portail', 'Quel <strong>type de portail</strong> ?', 'liste', '["Battant 2 vantaux", "Coulissant rail au sol", "Coulissant autoportant", "Portillon seul"]', NULL, NULL, 1],
        ['PORTAIL', 3, 'largeur_portail', 'Largeur passage', 'Quelle <strong>largeur de passage</strong> ?', 'mm', NULL, NULL, '💡 Standard: 3000 à 4000mm pour passage véhicule.', 1],
        ['PORTAIL', 4, 'hauteur_portail', 'Hauteur', 'Quelle <strong>hauteur</strong> de portail ?', 'mm', NULL, NULL, '💡 Standard: 1200 à 1800mm.', 1],
        ['PORTAIL', 5, 'piliers', 'Piliers existants', 'Les <strong>piliers</strong> sont-ils existants ?', 'liste', '["Oui, piliers existants", "Non, piliers à créer", "Scellement dans le mur"]', NULL, NULL, 1],
        ['PORTAIL', 6, 'largeur_pilier', 'Largeur piliers', 'Quelle <strong>largeur entre piliers</strong> (nu à nu) ?', 'mm', NULL, NULL, '⚠️ Mesurez entre les faces internes des piliers.', 1],
        ['PORTAIL', 7, 'motorisation_portail', 'Motorisation', 'Quel type de <strong>motorisation</strong> ?', 'liste', '["Vérins (battant)", "Bras articulés (battant)", "Coulissant rail", "Enterrée (battant)", "Sans motorisation"]', NULL, NULL, 1],
        ['PORTAIL', 8, 'remplissage_portail', 'Remplissage', 'Quel type de <strong>remplissage</strong> ?', 'liste', '["Plein (occultant)", "Ajouré (barreaux)", "Semi-ajouré", "Tôle perforée"]', NULL, NULL, 1],
        ['PORTAIL', 9, 'coloris_portail', 'Coloris', 'Quel <strong>coloris</strong> ?', 'liste', '["Gris Anthracite 7016", "Noir 9005", "Blanc 9016", "Vert 6005", "Autre RAL"]', NULL, NULL, 1],
    ];

    // =====================
    // STORE
    // =====================
    $store = [
        ['STORE', 2, 'type_store', 'Type de store', 'Quel <strong>type de store</strong> ?', 'liste', '["Store banne coffre intégral", "Store banne semi-coffre", "Store banne monobloc", "Screen vertical (zip)", "Brise-soleil orientable (BSO)"]', NULL, NULL, 1],
        ['STORE', 3, 'largeur_store', 'Largeur', 'Quelle <strong>largeur</strong> de store ?', 'mm', NULL, NULL, NULL, 1],
        ['STORE', 4, 'avancee_store', 'Avancée', 'Quelle <strong>avancée</strong> (projection) ?', 'mm', NULL, NULL, '💡 Pour store banne: max 4000mm standard.', 1],
        ['STORE', 5, 'hauteur_pose', 'Hauteur de pose', 'À quelle <strong>hauteur</strong> sera posé le store ?', 'mm', NULL, NULL, '⚠️ Du sol au point de fixation.', 1],
        ['STORE', 6, 'support_fixation', 'Support de fixation', 'Sur quel <strong>support</strong> sera fixé le store ?', 'liste', '["Mur maçonné", "Plafond (sous balcon)", "Chevrons bois", "IPN métallique"]', NULL, '⚠️ Important pour le choix des fixations.', 1],
        ['STORE', 7, 'manoeuvre_store', 'Manœuvre', 'Quel type de <strong>manœuvre</strong> ?', 'liste', '["Moteur filaire", "Moteur radio", "Moteur solaire", "Manivelle manuelle"]', NULL, NULL, 1],
        ['STORE', 8, 'toile_store', 'Type de toile', 'Quel type de <strong>toile</strong> ?', 'liste', '["Toile acrylique unie", "Toile acrylique rayures", "Toile micro-perforée", "Toile PVC (screen)"]', NULL, NULL, 1],
        ['STORE', 9, 'coloris_armature', 'Coloris armature', 'Quel coloris pour l\'<strong>armature</strong> ?', 'liste', '["Blanc 9016", "Gris Anthracite 7016", "Marron", "Autre RAL"]', NULL, NULL, 1],
    ];

    // =====================
    // VERANDA
    // =====================
    $veranda = [
        ['VERANDA', 2, 'type_veranda', 'Type', 'Quel <strong>type de projet</strong> ?', 'liste', '["Véranda classique", "Extension vitrée", "Fermeture de loggia", "SAS d\'entrée"]', NULL, NULL, 1],
        ['VERANDA', 3, 'forme_veranda', 'Forme', 'Quelle <strong>forme</strong> de véranda ?', 'liste', '["Rectangulaire", "En L", "Avec pan coupé", "Arrondie"]', NULL, NULL, 1],
        ['VERANDA', 4, 'largeur_veranda', 'Largeur façade', 'Quelle <strong>largeur façade</strong> ?', 'mm', NULL, NULL, NULL, 1],
        ['VERANDA', 5, 'profondeur_veranda', 'Profondeur', 'Quelle <strong>profondeur</strong> ?', 'mm', NULL, NULL, NULL, 1],
        ['VERANDA', 6, 'hauteur_acrotere', 'Hauteur acrotère', 'Hauteur de l\'<strong>acrotère/soubassement</strong> existant ?', 'mm', NULL, NULL, '💡 Si muret existant à habiller.', 0],
        ['VERANDA', 7, 'toiture_veranda', 'Type de toiture', 'Quel type de <strong>toiture</strong> ?', 'liste', '["Polycarbonate 32mm", "Double vitrage", "Panneaux sandwich isolés", "Mixte"]', NULL, NULL, 1],
        ['VERANDA', 8, 'ouvrants_veranda', 'Type d\'ouvrants', 'Quel type d\'<strong>ouvrants</strong> ?', 'liste', '["Coulissants", "Oscillo-battants", "Galandage", "Repliables"]', NULL, NULL, 1],
    ];

    // =====================
    // MOUSTIQUAIRE
    // =====================
    $moustiquaire = [
        ['MOUSTIQUAIRE', 2, 'type_moustiquaire', 'Type', 'Quel <strong>type de moustiquaire</strong> ?', 'liste', '["Enroulable verticale", "Enroulable latérale", "Cadre fixe", "Plissée"]', NULL, NULL, 1],
        ['MOUSTIQUAIRE', 3, 'largeur_moustiquaire', 'Largeur', 'Quelle <strong>largeur</strong> ?', 'mm', NULL, NULL, NULL, 1],
        ['MOUSTIQUAIRE', 4, 'hauteur_moustiquaire', 'Hauteur', 'Quelle <strong>hauteur</strong> ?', 'mm', NULL, NULL, NULL, 1],
        ['MOUSTIQUAIRE', 5, 'fixation_moust', 'Fixation', 'Quel type de <strong>fixation</strong> ?', 'liste', '["Pose en applique", "Pose en tableau", "Pose sur ouvrant"]', NULL, NULL, 1],
        ['MOUSTIQUAIRE', 6, 'coloris_moust', 'Coloris', 'Quel <strong>coloris</strong> ?', 'liste', '["Blanc", "Marron", "Gris Anthracite", "Noir"]', NULL, NULL, 1],
    ];

    // =====================
    // TAV (Travaux Annexes Vitre)
    // =====================
    $tav = [
        ['TAV', 2, 'type_tav', 'Type', 'Quel <strong>type de travail</strong> ?', 'liste', '["Bloc-porte intérieur", "Porte coulissante galandage", "Verrière atelier", "Placard/Dressing", "Escalier"]', NULL, NULL, 1],
        ['TAV', 3, 'largeur_tav', 'Largeur', 'Quelle <strong>largeur</strong> ?', 'mm', NULL, NULL, NULL, 1],
        ['TAV', 4, 'hauteur_tav', 'Hauteur', 'Quelle <strong>hauteur</strong> ?', 'mm', NULL, NULL, NULL, 1],
        ['TAV', 5, 'finition_tav', 'Finition', 'Quelle <strong>finition</strong> ?', 'liste', '["Laqué blanc", "Laqué noir", "Bois naturel", "Autre"]', NULL, NULL, 1],
    ];

    // Insert all
    $allEtapes = array_merge($garage, $pergola, $portail, $store, $veranda, $moustiquaire, $tav);
    
    foreach ($allEtapes as $e) {
        try {
            $stmt->execute($e);
            $inserted++;
        } catch (PDOException $ex) {
            // Duplicate, skip
        }
    }

    // Count total
    $total = $pdo->query("SELECT COUNT(*) FROM metrage_etapes")->fetchColumn();
    $perCat = $pdo->query("SELECT categorie, COUNT(*) as cnt FROM metrage_etapes GROUP BY categorie")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'inserted' => $inserted,
        'total' => $total,
        'per_category' => $perCat
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
