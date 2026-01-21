<?php
/**
 * view.php
 * PASSE-PLAT SÉCURISÉ : Permet de lire des fichiers locaux (Dropbox/Serveur).
 * 
 * SÉCURITÉ RENFORCÉE (Audit 2025-12-25):
 * - Whitelist stricte des dossiers autorisés
 * - Protection Path Traversal via realpath()
 * - Vérification session obligatoire
 * 
 * @project Antigravity
 * @version 2.0 (Hardened)
 */
require_once 'auth.php'; // Securite
// require_once 'db.php'; // Inclus par auth.php
require_once 'functions.php';

// ============================================
// 1. AUTHENTIFICATION OBLIGATOIRE
// ============================================
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Accès non autorisé. Veuillez vous connecter.");
}

// ============================================
// 2. WHITELIST DES DOSSIERS AUTORISÉS
// ============================================
$ALLOWED_ROOTS = [
    GED_ROOT,
    'C:/Dropbox/CLIENTS',
    'C:/laragon/www/antigravity/uploads',
    // Ajoutez vos dossiers métier ici
];

// Normaliser les chemins autorisés
$allowed_real_paths = array_filter(array_map('realpath', $ALLOWED_ROOTS));

// ============================================
// 3. RÉCUPÉRATION & VALIDATION DU CHEMIN
// ============================================
$path = $_GET['path'] ?? '';

if (empty($path)) {
    http_response_code(400);
    die("Paramètre 'path' manquant.");
}

// Normalisation des séparateurs
$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

// Résolution du chemin réel (élimine les .., liens symboliques, etc.)
$real_path = realpath($path);

if ($real_path === false) {
    http_response_code(404);
    die("Fichier ou dossier introuvable.");
}

// ============================================
// 4. VÉRIFICATION WHITELIST (CRITIQUE)
// ============================================
$is_allowed = false;
foreach ($allowed_real_paths as $allowed_root) {
    if (str_starts_with($real_path, $allowed_root)) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    // LOG de sécurité (optionnel)
    error_log("[SECURITY] Tentative d'accès non autorisé: $real_path par user_id=" . $_SESSION['user_id']);
    
    http_response_code(403);
    die("Accès refusé. Ce chemin n'est pas dans les dossiers autorisés.");
}

// On utilise le chemin résolu sécurisé
$path = $real_path;

// ============================================
// 5. SI FICHIER -> TÉLÉCHARGEMENT / AFFICHAGE
// ============================================
if (is_file($path)) {
    $mime = mime_content_type($path);
    $filename = basename($path);
    
    // Types affichables inline
    $inline_types = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'text/plain'
    ];
    
    if (in_array($mime, $inline_types, true)) {
        header("Content-Type: $mime");
        header("Content-Disposition: inline; filename=\"" . rawurlencode($filename) . "\"");
    } else {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . rawurlencode($filename) . "\"");
    }
    
    // Sécurité: Empêcher le caching de documents sensibles
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Content-Length: " . filesize($path));
    
    readfile($path);
    exit;
}

// ============================================
// 6. SI DOSSIER -> LISTING
// ============================================
if (is_dir($path)) {
    $files = scandir($path);
    $clean_files = [];
    
    foreach ($files as $f) {
        // Exclusions
        if ($f === '.' || $f === '..') continue;
        if (str_starts_with($f, '.')) continue; // Fichiers cachés
        if (str_starts_with($f, '~')) continue; // Fichiers temporaires Office
        
        $clean_files[] = $f;
    }
    
    // Tri naturel (1, 2, 10 au lieu de 1, 10, 2)
    natsort($clean_files);
    $clean_files = array_values($clean_files);
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorateur GED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/antigravity.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        body { background-color: var(--bs-body-bg); font-size: 0.9rem; }
        .file-item { cursor: pointer; transition: background-color 0.1s; }
        .file-item:hover { background-color: var(--bs-secondary-bg); }
        a { text-decoration: none; color: inherit; display: block; }
    </style>
</head>
<body>
    <div class="container-fluid py-3">
        <?php if (empty($clean_files)): ?>
            <div class="text-center text-muted mt-4">
                <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                <p>Dossier vide</p>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($clean_files as $f): 
                    $full_path = $path . DIRECTORY_SEPARATOR . $f;
                    $is_dir = is_dir($full_path);
                    
                    // Icône par type
                    $icon = $is_dir ? 'fa-folder text-warning' : 'fa-file text-secondary';
                    
                    if (!$is_dir) {
                        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                        $icon = match($ext) {
                            'pdf' => 'fa-file-pdf text-danger',
                            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'fa-file-image text-primary',
                            'xls', 'xlsx', 'csv' => 'fa-file-excel text-success',
                            'doc', 'docx' => 'fa-file-word text-primary',
                            'zip', 'rar', '7z' => 'fa-file-archive text-warning',
                            'txt', 'log' => 'fa-file-alt text-muted',
                            default => 'fa-file text-secondary'
                        };
                    }
                ?>
                <div class="list-group-item list-group-item-action file-item d-flex align-items-center">
                    <a href="view.php?path=<?= urlencode($full_path) ?>" class="d-flex align-items-center w-100">
                        <i class="fas <?= $icon ?> fa-lg me-3" style="width: 20px; text-align: center;"></i>
                        <span class="text-truncate"><?= htmlspecialchars($f) ?></span>
                        <?php if (!$is_dir): ?>
                            <small class="ms-auto text-muted"><?= date("d/m/Y H:i", filemtime($full_path)) ?></small>
                        <?php endif; ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
