<?php
/**
 * admin_logs.php
 * Interface Audit Sécurité & Connexions
 */
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// Access Control
if (($_SESSION['user_role'] ?? '') !== 'ADMIN') {
    die("Accès refusé.");
}

$page_title = 'Audit Connexions';
require_once 'header.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Fetch Logs
$stmt = $pdo->prepare("SELECT * FROM access_logs ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute();
$logs = $stmt->fetchAll();

// Total
$total_logs = $pdo->query("SELECT COUNT(*) FROM access_logs")->fetchColumn();
$total_pages = ceil($total_logs / $limit);
?>

<div class="main-content container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-user-secret me-2"></i>Audit des Connexions</h2>
            <p class="text-muted">Historique des accès et tentatives de connexion.</p>
        </div>
        <a href="parametres.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour Paramètres</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>IP</th>
                            <th>Appareil (User Agent)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                            <?php 
                                // Detect Mobile
                                $ua = $log['user_agent'];
                                $is_mobile = preg_match('/(android|iphone|ipad|mobile)/i', $ua);
                                $device_icon = $is_mobile ? '<i class="fas fa-mobile-alt text-primary" title="Mobile"></i>' : '<i class="fas fa-desktop text-secondary" title="Ordinateur"></i>';
                                
                                // Badge Event
                                $badge = 'bg-secondary';
                                if ($log['event_type'] === 'LOGIN') $badge = 'bg-success';
                                if ($log['event_type'] === 'LOGOUT') $badge = 'bg-warning text-dark';
                                if ($log['event_type'] === 'ACCESS_DENIED') $badge = 'bg-danger';
                            ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td class="fw-bold">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:24px; height:24px; font-size:0.7rem;">
                                            <?= strtoupper(substr($log['user_nom'] ?? '?', 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($log['user_nom']) ?>
                                    </div>
                                </td>
                                <td><span class="badge <?= $badge ?> rounded-pill"><?= $log['event_type'] ?></span></td>
                                <td class="font-monospace small"><?= $log['ip_address'] ?></td>
                                <td class="small text-muted">
                                    <div class="d-flex align-items-center gap-2">
                                        <?= $device_icon ?>
                                        <span class="text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($ua) ?>">
                                            <?= htmlspecialchars($ua) ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($logs)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Aucun historique disponible.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white py-3">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page-1 ?>">Précédent</a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">Page <?= $page ?> / <?= $total_pages ?></span>
                        </li>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page+1 ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
