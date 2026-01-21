<?php
require_once '../db.php';

try {
    echo "Seeding metrage_points_controle...\n";
    
    // 1. TRUNCATE
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE metrage_points_controle");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Table truncated.\n";

    // Helper to get Type ID
    function getTypeId($pdo, $name) {
        $stmt = $pdo->prepare("SELECT id FROM metrage_types WHERE nom = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }

    // Helper to Insert Point
    function addPoint($pdo, $type_name, $label, $type_saisie, $options = [], $obligatoire = 0, $aide = '', $ordre = 0, $image = '') {
        $tid = getTypeId($pdo, $type_name);
        if (!$tid) { echo "Warning: Type '$type_name' not found.\n"; return; }
        
        $sql = "INSERT INTO metrage_points_controle (metrage_type_id, label, type_saisie, options_liste, is_obligatoire, message_aide, ordre, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$tid, $label, $type_saisie, json_encode($options), $obligatoire, $aide, $ordre, $image]);
    }

    // --- DEFINITIONS ---

    // 1. FENETRE 1 VANTAIL / 2 VANTAUX / PORTE-FENETRE
    $menuiseries = ['Fenêtre 1 Vantail', 'Fenêtre 2 Vantaux', 'Porte-Fenêtre', 'Châssis Fixe'];
    foreach ($menuiseries as $m) {
        addPoint($pdo, $m, 'Largeur Tableau (mm)', 'mm', [], 1, 'Prendre la côte en 3 points (Haut, Milieu, Bas) et retenir la plus petite.', 10);
        addPoint($pdo, $m, 'Hauteur Tableau (mm)', 'mm', [], 1, 'Prendre la côte en 3 points (Gauche, Milieu, Droite) et retenir la plus petite.', 20);
        addPoint($pdo, $m, 'Type de Pose', 'liste', ['Applique', 'Tunnel', 'Feuillure', 'Rénovation'], 1, '', 30);
        addPoint($pdo, $m, 'Couleur', 'liste', ['Blanc 9016', 'Gris 7016', 'Chêne doré', 'Autre'], 0, '', 40);
        addPoint($pdo, $m, 'Vitrage', 'liste', ['Double 4/16/4', 'Phonique', 'Sécurité', 'Triple'], 0, '', 50);
        addPoint($pdo, $m, 'Volet Roulant Intégré ?', 'choix_binaire', [], 0, '', 60);
        addPoint($pdo, $m, 'Photo Intérieur', 'photo', [], 0, '', 70);
        addPoint($pdo, $m, 'Photo Extérieur', 'photo', [], 0, '', 80);
    }

    // 2. BAIE COULISSANTE
    addPoint($pdo, 'Baie Coulissante', 'Largeur Tableau (mm)', 'mm', [], 1, '', 10);
    addPoint($pdo, 'Baie Coulissante', 'Hauteur Tableau (mm)', 'mm', [], 1, '', 20);
    addPoint($pdo, 'Baie Coulissante', 'Nombres de Vantaux', 'liste', ['2 Vantaux', '3 Vantaux', '4 Vantaux'], 1, '', 30);
    addPoint($pdo, 'Baie Coulissante', 'Nombres de Rails', 'liste', ['2 Rails', '3 Rails'], 1, '', 40);
    addPoint($pdo, 'Baie Coulissante', 'Type de Pose', 'liste', ['Applique', 'Tunnel', 'Rénovation'], 1, '', 50);
    addPoint($pdo, 'Baie Coulissante', 'Photo Situation', 'photo', [], 1, '', 60);

    // 3. PORTE D'ENTREE
    addPoint($pdo, "Porte d'Entrée", 'Largeur Passage (mm)', 'mm', [], 1, '', 10);
    addPoint($pdo, "Porte d'Entrée", 'Hauteur Passage (mm)', 'mm', [], 1, '', 20);
    addPoint($pdo, "Porte d'Entrée", 'Sens d\'ouverture', 'liste', ['Tirant Gauche', 'Tirant Droite', 'Poussant Gauche', 'Poussant Droite'], 1, 'Vue de l\'intérieur', 30);
    addPoint($pdo, "Porte d'Entrée", 'Type de Seuil', 'liste', ['Alu PMR 20mm', 'Standard 40mm', 'Sans seuil'], 0, '', 40);
    addPoint($pdo, "Porte d'Entrée", 'Photo Seuil Actuel', 'photo', [], 1, 'Zoom sur le rejet d\'eau', 50);

    // 4. VOLET ROULANT
    addPoint($pdo, 'Volet Roulant', 'Largeur Tableau (mm)', 'mm', [], 1, '', 10);
    addPoint($pdo, 'Volet Roulant', 'Hauteur Tableau (mm)', 'mm', [], 1, '', 20);
    addPoint($pdo, 'Volet Roulant', 'Type de Manœuvre', 'liste', ['Radio Solaire', 'Radio Filaire', 'Sangle', 'Tirage Direct'], 1, '', 30);
    addPoint($pdo, 'Volet Roulant', 'Type de Pose', 'liste', ['Enroulement Extérieur', 'Enroulement Intérieur', 'Sous Linteau'], 1, '', 40);
    addPoint($pdo, 'Volet Roulant', 'Couleur Tablier', 'liste', ['Blanc', 'Gris Anthracite', 'Gris Alu'], 0, '', 50);

    // 5. VOLET BATTANT
    addPoint($pdo, 'Volet Battant', 'Largeur Tableau (mm)', 'mm', [], 1, '', 10);
    addPoint($pdo, 'Volet Battant', 'Hauteur Tableau (mm)', 'mm', [], 1, '', 20);
    addPoint($pdo, 'Volet Battant', 'Nombre de Vantaux', 'liste', ['1 Vantail', '2 Vantaux', '3 Vantaux', '4 Vantaux'], 1, '', 30);
    addPoint($pdo, 'Volet Battant', 'Matériau', 'liste', ['Alu Isolé', 'Alu Extrudé', 'Bois', 'PVC'], 1, '', 40);
    addPoint($pdo, 'Volet Battant', 'Type de Gonds', 'liste', ['Chimique', 'Scellement', 'Gonds existants'], 1, '', 50);
    addPoint($pdo, 'Volet Battant', 'Photo Gonds', 'photo', [], 1, 'Important pour vérifier l\'état', 60);

    // 6. PORTE DE GARAGE
    addPoint($pdo, 'Porte de Garage', 'Largeur Baie (mm)', 'mm', [], 1, '', 10);
    addPoint($pdo, 'Porte de Garage', 'Hauteur Baie (mm)', 'mm', [], 1, '', 20);
    addPoint($pdo, 'Porte de Garage', 'Retombée de Linteau (mm)', 'mm', [], 1, 'Espace disponible au dessus de l\'ouverture', 30);
    addPoint($pdo, 'Porte de Garage', 'Ecoinçon Gauche (mm)', 'mm', [], 1, '', 40);
    addPoint($pdo, 'Porte de Garage', 'Ecoinçon Droit (mm)', 'mm', [], 1, '', 50);
    addPoint($pdo, 'Porte de Garage', 'Type Ouverture', 'liste', ['Sectionnelle Plafond', 'Sectionnelle Latérale', 'Basculante', 'Enroulable'], 1, '', 60);
    addPoint($pdo, 'Porte de Garage', 'Présence Prise Courant ?', 'choix_binaire', [], 1, 'Pour le moteur', 70);

    // 7. PORTAIL
    addPoint($pdo, 'Portail', 'Largeur Entre Piliers (mm)', 'mm', [], 1, '', 10);
    addPoint($pdo, 'Portail', 'Hauteur Piliers (mm)', 'mm', [], 1, '', 20);
    addPoint($pdo, 'Portail', 'Type Ouverture', 'liste', ['Battant', 'Coulissant'], 1, '', 30);
    addPoint($pdo, 'Portail', 'Si Coulissant : Longueur Refoulement (mm)', 'mm', [], 0, 'Espace disponible pour l\'ouverture', 40);
    addPoint($pdo, 'Portail', 'Motorisation ?', 'choix_binaire', [], 1, '', 50);
    addPoint($pdo, 'Portail', 'Photo Piliers', 'photo', [], 1, '', 60);

    // 8. STORE BANNE
    addPoint($pdo, 'Store Banne', 'Largeur de Face (mm)', 'mm', [], 1, '', 10);
    addPoint($pdo, 'Store Banne', 'Avancée (mm)', 'liste', ['2000', '2500', '3000', '3500', '4000'], 1, '', 20);
    addPoint($pdo, 'Store Banne', 'Support de Pose', 'liste', ['Béton', 'Bois', 'Pierre'], 1, '', 30);
    addPoint($pdo, 'Store Banne', 'Côté Manœuvre/Moteur', 'liste', ['Gauche', 'Droite'], 1, 'Vue de face extérieure', 40);
    addPoint($pdo, 'Store Banne', 'Photo Façade', 'photo', [], 1, '', 50);

    // 9. PLACARD
    addPoint($pdo, 'Placard / Dressing', 'Largeur Niche (mm)', 'mm', [], 1, '', 10);
    addPoint($pdo, 'Placard / Dressing', 'Hauteur Sol-Plafond (mm)', 'mm', [], 1, '', 20);
    addPoint($pdo, 'Placard / Dressing', 'Type de Façade', 'liste', ['Coulissante', 'Pivotante', 'Pliante'], 1, '', 30);
    addPoint($pdo, 'Placard / Dressing', 'Aménagement Intérieur', 'texte', [], 0, 'Décrire (Tablettes, Penderie...)', 40);

    echo "Detailed seeding completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
