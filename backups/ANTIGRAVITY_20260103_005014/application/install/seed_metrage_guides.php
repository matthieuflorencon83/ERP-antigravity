<?php
// install/seed_metrage_guides.php
require_once __DIR__ . '/../db.php';

echo "<h2>Installation de l'Encyclopédie Visuelle (Guides Métrage)</h2>";

// 1. CREATE TABLE
$sql = "CREATE TABLE IF NOT EXISTS metrage_guides_full (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_family VARCHAR(50) NOT NULL,
    trigger_input_id VARCHAR(100) NOT NULL, -- The HTML ID or Name part
    image_filename VARCHAR(100) NOT NULL,
    titre VARCHAR(100) NOT NULL,
    texte_conseil TEXT,
    INDEX (trigger_input_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$pdo->exec($sql);
echo "Table `metrage_guides_full` créée/vérifiée.<br>";

// 2. TRUNCATE
$pdo->exec("TRUNCATE TABLE metrage_guides_full");

// 3. SEED DATA
$guides = [
    // --- MENUISERIE ---
    ['MENU', 'pose_reno', 'men_type_reno_dormant.svg', 'Pose en Rénovation', 'Vérifiez de sondage du bois ancien. La nouvelle fenêtre recouvre l\'ancien dormant.'],
    ['MENU', 'pose_depose', 'men_type_depose_totale.svg', 'Dépose Totale', 'Attention : vérifiez la profondeur de la feuillure béton mise à nu.'],
    ['MENU', 'pose_applique', 'men_type_neuf_isol.svg', 'Pose en Applique (Neuf)', 'Vérifiez l\'épaisseur totale doublage + placo pour commander la bonne tapée.'],
    ['MENU', 'pose_tunnel', 'men_type_tunnel.svg', 'Pose en Tunnel', 'Mesurez bien entre tableaux finis. Déduisez le jeu de pose.'],
    
    ['MENU', 'aile_reno', 'men_detail_aile_recouvrement.svg', 'Aile de Recouvrement', 'Mesurez l\'épaisseur visible du dormant. 27 std, 40 large, 70 XXL.'],
    ['MENU', 'hauteur_rejingot', 'men_detail_rejingot_beton.svg', 'Rejingot Maçonnerie', 'Le rejingot est la pièce d\'appui béton. Vérifiez sa pente et son état.'],
    ['MENU', 'largeur_tableau', 'men_cote_tableau_3pts.svg', 'Largeur Tableau', 'Prendre la cote en haut, milieu, bas. Retenir la plus petite.'],
    ['MENU', 'hauteur_tableau', 'men_cote_tableau_3pts.svg', 'Hauteur Tableau', 'Prendre la cote à gauche, milieu, droite. Retenir la plus petite.'],
    ['MENU', 'ht_poignee', 'men_cote_allege.svg', 'Hauteur Poignée / Allège', 'Attention normes PMR (max 1.30m) ou Garde-Corps (min 1m).'],

    // --- OCCULTATION (Volet/Store) ---
    ['OCCULT', 'pose_facade', 'vr_coffre_reno_ext.svg', 'Pose Façade (Ext)', 'Le coffre est visible à l\'extérieur, au-dessus de la fenêtre.'],
    ['OCCULT', 'pose_plafond', 'vr_coffre_tunnel_int.svg', 'Pose Plafond (Sous-Face)', 'Fixation sous dalle ou sous linteau.'],
    ['OCCULT', 'support', 'store_fixation_ite.svg', 'Support ITE', 'Attention : Isolation Extérieure = Scellement Chimique + Tamis Longs obligatoire.'],
    ['OCCULT', 'hauteur_ht', 'store_encombrement_coffre.svg', 'Encombrement Coffre', 'Vérifiez l\'espace disponible au-dessus de la fenêtre (min 250mm).'],
    ['OCCULT', 'check_poignee', 'schema_obstacle_poignee.svg', 'Obstacle Poignée', 'Si la poignée dépasse, le tablier risque de bloquer. Prévoyez coulisses écartées.'],
    ['OCCULT', 'coté_alim', 'schema_elec_gauche_droite.svg', 'Côté Moteur (Vue INT)', 'Toujours définir le côté moteur Vue Intérieure (Gauche ou Droite).'],
    
    // --- PORTAIL ---
    ['PORTAIL', 'largeur_pilier', 'portail_pilier_banane.svg', 'Largeur Entre Piliers', 'Mesurez Haut, Milieu, Bas. Attention aux piliers "bananés".'],
    ['PORTAIL', 'pente', 'portail_pente_seuil.svg', 'Pente du Seuil', 'Vérifiez la pente de seuil et de l\'allée pour ouverture battante.'],
    ['PORTAIL', 'refoulement', 'portail_refoulement_coulissant.svg', 'Refoulement Coulissant', 'Avez-vous la place pour dégager tout le portail + la queue de guidage ?'],
    ['PORTAIL', 'gond', 'portail_gond_regilux.svg', 'Gonds Existants', 'Vérifiez l\'alignement vertical des gonds existants.'],
    ['PORTAIL', 'chapeau', 'portail_cote_chapeau.svg', 'Hauteur sous Chapeau', 'Vérifiez la cote sous chapeau pilier pour installer les gonds hauts.'],

    // --- GARAGE ---
    ['GARAGE', 'retombee', 'garage_retombee_linteau.svg', 'Retombée de Linteau', 'Espace entre haut baie et plafond. Min 200mm pour moteur standard.'],
    ['GARAGE', 'ecoincon', 'garage_ecoincons.svg', 'Écoinçons G/D', 'Espace latéral requis pour les coulisses verticales (min 100mm).'],
    ['GARAGE', 'seuil', 'garage_seuil_joint.svg', 'Niveau du Seuil', 'Le sol est-il de niveau pour l\'étanchéité du joint boudin ?'],
    ['GARAGE', 'profondeur', 'garage_profondeur_rail.svg', 'Profondeur Plafond', 'Avez-vous la profondeur pour les rails horizontaux ? (Hauteur + 500mm).'],

    // --- VERANDA / PERGOLA ---
    ['VERANDA', 'equerrage', 'veranda_dalle_equerrage.svg', 'Équerrage Dalle', 'Les diagonales A et B doivent être IDENTIQUES. Tolérance 10mm.'],
    ['VERANDA', 'solin', 'veranda_solin_facade.svg', 'Solin Façade', 'Saignée dans crépi nécessaire ? État du support mural ?'],
    ['VERANDA', 'pente_toit', 'veranda_pente_minimale.svg', 'Pente Toiture', 'Pente minimale requise (5% polycarbonate, 15% tuile/verre).'],
];

$stmt = $pdo->prepare("INSERT INTO metrage_guides_full (produit_family, trigger_input_id, image_filename, titre, texte_conseil) VALUES (?, ?, ?, ?, ?)");

foreach ($guides as $g) {
    try {
        $stmt->execute($g);
        echo "Insert OK: {$g[1]} -> {$g[2]}<br>";
    } catch (PDOException $e) {
        echo "Erreur Insert {$g[1]}: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>Terminé ! " . count($guides) . " guides injectés.</h3>";
?>
