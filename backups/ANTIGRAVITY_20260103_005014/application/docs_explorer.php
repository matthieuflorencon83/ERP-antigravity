<?php
// docs_explorer.php - Explorateur de fichiers (Dropbox)
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$type = $_GET['type'] ?? '';
$types_map = [
    'arc' => ['key' => 'chemin_dropbox_arc', 'title' => 'ARC / BDC / BL', 'icon' => 'fa-file-invoice'],
    'bdc_fournisseur' => ['key' => 'chemin_dropbox_bdc_fournisseur', 'title' => 'BDC Fournisseurs', 'icon' => 'fa-truck-loading'],
    'doc_tech' => ['key' => 'chemin_dropbox_doc_tech', 'title' => 'Documentation Technique', 'icon' => 'fa-wrench'],
    'notice' => ['key' => 'chemin_dropbox_notice', 'title' => 'Notices', 'icon' => 'fa-info-circle']
];

if (!array_key_exists($type, $types_map)) {
    die("Type de document invalide.");
}

$current_config = $types_map[$type];
$page_title = $current_config['title'];

// Récupérer le chemin en base
$stmt = $pdo->prepare("SELECT valeur_config FROM parametres_generaux WHERE cle_config = ?");
$stmt->execute([$current_config['key']]);
$row = $stmt->fetch();
$base_path = $row['valeur_config'] ?? '';

// Gestion Serveur de Fichier (Proxy)
if (isset($_GET['file'])) {
    $file = basename($_GET['file']); // Sécurité basic
    $full_path = $base_path . '/' . $file;

    if (file_exists($full_path) && is_file($full_path)) {
        $mime = mime_content_type($full_path);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $file . '"');
        header('Content-Length: ' . filesize($full_path));
        readfile($full_path);
        exit;
    } else {
        die("Fichier introuvable.");
    }
}

// Scanne le dossier
$files = [];
$error = "";

if (is_dir($base_path)) {
    $scanned = scandir($base_path);
    foreach ($scanned as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $base_path . '/' . $f;
        if (is_file($full)) {
            $files[] = [
                'name' => $f,
                'size' => filesize($full),
                'date' => filemtime($full),
                'ext' => strtolower(pathinfo($full, PATHINFO_EXTENSION))
            ];
        }
    }
} else {
    $error = "Le dossier configuré est introuvable : " . htmlspecialchars($base_path);
}

require_once 'header.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas <?= $current_config['icon'] ?> me-2 text-primary"></i><?= htmlspecialchars($current_config['title']) ?>
            </h1>
            <p class="text-muted small mb-0"><i class="fas fa-folder-open me-1"></i> <?= htmlspecialchars($base_path) ?></p>
        </div>
        <div>
            <a href="parametres.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog me-1"></i> Configurer</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-warning border-start border-warning border-4 shadow-sm">
            <div class="d-flex">
                <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                <div>
                    <h5 class="alert-heading fw-bold">Dossier Inaccessible</h5>
                    <p class="mb-0"><?= $error ?></p>
                    <hr>
                    <p class="mb-0 small">Vérifiez le chemin dans les <a href="parametres.php" class="alert-link">paramètres</a> ou assurez-vous que Dropbox est bien lancé.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nom du Fichier</th>
                                <th>Date</th>
                                <th class="text-end">Taille</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($files)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i><br>
                                        Dossier vide.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($files as $f): ?>
                                    <?php 
                                        $icon = 'fa-file';
                                        $color = 'text-secondary';
                                        if (in_array($f['ext'], ['pdf'])) { $icon = 'fa-file-pdf'; $color = 'text-danger'; }
                                        if (in_array($f['ext'], ['jpg','png','jpeg'])) { $icon = 'fa-file-image'; $color = 'text-info'; }
                                        if (in_array($f['ext'], ['doc','docx'])) { $icon = 'fa-file-word'; $color = 'text-primary'; }
                                        if (in_array($f['ext'], ['xls','xlsx'])) { $icon = 'fa-file-excel'; $color = 'text-success'; }
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-medium">
                                            <i class="fas <?= $icon ?> <?= $color ?> me-2 fa-lg"></i>
                                            <a href="?type=<?= $type ?>&file=<?= urlencode($f['name']) ?>" target="_blank" class="text-dark text-decoration-none stretched-link">
                                                <?= htmlspecialchars($f['name']) ?>
                                            </a>
                                        </td>
                                        <td class="text-muted small"><?= date('d/m/Y H:i', $f['date']) ?></td>
                                        <td class="text-end font-monospace small"><?= round($f['size'] / 1024, 0) ?> Ko</td>
                                        <td class="text-end pe-4">
                                            <a href="?type=<?= $type ?>&file=<?= urlencode($f['name']) ?>" target="_blank" class="btn btn-sm btn-light text-primary border rounded-circle" title="Ouvrir">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <!-- Download Link Force -->
                                            <a download href="?type=<?= $type ?>&file=<?= urlencode($f['name']) ?>" class="btn btn-sm btn-light text-dark border rounded-circle ms-1" title="Télécharger" onclick="event.stopPropagation()">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>
</body>
</html>
