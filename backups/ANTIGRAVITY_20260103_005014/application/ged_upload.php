<?php
/**
 * ged_upload.php
 * Endpoint d'upload de fichiers pour la GED.
 * 
 * @project Antigravity
 * @version 1.0
 */
require_once 'auth.php'; // Securite
session_start(); // Deja fait dans auth, mais harmless
require_once 'db.php';
require_once 'functions.php';

// CONSTANTS
$MAX_SIZE = 10 * 1024 * 1024; // 10 Mo
$ALLOWED_EXTS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];
$ALLOWED_ROOTS = [
    GED_ROOT,
    'C:/Dropbox/CLIENTS',
    __DIR__ . '/uploads',
];

header('Content-Type: application/json');

// 1. AUTH & INPUT
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$target_dir = $_POST['target_dir'] ?? '';

// 2. SECURITY CHECK (PATH TRAVERSAL)
if (empty($target_dir)) {
    echo json_encode(['success' => false, 'error' => 'Dossier cible manquant']);
    exit;
}

$real_target = realpath($target_dir);
if ($real_target === false || !is_dir($real_target)) {
    echo json_encode(['success' => false, 'error' => 'Dossier cible introuvable ou inexistant']);
    exit;
}

// Validation Whitelist
$is_allowed = false;
foreach ($ALLOWED_ROOTS as $root) {
    if (str_starts_with($real_target, realpath($root))) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    echo json_encode(['success' => false, 'error' => 'Dossier cible interdit (Hors périmètre)']);
    exit;
}

// 3. FILE PROCESS
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Erreur upload (Code: ' . ($_FILES['file']['error'] ?? 'N/A') . ')']);
    exit;
}

$file = $_FILES['file'];

// Size Check
if ($file['size'] > $MAX_SIZE) {
    echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (Max 10Mo)']);
    exit;
}

// Extension Check
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $ALLOWED_EXTS)) {
    echo json_encode(['success' => false, 'error' => 'Type de fichier interdit (Extension: ' . $ext . ')']);
    exit;
}

// Sanitize Name
$clean_name = safe_filename(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
$dest_path = $real_target . DIRECTORY_SEPARATOR . $clean_name;

// Avoid Overwrite ? (Optionnel: suffixer si existe)
if (file_exists($dest_path)) {
    $clean_name = safe_filename(pathinfo($file['name'], PATHINFO_FILENAME)) . '_' . time() . '.' . $ext;
    $dest_path = $real_target . DIRECTORY_SEPARATOR . $clean_name;
}

if (move_uploaded_file($file['tmp_name'], $dest_path)) {
    echo json_encode(['success' => true, 'filename' => $clean_name]);
} else {
    echo json_encode(['success' => false, 'error' => 'Echec écriture disque']);
}
