<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/tcpdf/tcpdf.php';

// 1. Get ID
$id = $_GET['id'] ?? 0;
if (!$id) die("ID Manquant");

// 2. Fetch Data
try {
    $stmt = $pdo->prepare("SELECT * FROM metrage_lignes WHERE id = ?");
    $stmt->execute([$id]);
    $metrage = $stmt->fetch();
    if (!$metrage) die("Méthage introuvable");
} catch (Exception $e) {
    die("Erreur DB: " . $e->getMessage());
}

// 3. Decode JSON
$data = json_decode($metrage['details_json'], true);

// 4. Init TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Antigravity ERP');
$pdf->SetAuthor('Antigravity');
$pdf->SetTitle('Fiche Métrage Technique #' . $id);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// 5. Styles
$style = '
<style>
    h1 { color: #333; font-size: 20px; font-weight: bold; border-bottom: 2px solid #333; text-align: center; }
    h2 { color: #555; font-size: 14px; font-weight: bold; margin-top: 10px; background-color: #f0f0f0; padding: 5px; }
    table { width: 100%; border-collapse: collapse; }
    th { width: 40%; font-weight: bold; text-align: right; padding: 5px; color: #666; }
    td { width: 60%; padding: 5px; border-bottom: 1px solid #ddd; }
    .alert { color: red; font-weight: bold; }
    .img-container { text-align: center; margin-top: 10px; }
</style>
';

// 6. Build HTML Content
$html = $style;
$html .= '<h1>FICHE MÉTRAGE TECHNIQUE #' . $id . '</h1>';
$html .= '<p align="center">Date : ' . date('d/m/Y H:i') . ' | Ouvrage : <b>' . htmlspecialchars($metrage['type_ouvrage']) . '</b></p>';

// --- DIMENSIONS & GEOMETRY ---
$html .= '<h2>1. DIMENSIONS & GÉOMÉTRIE</h2>';
$html .= '<table>';

$dims = $data['dimensions'] ?? [];
$geo = $data['geometrie'] ?? []; // Assuming JSON structure

// Shape display
$shapeName = 'Rectangle Standard';
if (isset($data['form_fields']['forme_type']) && $data['form_fields']['forme_type'] === 'SPECIAL') {
    $shapeName = $data['form_fields']['forme_subtype'] ?? 'Spéciale';
    $html .= '<tr><th>Forme Spéciale :</th><td class="alert">' . $shapeName . '</td></tr>';
} else {
    $html .= '<tr><th>Forme :</th><td>Rectangle</td></tr>';
}

// Extract Dims from Fields if not in main structure
$fields = $data['form_fields'] ?? [];
$w = $fields['largeur_tableau'] ?? '-';
$h = $fields['hauteur_tableau'] ?? '-';

$html .= '<tr><th>Largeur Tableau :</th><td>' . $w . ' mm</td></tr>';
$html .= '<tr><th>Hauteur Tableau :</th><td>' . $h . ' mm</td></tr>';

// Special Dims
if ($shapeName !== 'Rectangle Standard') {
    if (isset($fields['cote_h1'])) $html .= '<tr><th>Hauteur H1 (Petit) :</th><td>' . $fields['cote_h1'] . ' mm</td></tr>';
    if (isset($fields['cote_h2'])) $html .= '<tr><th>Hauteur H2 (Grand) :</th><td>' . $fields['cote_h2'] . ' mm</td></tr>';
    if (isset($fields['fleche'])) $html .= '<tr><th>Flèche Cintre :</th><td>' . $fields['fleche'] . ' mm</td></tr>';
}
$html .= '</table>';

// --- POSE & CONTEXT ---
$html .= '<h2>2. CONTEXTE DE POSE</h2>';
$html .= '<table>';
$html .= '<tr><th>Type de Pose :</th><td>' . ($fields['pose_type'] ?? 'Non défini') . '</td></tr>';
if (isset($fields['aile_reno'])) $html .= '<tr><th>Aile Recouvrement :</th><td>' . $fields['aile_reno'] . ' mm</td></tr>';
if (isset($fields['larg_dormant_existant'])) {
    $html .= '<tr><th>Dormant Existant :</th><td>' . $fields['larg_dormant_existant'] . ' mm</td></tr>';
    if ($fields['larg_dormant_existant'] > ($fields['aile_reno'] ?? 0)) {
         $html .= '<tr><th>ALERTE ESTHÉTIQUE :</th><td class="alert">Dormant > Aile !!</td></tr>';
    }
}
$html .= '</table>';

// --- TECHNIQUE & FINISHES ---
$html .= '<h2>3. DÉTAILS TECHNIQUES</h2>';
$html .= '<table>';
if (isset($fields['vmc']) && $fields['vmc'] === 'OUI') {
    $html .= '<tr><th>VMC :</th><td>OUI</td></tr>';
    $html .= '<tr><th>Couleur Grille :</th><td>' . ($fields['vmc_couleur'] ?? '-') . '</td></tr>';
    if (isset($fields['vmc_debit_ref'])) $html .= '<tr><th>Référence Grille :</th><td>' . $fields['vmc_debit_ref'] . '</td></tr>';
} else {
    $html .= '<tr><th>VMC :</th><td>NON</td></tr>';
}

if (isset($fields['seuil_type'])) $html .= '<tr><th>Type de Seuil :</th><td>' . $fields['seuil_type'] . '</td></tr>';

// Obstacles
if (!empty($fields['obstacle_plinthe'])) $html .= '<tr><th>Obstacle Plinthe :</th><td class="alert">Oui (' . $fields['obstacle_plinthe'] . ' mm) - Prévoir Elargisseur</td></tr>';
if (!empty($fields['obstacle_radiateur'])) $html .= '<tr><th>Obstacle Radiateur :</th><td class="alert">Oui (' . $fields['obstacle_radiateur'] . ' mm) - Vérifier Ouverture</td></tr>';

$html .= '</table>';

// --- PHOTOS ---
// Assuming photos are locally stored or accessible via HTTP usually. TCPDF needs local path or reachable URL.
// We'll list them as text for now if paths are complex, or try to embed if they are in 'uploads/'.
$html .= '<h2>4. PHOTOS & FICHIERS</h2>';
$html .= '<ul>';
// Example logic - adapt to actual JSON structure for media
if (isset($data['media']) && is_array($data['media'])) {
    foreach($data['media'] as $key => $path) {
        $html .= '<li>' . $key . ': ' . basename($path) . '</li>';
    }
}
$html .= '</ul>';

// Output
$pdf->writeHTML($html, true, false, true, false, '');

// Footer Signature Area
$pdf->Ln(20);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(90, 30, 'VISA TECHNICIEN :', 1, 0, 'L');
$pdf->Cell(10, 30, '', 0, 0);
$pdf->Cell(90, 30, 'VISA BUREAU D\'ÉTUDES :', 1, 1, 'L');

$pdf->Output('fiche_metrage_' . $id . '.pdf', 'I');
