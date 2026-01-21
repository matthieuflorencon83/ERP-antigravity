<?php
// api/ged_api.php - API pour la GED (Gestion Électronique de Documents)
session_start();
require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Lister les fichiers GED
            $gedRoot = 'C:/ARTSALU/AFFAIRES';
            
            if (!is_dir($gedRoot)) {
                echo json_encode(['error' => 'Répertoire GED introuvable']);
                exit;
            }
            
            $files = scanGEDDirectory($gedRoot);
            echo json_encode($files);
            break;
            
        default:
            echo json_encode(['error' => 'Action inconnue']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Scanner récursif du répertoire GED
 * @param string $dir Répertoire à scanner
 * @param int $depth Profondeur actuelle
 * @return array Liste des fichiers
 */
function scanGEDDirectory($dir, $depth = 0) {
    // Limiter la profondeur pour éviter les performances
    if ($depth > 4) return [];
    
    $files = [];
    
    try {
        $items = @scandir($dir);
        if ($items === false) return [];
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            
            // Sécurité : vérifier que le chemin est bien dans GED_ROOT
            $realPath = realpath($path);
            if ($realPath === false || strpos($realPath, realpath('C:/ARTSALU/AFFAIRES')) !== 0) {
                continue;
            }
            
            if (is_file($path)) {
                // Filtrer les types de fichiers autorisés
                $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowedExtensions)) {
                    $files[] = [
                        'id' => md5($path),
                        'name' => $item,
                        'path' => $path,
                        'size' => filesize($path),
                        'type' => mime_content_type($path),
                        'extension' => $ext
                    ];
                }
            } elseif (is_dir($path)) {
                // Scanner récursivement les sous-dossiers
                $subFiles = scanGEDDirectory($path, $depth + 1);
                $files = array_merge($files, $subFiles);
            }
        }
    } catch (Exception $e) {
        error_log("Erreur scan GED: " . $e->getMessage());
    }
    
    return $files;
}
