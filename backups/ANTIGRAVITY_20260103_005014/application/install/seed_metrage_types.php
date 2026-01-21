<?php
require_once '../db.php';

try {
    echo "Seeding metrage_types...\n";
    
    // 1. TRUNCATE
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE metrage_types");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Table truncated.\n";
    
    // 2. DATA
    $types = [
        // MENUISERIE
        ['Menuiserie', 'Fenêtre 1 Vantail', 'fas fa-border-all', 'Sens ouverture, Hauteur allège'],
        ['Menuiserie', 'Fenêtre 2 Vantaux', 'far fa-window-maximize', 'Type ouverture, Hauteur allège'],
        ['Menuiserie', 'Porte-Fenêtre', 'fas fa-door-open', 'Seuil, Soubassement'],
        ['Menuiserie', 'Baie Coulissante', 'fas fa-columns', 'Nb rails, Nb vantaux'],
        ['Menuiserie', 'Porte d\'Entrée', 'fas fa-dungeon', 'Serrure, Tierce'],
        ['Menuiserie', 'Châssis Fixe', 'far fa-square', 'Type vitrage'],
        
        // FERMETURE
        ['Fermeture', 'Volet Roulant', 'fas fa-blinds', 'Type manœuvre, Type coffre'],
        ['Fermeture', 'Volet Battant', 'fas fa-door-closed', 'Nb vantaux, Matériau'],
        ['Fermeture', 'Porte de Garage', 'fas fa-warehouse', 'Sectionnelle/Basculante, Motorisation'],
        ['Fermeture', 'Portail', 'fas fa-torii-gate', 'Coulissant/Battant, Motorisation'],
        ['Fermeture', 'Grille de Défense', 'fas fa-th', 'Type fixation'],

        // PROTECTION SOLAIRE
        ['Protection Solaire', 'Store Banne', 'fas fa-umbrella-beach', 'Avancée, Type toile'],
        ['Protection Solaire', 'Pergola', 'fas fa-cloud-sun', 'Bioclimatique/Toile'],
        ['Protection Solaire', 'Moustiquaire', 'fas fa-border-none', 'Enroulable/Plissée'],
        
        // INTERIEUR / AUTRE
        ['Intérieur', 'Placard / Dressing', 'fas fa-tshirt', 'Aménagement intérieur'],
        ['Intérieur', 'Verrière', 'fas fa-crop-alt', 'Nb travées'],
        ['Autre', 'Garde-Corps', 'fas fa-ruler-horizontal', 'Type pose, Modèle'],
        ['Autre', 'Vitrage Seul', 'fas fa-glass-martini-alt', 'Composition'],
        ['Autre', 'Autre', 'fas fa-question', 'Préciser en notes']
    ];
    
    // 3. INSERT
    $stmt = $pdo->prepare("INSERT INTO metrage_types (categorie, nom, icone, description_technique) VALUES (?, ?, ?, ?)");
    
    foreach ($types as $t) {
        $stmt->execute($t);
        echo "Inserted: {$t[1]}\n";
    }
    
    echo "Done! " . count($types) . " items inserted.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
