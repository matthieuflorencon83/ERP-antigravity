<?php
// install/add_more_etapes.php
// Add more detailed criteria per product type
require_once '../db.php';

header('Content-Type: application/json');

try {
    // Additional criteria for FENETRE
    $fenetre_extras = [
        ['FENETRE', 16, 'nombre_vantaux', 'Nombre de vantaux',
         'Combien de <strong>vantaux</strong> (parties ouvrantes) ?',
         'liste', '["1 vantail", "2 vantaux", "3 vantaux", "4 vantaux"]', NULL, 
         '💡 Standard: 1 vantail jusqu\'à 800mm, 2 vantaux au-delà.', 1],
        
        ['FENETRE', 17, 'sens_ouvrant', 'Sens d\'ouverture',
         'Quel est le <strong>sens d\'ouverture</strong> ?',
         'liste', '["Gauche (tirant gauche)", "Droite (tirant droit)", "Oscillo-battant", "À soufflet"]', 
         NULL, '⚠️ Vue de l\'INTÉRIEUR pour les fenêtres !', 1],
        
        ['FENETRE', 18, 'allege', 'Hauteur d\'allège',
         'Quelle est la <strong>hauteur d\'allège</strong> (du sol au bas de la fenêtre) ?',
         'mm', NULL, NULL, 
         '⚠️ Sécurité enfant: si < 900mm, prévoir garde-corps ou vitrage sécurit.', 0],
        
        ['FENETRE', 19, 'volet_associe', 'Volet associé',
         'Y a-t-il un <strong>volet</strong> à prévoir avec cette fenêtre ?',
         'liste', '["Non", "Volet roulant", "Volet battant", "Persiennes"]', 
         NULL, '💡 Si oui, les dimensions seront reprises automatiquement.', 0],
        
        ['FENETRE', 20, 'store_associe', 'Store associé',
         'Y a-t-il un <strong>store</strong> à prévoir ?',
         'liste', '["Non", "Store intérieur", "Store extérieur", "Brise-soleil"]', 
         NULL, NULL, 0],
    ];

    // Additional criteria for VOLET
    $volet_extras = [
        ['VOLET', 9, 'coulisse', 'Type de coulisse',
         'Quel type de <strong>coulisse</strong> ?',
         'liste', '["Coulisse alu standard", "Coulisse PVC", "Coulisse compacte", "Sans coulisse (intégrée)"]', 
         NULL, NULL, 1],
        
        ['VOLET', 10, 'lame_finale', 'Type de lame finale',
         'Quel type de <strong>lame finale</strong> ?',
         'liste', '["Standard", "Renforcée anti-effraction", "Ajourée"]', 
         NULL, '💡 Lame renforcée recommandée en RDC.', 1],
    ];

    // Additional criteria for PORTE
    $porte_extras = [
        ['PORTE', 10, 'tierce', 'Tierce / Semi-fixe',
         'Y a-t-il une <strong>tierce</strong> (partie fixe latérale) ?',
         'liste', '["Non", "Tierce à gauche", "Tierce à droite", "Tierce des deux côtés"]', 
         NULL, '💡 Tierce = partie fixe vitrée ou pleine à côté de la porte.', 0],
        
        ['PORTE', 11, 'imposte_porte', 'Imposte',
         'Y a-t-il une <strong>imposte</strong> (partie fixe au-dessus) ?',
         'liste', '["Non", "Imposte vitrée", "Imposte pleine"]', 
         NULL, NULL, 0],
        
        ['PORTE', 12, 'bequille', 'Type de béquille',
         'Quel type de <strong>béquille</strong> (poignée) ?',
         'liste', '["Béquille standard inox", "Béquille design", "Bouton fixe extérieur", "Barre de tirage"]', 
         NULL, NULL, 0],
        
        ['PORTE', 13, 'grille_aeration', 'Grille d\'aération',
         'Y a-t-il besoin d\'une <strong>grille d\'aération</strong> ?',
         'binaire', '["Oui", "Non"]', 
         NULL, '⚠️ Obligatoire pour les portes de chaufferie ou local technique.', 0],
    ];

    // Insert all extras
    $sql = "INSERT INTO metrage_etapes (categorie, ordre, code_etape, nom_etape, message_assistant, type_saisie, options_json, schema_url, rappel, est_obligatoire) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $inserted = 0;

    foreach (array_merge($fenetre_extras, $volet_extras, $porte_extras) as $e) {
        try {
            $stmt->execute($e);
            $inserted++;
        } catch (PDOException $ex) {
            // Ignore duplicates
        }
    }

    // Count total
    $total = $pdo->query("SELECT COUNT(*) FROM metrage_etapes")->fetchColumn();
    
    // Count per category
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
