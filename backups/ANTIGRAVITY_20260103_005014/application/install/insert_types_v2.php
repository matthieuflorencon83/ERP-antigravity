<?php
/**
 * insert_types_v2.php - Insertion types (version corrigée)
 * Structure réelle : id, nom, categorie, famille, slug, has_motorisation, has_maconnerie, image_url
 */

require_once '../db.php';

$types = [
    // MENUISERIE
    ['Fenêtre PVC', 'menuiserie', 'fenetre', 'fenetre-pvc', 0, 0],
    ['Fenêtre Aluminium', 'menuiserie', 'fenetre', 'fenetre-alu', 0, 0],
    ['Porte-fenêtre', 'menuiserie', 'fenetre', 'porte-fenetre', 0, 0],
    ['Baie coulissante', 'menuiserie', 'baie', 'baie-coulissante', 0, 0],
    
    // GARAGE
    ['Porte Garage Sectionnelle', 'garage', 'porte', 'porte-garage-sectionnelle', 1, 0],
    ['Porte Garage Basculante', 'garage', 'porte', 'porte-garage-basculante', 1, 0],
    
    // PORTAIL
    ['Portail Coulissant', 'portail', 'portail', 'portail-coulissant', 1, 1],
    ['Portail Battant', 'portail', 'portail', 'portail-battant', 1, 1],
    
    // PERGOLA
    ['Pergola Bioclimatique', 'pergola', 'pergola', 'pergola-bioclimatique', 1, 1],
    ['Pergola Fixe', 'pergola', 'pergola', 'pergola-fixe', 0, 1],
    
    // STORE
    ['Store Banne', 'store', 'store', 'store-banne', 1, 0],
    ['Store Vertical', 'store', 'store', 'store-vertical', 1, 0],
    
    // VOLET
    ['Volet Roulant', 'volet', 'volet', 'volet-roulant', 1, 0],
    ['Volet Battant', 'volet', 'volet', 'volet-battant', 0, 0],
];

try {
    $pdo->beginTransaction();
    
    // Structure réelle : nom, categorie, famille, slug, has_motorisation, has_maconnerie
    $stmt = $pdo->prepare("
        INSERT INTO metrage_types 
        (nom, categorie, famille, slug, has_motorisation, has_maconnerie, image_url) 
        VALUES (?, ?, ?, ?, ?, ?, 'assets/img/types/default.png')
    ");
    
    $count = 0;
    foreach ($types as $type) {
        $stmt->execute($type);
        $count++;
    }
    
    $pdo->commit();
    
    echo "<h2>✅ Succès !</h2>";
    echo "<p>{$count} types de produits insérés.</p>";
    echo "<a href='../metrage_studio_v4.php' class='btn btn-primary'>→ Retour au Studio V4</a>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<h2>❌ Erreur</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
