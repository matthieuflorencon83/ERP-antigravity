<?php
/**
 * controllers/header_controller.php
 * Gestion de la navigation et des alertes globales
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Protection globale (si non gérée par le fichier parent)
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: login.php");
    exit;
}

$headerAlerts = [];
$nbHeaderAlerts = 0;

if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php' && isset($pdo)) {

    // Récupération des alertes globales
    if (function_exists('getGlobalAlerts')) {
        $headerAlerts = getGlobalAlerts($pdo);
        
        // AJOUT DES TÂCHES AU TICKER
        try {
            $sql_tasks = "SELECT t.title, t.importance, t.description, t.due_date, t.created_at, a.nom_affaire 
                          FROM tasks t 
                          LEFT JOIN affaires a ON t.affaire_id = a.id 
                          WHERE t.user_id = ? AND t.status = 'todo' 
                          ORDER BY t.importance DESC, t.due_date ASC, t.created_at DESC LIMIT 5";
            $stmt_tasks = $pdo->prepare($sql_tasks);
            $stmt_tasks->execute([$_SESSION['user_id']]);
            $my_tasks = $stmt_tasks->fetchAll();
            
            foreach($my_tasks as $mt) {
                $icon_name = $mt['importance'] == 'high' ? 'exclamation-circle' : 'clipboard-list';
                $type = $mt['importance'] == 'high' ? 'danger' : 'warning';
                
                // Formatage du message
                $date_str = $mt['created_at'] ? date('d/m', strtotime($mt['created_at'])) : '';
                $date_badge = $date_str ? "[{$date_str}] " : "";
                
                $chantier_str = $mt['nom_affaire'] ? " | {$mt['nom_affaire']}" : "";
                $desc_str = !empty($mt['description']) ? " : " . substr(strip_tags($mt['description']), 0, 50) . (strlen($mt['description']) > 50 ? '...' : '') : "";
                
                $full_message = "{$date_badge}<strong>" . htmlspecialchars($mt['title']) . "</strong>{$chantier_str}" . htmlspecialchars($desc_str);
    
                $headerAlerts[] = [
                    'type' => $type,
                    'icon' => $icon_name,
                    'message' => $full_message,
                    'link' => 'tasks.php' // Link to Task Module
                ];
            }
        } catch (Exception $e) {
            // Silence en prod
        }
    
        // Animation Ticker (Duplication si peu d'items)
        $original_count = count($headerAlerts);
        if ($original_count > 0 && $original_count < 4) {
            $multiplier = ceil(4 / $original_count);
            $base = $headerAlerts;
            for ($i=1; $i < $multiplier; $i++) {
                $headerAlerts = array_merge($headerAlerts, $base);
            }
        }
        
        $nbHeaderAlerts = count($headerAlerts);
    }
}
