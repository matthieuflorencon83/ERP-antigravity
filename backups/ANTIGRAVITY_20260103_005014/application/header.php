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

    <!-- Theme Init (No FOUC) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery (Required for Antigravity Legacy Code) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap Bundle JS (Required for Dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Antigravity    <!-- Custom CSS -->
    <link href="assets/css/antigravity.css?v=<?= time() ?>" rel="stylesheet">
    
    <!-- Keyboard Shortcuts -->
    <script src="assets/js/keyboard-shortcuts.js?v=<?= time() ?>"></script>
    
    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/9370/9370252.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>

<?php
// Chargement du Contrôleur Header (Navigation & Alertes)
require_once 'controllers/header_controller.php';
// $headerAlerts et $nbHeaderAlerts sont maintenant disponibles
?>

<!-- WRAPPER FIXE (NAV + TICKER) -->
<div class="fixed-top shadow-sm" style="z-index: 1030;">
    
    <!-- NAVBAR HAUTE (V2) -->
    <nav class="navbar navbar-expand-lg navbar-dark ag-navbar p-0 position-relative" style="z-index: 1050;">
      <div class="container-fluid px-4">
        <!-- BRAND -->
        <!-- BRAND -->
        <!-- TOGGLER (HAMBURGER MOBILE) -->
        <button class="btn btn-link text-white d-lg-none me-2 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAG" aria-controls="navbarAG" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars fa-lg"></i>
        </button>

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
            
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            
            <!-- DASHBOARD -->
            <li class="nav-item">
                <a class="nav-link ag-nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    Dashboard
                </a>
            </li>

            <!-- PLANNING (Top Level - Control Tower) -->
            <li class="nav-item">
                <a class="nav-link ag-nav-link <?= $current_page == 'gestion_planning.php' ? 'active' : '' ?>" href="gestion_planning.php">
                   <i class="fas fa-tower-broadcast small me-1"></i>Planning
                </a>
            </li>

            <!-- TO DO LIST -->
            <li class="nav-item">
                <a class="nav-link ag-nav-link <?= $current_page == 'tasks.php' ? 'active' : '' ?>" href="tasks.php">
                    To Do List
                </a>
            </li>

            <!-- EMAIL (New) -->
            <li class="nav-item">
                <a class="nav-link ag-nav-link <?= $current_page == 'gestion_email.php' ? 'active' : '' ?>" href="gestion_email.php">
                    <i class="fas fa-envelope small me-1"></i>Email
                </a>
            </li>

            <!-- GESTION -->
            <?php $gestion_pages = ['affaires_liste.php', 'commandes_liste.php', 'dashboard_bi.php']; ?>
            <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link ag-nav-link dropdown-toggle <?= in_array($current_page, $gestion_pages) ? 'active' : '' ?>" href="#" role="button">
                    Gestion
                </a>
                <ul class="dropdown-menu ag-dropdown-menu border-0 shadow-lg mt-0">
                    <!-- Moved Pilotage here -->
                    <li><a class="dropdown-item py-2 fw-bold text-primary" href="dashboard_bi.php"><i class="fas fa-chart-line me-2"></i>Pilotage</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="affaires_liste.php"><i class="fas fa-folder-open me-2 text-primary opacity-75"></i>Affaires</a></li>
                    <li><a class="dropdown-item py-2" href="commandes_liste.php"><i class="fas fa-shopping-cart me-2 text-success opacity-75"></i>Commandes</a></li>
                    <li><a class="dropdown-item py-2" href="sav_fil.php"><i class="fas fa-tools me-2 text-danger opacity-75"></i>Service Après-Vente (SAV)</a></li>
                    <li><a class="dropdown-item py-2" href="gestion_commande_rapide.php"><i class="fas fa-rocket me-2 text-danger opacity-75"></i>Commande Rapide</a></li>
                    <li><a class="dropdown-item py-2" href="depenses_upload_ocr.php"><i class="fas fa-file-invoice-dollar me-2 text-warning opacity-75"></i>Saisie Factures (OCR)</a></li>
                </ul>
            </li>

            <!-- TECHNIQUE -->
            <?php $technique_pages = ['besoins_saisie.php', 'besoins_saisie_v2.php', 'metrage_cockpit.php', 'gestion_metrage_planning.php']; ?>
            <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link ag-nav-link dropdown-toggle <?= in_array($current_page, $technique_pages) ? 'active' : '' ?>" href="#" role="button">
                    Technique
                </a>
                <ul class="dropdown-menu ag-dropdown-menu border-0 shadow-lg mt-0">
                    <li><a class="dropdown-item py-2" href="besoins_saisie_v2.php"><i class="fas fa-clipboard-list me-2 text-primary opacity-75"></i>Liste de Besoins <span class="badge bg-success ms-1">V3</span></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="metrage_cockpit.php"><i class="fas fa-satellite-dish me-2 text-danger opacity-75"></i>Cockpit Métrage <span class="badge bg-danger ms-1">LIVE</span></a></li>
                    <li><a class="dropdown-item py-2" href="metrage_studio.php"><i class="fas fa-palette me-2 text-success opacity-75"></i>Studio Métrage</a></li>
                </ul>
            </li>

            <!-- STOCK (Cockpit Unique) -->
            <li class="nav-item">
                <a class="nav-link ag-nav-link <?= basename($_SERVER['PHP_SELF']) == 'stocks_cockpit.php' ? 'active' : '' ?>" href="stocks_cockpit.php">
                    Stock
                </a>
            </li>

            <!-- BIBLIOTHÈQUE (Includes Documents) -->
            <?php $biblio_pages = ['clients_liste.php', 'fournisseurs_liste.php', 'catalogue_liste.php', 'docs_explorer.php']; ?>
            <li class="nav-item dropdown ag-dropdown-container">
                <a class="nav-link ag-nav-link dropdown-toggle <?= in_array($current_page, $biblio_pages) ? 'active' : '' ?>" href="#" role="button">
                    Bibliothèque
                </a>
                <ul class="dropdown-menu ag-dropdown-menu border-0 shadow-lg mt-0">
                    <li><a class="dropdown-item py-2" href="clients_liste.php"><i class="fas fa-user-tie me-2 text-primary opacity-75"></i>Clients</a></li>
                    <li><a class="dropdown-item py-2" href="fournisseurs_liste.php"><i class="fas fa-truck me-2 text-warning opacity-75"></i>Fournisseurs</a></li>
                    <li><a class="dropdown-item py-2" href="catalogue_liste.php"><i class="fas fa-book-open me-2 text-info opacity-75"></i>Articles</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li class="dropdown-header small text-muted text-uppercase fw-bold"><i class="fas fa-folder me-2"></i>Documents (Dropbox)</li>
                    <li><a class="dropdown-item py-2" href="docs_explorer.php?type=arc"><i class="fas fa-file-invoice me-2"></i>ARC / BDC / BL</a></li>
                    <li><a class="dropdown-item py-2" href="docs_explorer.php?type=bdc_fournisseur"><i class="fas fa-file-pdf me-2 text-danger opacity-75"></i>BDC Fournisseur</a></li>
                    <li><a class="dropdown-item py-2" href="docs_explorer.php?type=doc_tech"><i class="fas fa-wrench me-2 text-secondary opacity-75"></i>Doc Technique</a></li>
                    <li><a class="dropdown-item py-2" href="docs_explorer.php?type=notice"><i class="fas fa-info-circle me-2 text-info opacity-75"></i>Notice</a></li>
                </ul>
            </li>

          </ul>

          <!-- RIGHT SIDE: PARAMETRES & USER -->
            <!-- PARAMÈTRES (Dropdown) -->
            <li class="nav-item dropdown me-2">
                <a class="nav-link ag-nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" title="Paramètres">
                    <i class="fas fa-cog"></i> <span class="d-lg-none ms-2">Paramètres</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                    <li><a class="dropdown-item py-2" href="parametres.php"><i class="fas fa-sliders-h me-2"></i>Paramètres Généraux</a></li>
                    <li><a class="dropdown-item py-2" href="parametres_email.php"><i class="fas fa-envelope me-2 text-primary"></i>Configuration Email</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="utilisateurs_liste.php"><i class="fas fa-users me-2"></i>Utilisateurs</a></li>
                </ul>
            </li>

            <!-- THEME TOGGLE (Day/Night) -->
            <li class="nav-item me-3">
                <button class="btn nav-link" id="themeToggle" title="Mode Jour/Nuit">
                   <i class="fas fa-moon"></i>
                </button>
            </li>

            <!-- NOTIFICATIONS (New System - Offcanvas) -->
            <li class="nav-item me-3" id="notification-item">
                <a class="nav-link ag-nav-link text-white position-relative" href="#" role="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotifications" aria-controls="offcanvasNotifications">
                    <i class="fas fa-bell fa-lg"></i>
                    <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none; font-size: 0.6rem;">
                        0
                    </span>
                </a>
            </li>

            <!-- USER DROPDOWN -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center fw-bold" style="width:32px; height:32px;">
                        <?= strtoupper(substr($_SESSION['user_login'] ?? 'U', 0, 1)) ?>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 animate__animated animate__fadeIn bg-white">
                    <li><h6 class="dropdown-header">Bonjour, <?= htmlspecialchars($_SESSION['user_login'] ?? 'Invité') ?></h6></li>
                    <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user-circle me-2 text-secondary"></i>Mon Profil</a></li>
                    
                    <?php if(($_SESSION['user_role'] ?? '') === 'ADMIN'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                             <a class="dropdown-item text-danger fw-bold" href="admin_validations.php">
                                <i class="fas fa-shield-alt me-2"></i>Admin Validations
                             </a>
                        </li>
                        <li><a class="dropdown-item" href="utilisateurs_liste.php"><i class="fas fa-users-cog me-2 text-secondary"></i>Utilisateurs</a></li>
                    <?php endif; ?>

                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
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

<!-- NOTIFICATIONS OFFCANVAS -->
<div class="offcanvas offcanvas-end border-0 shadow-lg" tabindex="-1" id="offcanvasNotifications" aria-labelledby="offcanvasNotificationsLabel" style="z-index: 1060;">
  <div class="offcanvas-header text-white" style="background: linear-gradient(135deg, var(--ag-primary) 0%, var(--ag-primary-dark) 100%);">
    <h5 class="offcanvas-title fw-bold" id="offcanvasNotificationsLabel"><i class="fas fa-bell me-2"></i>NOTIFICATIONS</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0 bg-light">
    <?php if($nbHeaderAlerts > 0): ?>
        <div class="list-group list-group-flush">
            <?php foreach($headerAlerts as $alert): ?>
            <a href="<?= isset($alert['link']) ? $alert['link'] : '#' ?>" class="list-group-item list-group-item-action border-0 py-3 border-bottom">
                <div class="d-flex align-items-start">
                    <div class="me-3 mt-1 text-<?= $alert['type'] == 'danger' ? 'danger' : 'primary' ?>">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark mb-1">Information Système</div>
                        <div class="text-muted small lh-sm">
                            <?= $alert['message'] ?>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
            <i class="fas fa-check-circle fa-4x mb-3 text-success opacity-25"></i>
            <p>Aucune nouvelle notification.</p>
        </div>
    <?php endif; ?>
  </div>
</div>

<!-- Main Wrapper Starts Here -->
<div class="container-fluid px-4 ag-main-content">

<script>
document.addEventListener('DOMContentLoaded', function() {
    var myOffcanvas = document.getElementById('offcanvasNotifications');
    var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);
    var trigger = document.querySelector('[data-bs-target="#offcanvasNotifications"]');
    var isHovered = false;

    // Open on hover
    trigger.addEventListener('mouseenter', function() {
        bsOffcanvas.show();
        isHovered = true;
    });

    // Handle closing logic
    var closeTimer;

    function startCloseTimer() {
        closeTimer = setTimeout(function() {
            if (!isHovered) {
                bsOffcanvas.hide();
            }
        }, 300); // 300ms delay to move cursor
    }

    function stopCloseTimer() {
        clearTimeout(closeTimer);
        isHovered = true;
    }

    trigger.addEventListener('mouseleave', function() {
        isHovered = false;
        startCloseTimer();
    });

    myOffcanvas.addEventListener('mouseenter', stopCloseTimer);
    
    myOffcanvas.addEventListener('mouseleave', function() {
        isHovered = false;
        startCloseTimer();
    });
});
</script>

<script>
// THEME SWITCHER LOGIC
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('themeToggle');
    const icon = toggleBtn ? toggleBtn.querySelector('i') : null;
    const html = document.documentElement;
    
    // Init Icon based on current theme
    const currentTheme = html.getAttribute('data-bs-theme') || 'light';
    if(icon) {
        icon.className = currentTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    if(toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            
            // Apply
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            
            // Icon
            if(icon) {
                icon.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    }
});
</script>

