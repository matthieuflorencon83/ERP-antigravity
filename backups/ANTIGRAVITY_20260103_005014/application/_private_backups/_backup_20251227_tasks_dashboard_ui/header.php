<?php
// header.php - TOP BAR NAVIGATION (DROPDOWNS)
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antigravity V2</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Bundle JS (Required for Dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Antigravity Design System -->
    <link href="assets/css/antigravity.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

<?php
// Protection globale
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php'):

// Récupération des alertes globales pour le header
$headerAlerts = [];
$nbHeaderAlerts = 0;
if (function_exists('getGlobalAlerts') && isset($pdo)) {
    $headerAlerts = getGlobalAlerts($pdo);
    
    // AJOUT DES TÂCHES AU TICKER (Sécurisé)
    try {
        // Updated Query to fetch Date, Affaire, Description
        $sql_tasks = "SELECT t.title, t.importance, t.description, t.due_date, a.nom_affaire 
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
            
            // Build Rich Message (Project Creation Date)
            $date_str = $mt['created_at'] ? date('d/m', strtotime($mt['created_at'])) : '';
            $date_badge = $date_str ? "[{$date_str}] " : "";
            
            $chantier_str = $mt['nom_affaire'] ? " | {$mt['nom_affaire']}" : "";
            $desc_str = !empty($mt['description']) ? " : " . substr(strip_tags($mt['description']), 0, 50) . (strlen($mt['description']) > 50 ? '...' : '') : "";
            
            $full_message = "{$date_badge}<strong>" . htmlspecialchars($mt['title']) . "</strong>{$chantier_str}" . htmlspecialchars($desc_str);

            $headerAlerts[] = [
                'type' => $type,
                'icon' => $icon_name,
                'message' => $full_message // Contains HTML for bolding
            ];
        }
    } catch (Exception $e) {
        // En cas d'erreur (table manquante, etc.), on continue sans afficher les tâches
    }

    // Force Carousel Animation : If few items, duplicate well to fill the ticker (at least 4 items)
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
?>

<!-- WRAPPER FIXE (NAV + TICKER) -->
<div class="fixed-top shadow-sm" style="z-index: 1030;">
    
    <!-- NAVBAR HAUTE (V2) -->
    <nav class="navbar navbar-expand-lg navbar-dark ag-navbar p-0 position-relative" style="z-index: 1050;">
      <div class="container-fluid px-4">
        <!-- BRAND -->
        <!-- BRAND -->
        <a class="navbar-brand fw-bold me-4" href="dashboard.php" style="font-family: 'Inter', sans-serif; letter-spacing: 0.5px;">
            <i class="fas fa-cube me-2 text-primary"></i><?= isset($page_title) ? strtoupper($page_title) : 'ANTIGRAVITY' ?>
        </a>

        <!-- TOGGLER -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAG">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- MAIN NAVIGATION -->
        <div class="collapse navbar-collapse" id="navbarAG">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0 ag-nav-list h-100 align-items-center">
            
            <!-- DASHBOARD -->
            <li class="nav-item">
                <a class="nav-link ag-nav-link active" href="dashboard.php">
                    Dashboard
                </a>
            </li>

            <!-- TO DO LIST -->
            <li class="nav-item">
                <a class="nav-link ag-nav-link" href="tasks.php">
                    To Do List
                </a>
            </li>

            <!-- GESTION -->
            <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link ag-nav-link dropdown-toggle" href="#" role="button">
                    Gestion
                </a>
                <ul class="dropdown-menu ag-dropdown-menu border-0 shadow-lg mt-0">
                    <li><a class="dropdown-item py-2" href="affaires_liste.php"><i class="fas fa-folder-open me-2 text-primary opacity-75"></i>Affaires</a></li>
                    <li><a class="dropdown-item py-2" href="commandes_liste.php"><i class="fas fa-shopping-cart me-2 text-success opacity-75"></i>Commandes</a></li>
                    <li><a class="dropdown-item py-2" href="planning_view.php"><i class="fas fa-calendar-alt me-2 text-warning opacity-75"></i>Agenda</a></li>
                </ul>
            </li>

            <!-- TECHNIQUE -->
            <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link ag-nav-link dropdown-toggle" href="#" role="button">
                    Technique
                </a>
                <ul class="dropdown-menu ag-dropdown-menu border-0 shadow-lg mt-0">
                    <li><a class="dropdown-item py-2" href="besoins_saisie.php"><i class="fas fa-calculator me-2 text-info opacity-75"></i>Liste Besoins</a></li>
                    <li><a class="dropdown-item py-2" href="atelier_liste.php"><i class="fas fa-cut me-2 text-danger opacity-75"></i>Debit / Coupe</a></li>
                </ul>
            </li>

            <!-- STOCK -->
            <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link ag-nav-link dropdown-toggle" href="#" role="button">
                    Stock
                </a>
                <ul class="dropdown-menu ag-dropdown-menu border-0 shadow-lg mt-0">
                    <li><a class="dropdown-item py-2" href="stocks_mouvements.php"><i class="fas fa-exchange-alt me-2 text-primary opacity-75"></i>Mouvements</a></li>
                    <li><a class="dropdown-item py-2" href="stocks_liste.php"><i class="fas fa-cubes me-2 text-success opacity-75"></i>Etat Stock</a></li>
                </ul>
            </li>

            <!-- BIBLIOTHÈQUE -->
            <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link ag-nav-link dropdown-toggle" href="#" role="button">
                    Bibliothèque
                </a>
                <ul class="dropdown-menu ag-dropdown-menu border-0 shadow-lg mt-0">
                    <li><a class="dropdown-item py-2" href="clients_liste.php"><i class="fas fa-user-tie me-2 text-primary opacity-75"></i>Clients</a></li>
                    <li><a class="dropdown-item py-2" href="fournisseurs_liste.php"><i class="fas fa-truck me-2 text-warning opacity-75"></i>Fournisseurs</a></li>
                    <li><a class="dropdown-item py-2" href="catalogue_liste.php"><i class="fas fa-book-open me-2 text-info opacity-75"></i>Articles</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item py-2 text-muted" href="#"><i class="fas fa-file-invoice me-2"></i>ARC / BDC / BL</a></li>
                </ul>
            </li>

            <!-- DOCUMENT -->
            <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link ag-nav-link dropdown-toggle" href="#" role="button">
                    Document
                </a>
                <ul class="dropdown-menu ag-dropdown-menu border-0 shadow-lg mt-0">
                    <li><a class="dropdown-item py-2" href="#"><i class="fas fa-file-pdf me-2 text-danger opacity-75"></i>BDC Fournisseur</a></li>
                    <li><a class="dropdown-item py-2" href="#"><i class="fas fa-wrench me-2 text-secondary opacity-75"></i>Doc Technique</a></li>
                    <li><a class="dropdown-item py-2" href="#"><i class="fas fa-info-circle me-2 text-info opacity-75"></i>Notice</a></li>
                </ul>
            </li>

          </ul>

          <!-- RIGHT SIDE: PARAMETRES & USER -->
          <ul class="navbar-nav ms-auto align-items-center">
            
            <!-- PARAMÈTRES (Lien direct) -->
            <li class="nav-item me-3">
                 <a class="nav-link ag-nav-link" href="parametres.php" title="Paramètres">
                    <i class="fas fa-cog"></i> <span class="d-lg-none ms-2">Paramètres</span>
                </a>
            </li>

            <!-- NOTIFICATIONS -->
            <li class="nav-item dropdown ag-dropdown-container me-3">
                <a class="nav-link position-relative" href="#" id="notifDropdown" role="button">
                    <i class="fas fa-bell fa-lg <?= $nbHeaderAlerts > 0 ? 'text-warning' : '' ?>"></i>
                    <?php if($nbHeaderAlerts > 0): ?>
                    <span class="position-absolute top-10 start-90 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">Alertes</span>
                    </span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg ag-dropdown-menu p-0" style="width:300px;">
                    <li class="p-2 bg-light border-bottom fw-bold text-muted small uppercase">
                        NOTIFICATIONS
                    </li>
                     <li class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                        <?php if($nbHeaderAlerts > 0): foreach($headerAlerts as $alert): ?>
                        <a href="#" class="list-group-item list-group-item-action border-0 py-2">
                            <div class="d-flex align-items-start">
                                <div class="me-2 mt-1 text-<?= $alert['type'] == 'danger' ? 'danger' : 'primary' ?>">
                                    <i class="fas fa-circle fa-xs"></i>
                                </div>
                                <div class="small lh-sm text-dark">
                                    <?= $alert['message'] ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; else: ?>
                        <div class="text-center p-3 text-muted small">Rien à signaler</div>
                        <?php endif; ?>
                    </li>
                </ul>
            </li>

            <!-- USER PROFILE (Far Right) -->
             <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        <?= strtoupper(substr($_SESSION['user_login'] ?? 'U', 0, 1)) ?>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end ag-dropdown-menu shadow-lg border-0">
                    <li><a class="dropdown-item py-2" href="logout.php"><i class="fas fa-sign-out-alt me-2 text-danger"></i>Déconnexion</a></li>
                </ul>
            </li>

          </ul>
        </div>
      </div>
    </nav>

    <!-- ALERT TICKER (Integrated in Wrapper) -->
    <!-- ALERT TICKER (Fading Carousel) -->
    <div class="alert-ticker position-relative" style="z-index: 1040; height: 40px; background: #0f4c75; overflow: hidden;">
        <div id="tickerCarousel" class="ticker-carousel w-100 h-100 position-relative">
            <?php if ($nbHeaderAlerts > 0): ?>
                <?php foreach ($headerAlerts as $alert): ?>
                <div class="ticker-slide">
                    <i class="fas fa-<?= $alert['icon'] ?> me-2"></i><?= $alert['message'] ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <div class="ticker-slide">
                     <i class="fas fa-check-circle me-2"></i>Aucune alerte ! Système opérationnel.
                 </div>
                 <div class="ticker-slide">
                     <i class="fas fa-calendar-alt me-2"></i><?= date_fr(date('Y-m-d')) ?>
                 </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<!-- Main Wrapper Starts Here -->

<!-- Main Wrapper (Standardized Class) -->
<div class="container-fluid px-4 ag-main-content">
<?php endif; ?>
