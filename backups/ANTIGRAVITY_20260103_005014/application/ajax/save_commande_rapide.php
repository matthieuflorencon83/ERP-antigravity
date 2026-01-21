<?php
// ajax/save_commande_rapide.php
require_once '../auth.php';
require_once '../functions.php';
require_once '../db.php';
require_once '../core/CommandeExpressPDF.php';

// Prepare Response
$response = ['success' => false, 'message' => '', 'pdf_url' => ''];

// Clean output buffer to remove any spurrious warnings included before this script
ob_clean();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée");
    }

    // 1. DATA EXTRACTION
    $type_imputation = $_POST['imputation_type'] ?? ''; 
    
    // Sanitize Affaire ID
    $raw_id = $_POST['affaire_id'] ?? null;
    $affaire_id = ($type_imputation === 'AFFAIRE' && !empty($raw_id) && $raw_id > 0) ? intval($raw_id) : null;

    // Verify existence to avoid FK error hard crash
    if ($affaire_id) {
        $check = $pdo->prepare("SELECT id FROM affaires WHERE id = ?");
        $check->execute([$affaire_id]);
        if (!$check->fetch()) {
             $affaire_id = null; // Fallback to null if ID doesn't exist
        }
    }
    
    $module_type = $_POST['module_type'] ?? '';
    
    // Details JSON Construction
    // We expect raw POST inputs specific to each module
    $details = [];
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['imputation_type', 'affaire_id', 'module_type', 'canvas_image'])) {
            $details[$key] = $value;
        }
    }
    
    // Handling Special Fields (Canvas, File)
    if (!empty($_POST['canvas_image'])) {
        $details['canvas_image'] = $_POST['canvas_image']; // Base64 string
    }
    
    // Determine Supplier Name (simplistic logic, can be refined)
    $fournisseur_nom = "TBD";
    if (!empty($_POST['fournisseur'])) $fournisseur_nom = $_POST['fournisseur'];
    if (!empty($_POST['fournisseur_cat'])) $fournisseur_nom = $_POST['fournisseur_cat'];
    if (!empty($_POST['fournisseur_id'])) $fournisseur_nom = $_POST['fournisseur_id'];

    if ($module_type === 'VITRAGE') $fournisseur_nom = "RIOU GLASS"; // Default for Vitrage example
    if ($module_type === 'PLIAGE') $fournisseur_nom = "ATELIER PLIAGE";

    // 2. DATABASE INSERT
    $sql = "INSERT INTO commandes_express (
                type_imputation, 
                affaire_id, 
                module_type, 
                fournisseur_nom, 
                details_json, 
                created_by, 
                created_at,
                statut,
                user_id
            ) VALUES (
                :type_imputation, 
                :affaire_id, 
                :module_type, 
                :fournisseur_nom, 
                :details_json, 
                :created_by, 
                NOW(),
                'EN_ATTENTE',
                :user_id
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':type_imputation' => $type_imputation,
        ':affaire_id' => $affaire_id,
        ':module_type' => $module_type,
        ':fournisseur_nom' => $fournisseur_nom,
        ':details_json' => json_encode($details),
        ':created_by' => $_SESSION['user_id'] ?? 1, // Fallback ID
        ':user_id' => $_SESSION['user_id'] ?? 1
    ]);
    
    $commande_id = $pdo->lastInsertId();

    // 3. PDF GENERATION
    $pdfData = [
        'id' => $commande_id,
        'imputation_type' => $type_imputation,
        'imputation_ref' => ($type_imputation === 'AFFAIRE' && $affaire_id) ? "Affaire #$affaire_id" : "Stock",
        'module_type' => $module_type,
        'details' => (object)$details,
        'commentaire' => ''
    ];

    $pdf = new CommandeExpressPDF($pdfData, ['nom' => $fournisseur_nom]);
    $pdf->renderBody();

    // Ensure directory exists
    $outputDir = __DIR__ . '/../uploads/commandes_express/';
    if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

    $filename = "CMD_EXP_{$commande_id}_{$module_type}.pdf";
    $filepath = $outputDir . $filename;
    
    $pdf->Output($filepath, 'F');

    // Update DB with PDF path ? (Optional, currently not in schema, relying on convention or adding column later)
    // For now, valid response is enough.

    $response['success'] = true;
    $response['message'] = "Commande #$commande_id enregistrée avec succès !";
    $response['pdf_url'] = "uploads/commandes_express/$filename";

} catch (Throwable $e) {
    $response['message'] = "Erreur : " . $e->getMessage() . " (File: " . $e->getFile() . " Line: " . $e->getLine() . ")";
}

header('Content-Type: application/json');
echo json_encode($response);
