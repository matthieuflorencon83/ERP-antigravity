<?php
// tools/seed_20_articles.php
require_once __DIR__ . '/../db.php';

// Get IDs for relations
$fournisseurs = $pdo->query("SELECT id FROM fournisseurs LIMIT 1")->fetchColumn();
$finitions = $pdo->query("SELECT id FROM finitions")->fetchAll(PDO::FETCH_COLUMN);

$articles = [
    // PROFILÉS ALU
    ['P-CHV-60-7016', 'Chevron Alu 60x40mm RAL 7016', 'PROFIL', 'Chevrons', $fournisseurs, 'CHV-60-7016', 52.50, 'U', 1.850, 6500, '7016', null, 15.00, 5.00],
    ['P-CHV-80-9005', 'Chevron Alu 80x50mm RAL 9005', 'PROFIL', 'Chevrons', $fournisseurs, 'CHV-80-9005', 68.90, 'U', 2.450, 6500, '9005', null, 8.00, 5.00],
    ['P-POT-100-7016', 'Poteau Alu 100x60mm RAL 7016', 'PROFIL', 'Poteaux', $fournisseurs, 'POT-100-7016', 89.00, 'U', 3.200, 6500, '7016', null, 12.00, 3.00],
    ['P-POT-120-9016', 'Poteau Alu 120x80mm RAL 9016', 'PROFIL', 'Poteaux', $fournisseurs, 'POT-120-9016', 125.00, 'U', 4.100, 6500, '9016', null, 6.00, 3.00],
    ['P-TRV-50-7016', 'Traverse Alu 50x30mm RAL 7016', 'PROFIL', 'Traverses', $fournisseurs, 'TRV-50-7016', 38.50, 'U', 1.200, 6500, '7016', null, 20.00, 10.00],
    ['P-TRV-70-1247', 'Traverse Alu 70x40mm Bronze', 'PROFIL', 'Traverses', $fournisseurs, 'TRV-70-1247', 55.00, 'U', 1.650, 6500, '1247', null, 10.00, 5.00],
    ['P-SAB-80-7016', 'Sablière Alu 80x60mm RAL 7016', 'PROFIL', 'Sablières', $fournisseurs, 'SAB-80-7016', 72.00, 'U', 2.800, 6500, '7016', null, 8.00, 5.00],
    ['P-SAB-100-9005', 'Sablière Alu 100x80mm RAL 9005', 'PROFIL', 'Sablières', $fournisseurs, 'SAB-100-9005', 98.50, 'U', 3.600, 6500, '9005', null, 5.00, 3.00],
    ['P-CAP-45-7016', 'Capot Alu 45mm RAL 7016', 'PROFIL', 'Capots', $fournisseurs, 'CAP-45-7016', 28.00, 'U', 0.850, 6500, '7016', null, 25.00, 10.00],
    ['P-CAP-60-9016', 'Capot Alu 60mm RAL 9016', 'PROFIL', 'Capots', $fournisseurs, 'CAP-60-9016', 35.00, 'U', 1.100, 6500, '9016', null, 18.00, 10.00],
    
    // TÔLES & ACCESSOIRES
    ['T-BAC-0.75-7016', 'Tôle Bac Acier 0.75mm RAL 7016', 'PROFIL', '', $fournisseurs, 'BAC-075-7016', 18.50, 'M2', 5.800, null, '7016', null, 120.00, 20.00],
    ['T-BAC-1.00-9005', 'Tôle Bac Acier 1.00mm RAL 9005', 'PROFIL', '', $fournisseurs, 'BAC-100-9005', 22.00, 'M2', 7.850, null, '9005', null, 80.00, 20.00],
    ['A-VIS-6x80', 'Vis Autoperceuse 6.3x80mm', 'PROFIL', '', $fournisseurs, 'VIS-6x80', 0.15, 'U', 0.012, null, null, null, 5000.00, 500.00],
    ['A-RIV-4.8', 'Rivet Alu 4.8mm', 'PROFIL', '', $fournisseurs, 'RIV-4.8', 0.08, 'U', 0.005, null, null, null, 8000.00, 1000.00],
    ['A-JNT-EPDM-10', 'Joint EPDM 10mm Noir', 'PROFIL', '', $fournisseurs, 'JNT-EPDM-10', 3.50, 'ML', 0.045, null, null, null, 250.00, 50.00],
    
    // VITRAGES (fictifs pour test)
    ['V-44.2-CLAIR', 'Vitrage 44.2 Feuilleté Clair', 'VITRAGE', '', $fournisseurs, 'VIT-44.2', 85.00, 'M2', 20.000, null, null, null, 15.00, 5.00],
    ['V-66.2-CLAIR', 'Vitrage 66.2 Feuilleté Clair', 'VITRAGE', '', $fournisseurs, 'VIT-66.2', 125.00, 'M2', 30.000, null, null, null, 10.00, 3.00],
    
    // PANNEAUX
    ['PN-SAND-40-7016', 'Panneau Sandwich 40mm RAL 7016', 'PROFIL', '', $fournisseurs, 'SAND-40-7016', 45.00, 'M2', 8.500, null, '7016', null, 50.00, 10.00],
    ['PN-SAND-60-9005', 'Panneau Sandwich 60mm RAL 9005', 'PROFIL', '', $fournisseurs, 'SAND-60-9005', 58.00, 'M2', 11.200, null, '9005', null, 35.00, 10.00],
    ['PN-POLY-16-TRANSP', 'Polycarbonate 16mm Transparent', 'PROFIL', '', $fournisseurs, 'POLY-16', 32.00, 'M2', 2.800, null, null, null, 40.00, 10.00],
];

$stmt = $pdo->prepare("INSERT INTO articles 
    (reference_interne, designation, famille, sous_famille, fournisseur_prefere_id, ref_fournisseur, 
     prix_achat_ht, unite_stock, poids_kg, longueur_barre, couleur_ral, image_path, stock_actuel, seuil_alerte_stock) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$count = 0;
foreach($articles as $a) {
    try {
        $stmt->execute($a);
        $count++;
        echo "✓ {$a[1]}<br>";
    } catch(PDOException $e) {
        echo "✗ {$a[1]}: {$e->getMessage()}<br>";
    }
}

echo "<hr><strong>Total inséré: $count / " . count($articles) . "</strong>";
