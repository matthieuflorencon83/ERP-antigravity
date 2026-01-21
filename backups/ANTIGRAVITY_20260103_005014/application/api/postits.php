<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 1; // Default to 1 if not logged in (dev mode)
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        // Fetch all post-its for the user
        $stmt = $pdo->prepare("SELECT * FROM dashboard_postits WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $postits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'postits' => $postits]);
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // ACTION: Update Coordinates
        if (isset($_GET['action']) && $_GET['action'] === 'update_coords') {
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID manquant']);
                exit;
            }
            
            $id = $data['id'];
            $x = $data['x'] ?? 20;
            $y = $data['y'] ?? 20;
            $w = $data['width'] ?? 220;
            $h = $data['height'] ?? 220;
            $z = $data['z_index'] ?? 1;
            
            $stmt = $pdo->prepare("UPDATE dashboard_postits SET x_pos = ?, y_pos = ?, width = ?, height = ?, z_index = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$x, $y, $w, $h, $z, $id, $user_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }

        // ACTION: Update Content (Inline Edit)
        if (isset($_GET['action']) && $_GET['action'] === 'update_content') {
            if (!isset($data['id']) || !isset($data['content'])) {
                echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                exit;
            }
            
            $id = $data['id'];
            $content = trim($data['content']);
            
            $stmt = $pdo->prepare("UPDATE dashboard_postits SET content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$content, $id, $user_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }

        // ACTION: Create New Post-it
        if (!isset($data['content']) || empty(trim($data['content']))) {
            echo json_encode(['success' => false, 'message' => 'Contenu vide']);
            exit;
        }

        $content = trim($data['content']);
        $color = $data['color'] ?? 'jaune';

        $stmt = $pdo->prepare("INSERT INTO dashboard_postits (user_id, content, color) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $content, $color]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } 
    elseif ($method === 'DELETE') {
        // Delete post-it
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID manquant']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM dashboard_postits WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Post-it introuvable ou non autorisé']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
