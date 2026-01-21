<?php
// install/seed_full_catalog.php
require_once __DIR__ . '/../db.php';

echo "<h2>Installation du Catalogue Métier Complet (8 Familles)</h2>";

// 1. ALTER TABLE STRUCTURE
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$pdo->exec("DROP TABLE IF EXISTS metrage_types");
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

$sql = "CREATE TABLE metrage_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    categorie VARCHAR(50), -- Keeping for backward compat (will map to famille)
    famille VARCHAR(50) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    has_motorisation TINYINT(1) DEFAULT 0,
    has_maconnerie TINYINT(1) DEFAULT 0,
    image_url VARCHAR(255) DEFAULT 'assets/img/types/default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$pdo->exec($sql);
echo "Table `metrage_types` recréée avec nouvelle structure.<br>";

// 2. DATASET
$products = [
    // 1. MENUISERIE EXTERIEURE (L'ENVELOPPE)
    ['Fenêtre Ouvrant Française', 'menuiserie', 'EXTERIEUR', 'fen_fr', 0, 1],
    ['Fenêtre Oscillo-Battant (OB)', 'menuiserie', 'EXTERIEUR', 'fen_ob', 0, 1],
    ['Coulissant Alu (2-6 Vtx)', 'menuiserie', 'EXTERIEUR', 'fen_coulissant', 0, 1],
    ['Galandage (Mono/Bi-rail)', 'menuiserie', 'EXTERIEUR', 'fen_galandage', 0, 1],
    ['Châssis Fixe / Pano', 'menuiserie', 'EXTERIEUR', 'fen_fixe', 0, 1],
    ['Soufflet / Vasistas', 'menuiserie', 'EXTERIEUR', 'fen_soufflet', 1, 1], // Motorisable parfois
    ['Forme (Cintre/Trapèze)', 'menuiserie', 'EXTERIEUR', 'fen_forme', 0, 1],
    ['Porte Entrée (Monobloc)', 'menuiserie', 'EXTERIEUR', 'porte_entree', 1, 1], // Serrure elec
    ['Porte Service / Cave', 'menuiserie', 'EXTERIEUR', 'porte_service', 0, 1],
    ['Porte Palière Blindée', 'menuiserie', 'EXTERIEUR', 'porte_blindee', 0, 1],
    ['Porte Repliable (Accordéon)', 'menuiserie', 'EXTERIEUR', 'porte_accordeon', 0, 1],

    // 2. FERMETURE (OCCULTATION)
    ['VR Rénovation (Coffre Ext)', 'volet', 'FERMETURE', 'vr_reno', 1, 0],
    ['VR Traditionnel (Tunnel)', 'volet', 'FERMETURE', 'vr_tradi', 1, 0],
    ['VR Bloc-Baie', 'volet', 'FERMETURE', 'vr_blocbaie', 1, 0],
    ['VR Solaire', 'volet', 'FERMETURE', 'vr_solaire', 1, 0],
    ['Volet Battant Plein', 'volet', 'FERMETURE', 'vb_plein', 1, 1], // Motorisable + Gonds
    ['Volet Battant Persienné', 'volet', 'FERMETURE', 'vb_persienne', 1, 1],
    ['Volet Coulissant Façade', 'volet', 'FERMETURE', 'v_coulissant', 1, 0],
    ['Persienne Repliable', 'volet', 'FERMETURE', 'persienne_repli', 0, 0],
    ['Jalousie Projection', 'volet', 'FERMETURE', 'jalousie_proj', 0, 0],
    ['Grille Défense Fixe', 'volet', 'FERMETURE', 'grille_fixe', 0, 1],
    ['Grille Ouvrante', 'volet', 'FERMETURE', 'grille_ouvrante', 0, 1],

    // 3. GARAGE & INDUSTRIEL
    ['Garage Sectionnelle Plafond', 'garage', 'GARAGE', 'garage_sect_plafond', 1, 1],
    ['Garage Sectionnelle Latérale', 'garage', 'GARAGE', 'garage_sect_lat', 1, 1],
    ['Garage Basculante', 'garage', 'GARAGE', 'garage_basculante', 1, 1],
    ['Garage Enroulable', 'garage', 'GARAGE', 'garage_enroulable', 1, 1],
    ['Garage Battante (Tradi)', 'garage', 'GARAGE', 'garage_battante', 0, 1],
    ['Rideau Métallique (Indus)', 'garage', 'GARAGE', 'rideau_metal', 1, 1],

    // 4. AMENAGEMENT EXT (CLOTURE)
    ['Portail Battant', 'portail', 'CLOTURE', 'portail_battant', 1, 1],
    ['Portail Coulissant', 'portail', 'CLOTURE', 'portail_coulissant', 1, 1],
    ['Portail Autoportant', 'portail', 'CLOTURE', 'portail_autoportant', 1, 1],
    ['Portillon Piéton', 'portail', 'CLOTURE', 'portillon', 1, 1],
    ['Clôture sur Muret', 'portail', 'CLOTURE', 'cloture_muret', 0, 1],
    ['Clôture Pleine Hauteur', 'portail', 'CLOTURE', 'cloture_pleine', 0, 1],
    ['Garde-Corps Droit', 'portail', 'CLOTURE', 'gc_droit', 0, 1],
    ['Garde-Corps Rampant', 'portail', 'CLOTURE', 'gc_rampant', 0, 1],

    // 5. PROTECTION SOLAIRE
    ['Store Banne (Coffre)', 'store', 'SOLAIRE', 'store_banne', 1, 1], // Fixation Chimique
    ['Store Vertical (Zip)', 'store', 'SOLAIRE', 'store_vertical', 1, 0],
    ['Store Projection', 'store', 'SOLAIRE', 'store_proj', 1, 0],
    ['BSO (Brise Soleil)', 'store', 'SOLAIRE', 'bso', 1, 0],
    ['Pergola Bioclimatique', 'pergola', 'SOLAIRE', 'pergola_bio', 1, 1],
    ['Pergola Toile', 'pergola', 'SOLAIRE', 'pergola_toile', 1, 1],
    ['Carport Alu', 'pergola', 'SOLAIRE', 'carport', 0, 1],

    // 6. INTERIEUR (NOUVEAU)
    ['Bloc-Porte Intérieur', 'tav', 'INTERIEUR', 'bp_int', 0, 0],
    ['Porte Coulissante Int', 'tav', 'INTERIEUR', 'porte_coul_int', 0, 0],
    ['Verrière Atelier', 'tav', 'INTERIEUR', 'verriere', 0, 0],
    ['Escalier', 'tav', 'INTERIEUR', 'escalier', 0, 0],
    ['Placard / Dressing', 'tav', 'INTERIEUR', 'placard', 0, 0],

    // 7. STRUCTURE
    ['Véranda', 'veranda', 'STRUCTURE', 'veranda_hab', 0, 1],
    ['Fermeture Loggia', 'veranda', 'STRUCTURE', 'loggia', 0, 0],
    ['SAS Entrée', 'veranda', 'STRUCTURE', 'sas_entree', 0, 1],

    // 8. MOUSTIQUAIRE
    ['Moustiquaire Enroulable', 'moustiquaire', 'MOUSTIQUAIRE', 'mously_vertical', 0, 0],
    ['Moustiquaire Latérale', 'moustiquaire', 'MOUSTIQUAIRE', 'moust_lateral', 0, 0],
    ['Moustiquaire Cadre Fixe', 'moustiquaire', 'MOUSTIQUAIRE', 'moust_fixe', 0, 0],
];

$stmt = $pdo->prepare("INSERT INTO metrage_types (nom, categorie, famille, slug, has_motorisation, has_maconnerie) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($products as $p) {
    try {
        $stmt->execute($p);
        echo "Insert OK: {$p[0]} ({$p[2]})<br>";
    } catch (PDOException $e) {
        echo "Error {$p[0]}: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>Catalogue Métier (45+ Produits) Installé avec succès !</h3>";
?>
