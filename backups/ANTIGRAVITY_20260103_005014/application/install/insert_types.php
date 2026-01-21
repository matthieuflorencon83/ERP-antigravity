<?php
/**
 * insert_types.php - Insertion rapide des types de produits
 * À exécuter une seule fois : http://localhost/antigravity/install/insert_types.php
 */

require_once '../db.php';

$types = [
    // MENUISERIE
    ['fenetre-pvc', 'Fenêtre PVC', 'menuiserie'],
    ['fenetre-alu', 'Fenêtre Aluminium', 'menuiserie'],
    ['porte-fenetre', 'Porte-fenêtre', 'menuiserie'],
    ['baie-coulissante', 'Baie coulissante', 'menuiserie'],
    
    // GARAGE
    ['porte-garage-sectionnelle', 'Porte Garage Sectionnelle', 'garage'],
    ['porte-garage-basculante', 'Porte Garage Basculante', 'garage'],
    
    // PORTAIL
    ['portail-coulissant', 'Portail Coulissant', 'portail'],
    ['portail-battant', 'Portail Battant', 'portail'],
    
    // PERGOLA
    ['pergola-bioclimatique', 'Pergola Bioclimatique', 'pergola'],
    ['pergola-fixe', 'Pergola Fixe', 'pergola'],
    
    // STORE
    ['store-banne', 'Store Banne', 'store'],
    ['store-vertical', 'Store Vertical', 'store'],
    
    // VOLET
    ['volet-roulant', 'Volet Roulant', 'volet'],
    ['volet-battant', 'Volet Battant', 'volet'],
];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO metrage_types (slug, nom, categorie, actif) VALUES (?, ?, ?, TRUE)");
    
    $count = 0;
    foreach ($types as $type) {
        $stmt->execute($type);
        $count++;
    }
    
    $pdo->commit();
    
    echo "✅ {$count} types de produits insérés avec succès !<br><br>";
    echo "<a href='../metrage_studio_v4.php'>→ Retour au Studio V4</a>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Erreur : " . $e->getMessage();
}
