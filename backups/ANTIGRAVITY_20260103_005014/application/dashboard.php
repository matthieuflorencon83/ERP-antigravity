<?php
/**
 * dashboard.php
 * Dashboard avec suivi des commandes et agendas
 * @version 3.0 (Restored from 02:21 AM Backup)
 */

require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output Buffering to prevent "headers already sent" issues
ob_start();

// Chargement du Contrôleur (MVC Pattern)
require_once 'controllers/dashboard_controller.php';

// Le contrôleur rend disponible :
// $stats, $commandes_en_attente, $commandes_commandees, 
// $commandes_arc_recus, $commandes_livraisons,
// $agenda_livraisons, $agenda_poses
?>
<!DOCTYPE html>
<!-- Redirecting to correct structure: header.php handles structure now -->
<?php 
$page_title = 'Dashboard';
require_once 'header.php'; 
echo '<link rel="stylesheet" href="assets/css/dashboard_postits.css">'; 
?>

<!-- FIX LAYOUT DASHBOARD -->
<style>
    /* Masquer la scrollbar pour les agendas tout en gardant le scroll actif */
    .agenda-scroll-area::-webkit-scrollbar {
        width: 0px;
        background: transparent;
    }
    .agenda-scroll-area {
        scrollbar-width: none;  /* Firefox */
        -ms-overflow-style: none;  /* IE and Edge */
    }
</style>
<!-- FIX LAYOUT DASHBOARD -->
<div id="dashboard-wrapper" class="d-flex flex-column h-100" style="margin-top: -45px;">
    <div class="row flex-nowrap flex-column h-100">

<!-- Container is already opened in header.php -->





        
        <!-- En-tête avec Bandeau et Cloche -->

        <!-- TUILES KPI -->
        <div class="row g-3 mb-0 flex-shrink-0" style="min-height: 280px;">
            <!-- En Attente -->
            <div class="col-6 col-md-3">
                <div class="stat-card card bg-warning text-white h-100" onclick="window.location='commandes_liste.php?f_stage=draft'" style="cursor: pointer;">
                    <div class="card-body p-3 d-flex flex-column">
                        <i class="fas fa-hourglass-half stat-icon" style="opacity: 0.2; font-size: 2rem; position: absolute; right: 15px; top: 15px;"></i>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <p class="stat-value h3 mb-0"><?= $stats['en_attente'] ?></p>
                            <p class="stat-label mb-0 small text-uppercase fw-bold">En Attente</p>
                        </div>
                        <div class="stat-list-wrapper flex-grow-1" style="min-height: 0; overflow: hidden;">
                            <div class="stat-list-scroll">
                                <?php if (!empty($commandes_en_attente)): ?>
                                    <?php foreach ($commandes_en_attente as $cmd): ?>
                                    <div class="stat-list-item small text-truncate mb-1" onclick="event.stopPropagation(); window.location='commandes_detail.php?id=<?= $cmd['id'] ?>'" style="cursor: pointer;">
                                        <i class="fas fa-circle fa-xs me-1 small opacity-50"></i><?= htmlspecialchars($cmd['nom_affaire'] ?? 'Sans affaire') . (!empty($cmd['designation']) ? ' | ' . htmlspecialchars($cmd['designation']) : '') ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-white-50 small fst-italic">Aucun brouillon</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Commandées -->
            <div class="col-6 col-md-3">
                <div class="stat-card card bg-primary text-white h-100" onclick="window.location='commandes_liste.php?f_stage=ordered'" style="cursor: pointer;">
                    <div class="card-body p-3 d-flex flex-column">
                        <i class="fas fa-shopping-cart stat-icon" style="opacity: 0.2; font-size: 2rem; position: absolute; right: 15px; top: 15px;"></i>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <p class="stat-value h3 mb-0"><?= $stats['commandees'] ?></p>
                            <p class="stat-label mb-0 small text-uppercase fw-bold">Commandées</p>
                        </div>
                        <div class="stat-list-wrapper flex-grow-1" style="min-height: 0; overflow: hidden;">
                            <div class="stat-list-scroll">
                                <?php if (!empty($commandes_commandees)): ?>
                                    <?php foreach ($commandes_commandees as $cmd): ?>
                                    <div class="stat-list-item small d-flex justify-content-between mb-1" onclick="event.stopPropagation(); window.location='commandes_detail.php?id=<?= $cmd['id'] ?>'" style="cursor: pointer;">
                                        <span class="text-truncate" style="max-width: 70%;"><i class="fas fa-circle fa-xs me-1 small opacity-50"></i><?= htmlspecialchars($cmd['nom_affaire'] ?? 'Sans affaire') . (!empty($cmd['designation']) ? ' | ' . htmlspecialchars($cmd['designation']) : '') ?></span>
                                        <span class="fw-bold opacity-75"><?= date('d/m', strtotime($cmd['date_commande'])) ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-white-50 small fst-italic">Aucune commande</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
             <!-- ARC Reçus -->
            <div class="col-6 col-md-3">
                <div class="stat-card card bg-info text-white h-100" onclick="window.location='commandes_liste.php?f_stage=arc'" style="cursor: pointer;">
                    <div class="card-body p-3 d-flex flex-column">
                        <i class="fas fa-envelope-open stat-icon" style="opacity: 0.2; font-size: 2rem; position: absolute; right: 15px; top: 15px;"></i>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <p class="stat-value h3 mb-0"><?= $stats['arc_recus'] ?></p>
                            <p class="stat-label mb-0 small text-uppercase fw-bold">ARC Reçus</p>
                        </div>
                        <div class="stat-list-wrapper flex-grow-1" style="min-height: 0; overflow: hidden;">
                            <div class="stat-list-scroll">
                                <?php if (!empty($commandes_arc_recus)): ?>
                                    <?php foreach ($commandes_arc_recus as $cmd): ?>
                                    <div class="stat-list-item small d-flex justify-content-between mb-1" onclick="event.stopPropagation(); window.location='commandes_detail.php?id=<?= $cmd['id'] ?>'" style="cursor: pointer;">
                                        <span class="text-truncate" style="max-width: 70%;"><i class="fas fa-circle fa-xs me-1 small opacity-50"></i><?= htmlspecialchars($cmd['nom_affaire'] ?? 'Sans affaire') . (!empty($cmd['designation']) ? ' | ' . htmlspecialchars($cmd['designation']) : '') ?></span>
                                        <span class="fw-bold opacity-75"><?= date('d/m', strtotime($cmd['date_arc_recu'])) ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-white-50 small fst-italic">Aucun ARC</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Livraisons -->
            <div class="col-6 col-md-3">
                <div class="stat-card card bg-success text-white h-100 cursor-pointer" onclick="window.location='commandes_liste.php?f_stage=delivery'">
                    <div class="card-body p-3 d-flex flex-column">
                        <i class="fas fa-truck stat-icon" style="opacity: 0.2; font-size: 2rem; position: absolute; right: 15px; top: 15px;"></i>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <p class="stat-value h3 mb-0"><?= $stats['livraisons_prevues'] ?></p>
                            <p class="stat-label mb-0 small text-uppercase fw-bold">LIVRAISONS PRÉVUES</p>
                        </div>
                        <div class="stat-list-wrapper flex-grow-1" style="min-height: 0; overflow: hidden;">
                            <div class="stat-list-scroll">
                                <?php if (!empty($commandes_livraisons)): ?>
                                    <?php foreach ($commandes_livraisons as $cmd): ?>
                                    <div class="stat-list-item small d-flex justify-content-between mb-1 cursor-pointer" onclick="event.stopPropagation(); window.location='commandes_detail.php?id=<?= $cmd['id'] ?>'">
                                        <span class="text-truncate" style="max-width: 60%;"><i class="fas fa-circle fa-xs me-1 small opacity-50"></i><?= htmlspecialchars($cmd['nom_affaire'] ?? 'Sans affaire') . (!empty($cmd['designation']) ? ' | ' . htmlspecialchars($cmd['designation']) : '') ?></span>
                                        <span class="small badge bg-white text-success">J-<?= $cmd['jours_restants'] ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-white-50 small fst-italic">Aucune livraison</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- NEW LAYOUT: CALENDAR TABS + POST-ITS -->
        <div class="row g-3 flex-shrink-0 mt-4 dashboard-bottom-row" style="height: calc(100vh - 420px); min-height: 300px;">
            
            <!-- LEFT COLUMN: CALENDAR TABS (Livraisons / Poses) -->
            <div class="col-lg-4 d-flex flex-column">
                <div class="card shadow-sm border-0 h-100 agenda-card">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <!-- Segmented Control Navigation -->
                        <div class="px-3 py-2">
                            <ul class="nav segmented-control" id="agendaTabs" role="tablist">
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link active" id="metrage-tab" data-bs-toggle="tab" data-bs-target="#metrage-pane" type="button" role="tab">
                                        Métrage
                                    </button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link" id="poses-tab" data-bs-toggle="tab" data-bs-target="#poses-pane" type="button" role="tab">
                                        Poses
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-0 d-flex flex-column">
                        <div class="tab-content flex-grow-1 d-flex flex-column" id="agendaTabsContent">
                            
                            <!-- TAB 1: MÉTRAGES -->
                            <div class="tab-pane fade show active flex-grow-1" id="metrage-pane" role="tabpanel" aria-labelledby="metrage-tab" style="position: relative;">
                                <div class="agenda-scroll-area h-100 overflow-auto" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;">
                                    <?php if (count($agenda_metrages) > 0): ?>
                                        <div class="ag-modern-list">
                                        <?php foreach ($agenda_metrages as $met): ?>
                                        <?php
                                        $jours = $met['jours_restants'];
                                        $is_today = $jours == 0;
                                        $is_urgent = $jours < 0;
                                        
                                        // Dot Class
                                        $dot_class = '';
                                        if ($is_today) $dot_class = 'dot-today';
                                        elseif ($is_urgent) $dot_class = 'dot-urgent';
                                        else $dot_class = 'dot-future';

                                        $date = new DateTime($met['date_rdv']);
                                        ?>
                                        <div class="ag-row" onclick="window.location='gestion_metrage_cockpit.php?id=<?= $met['id'] ?>'">
                                            <div class="ag-date-col">
                                                <div class="ag-date-day"><?= $date->format('d') ?></div>
                                                <div class="ag-date-month"><?= $date->format('M') ?></div>
                                            </div>
                                            <div class="ag-content-col">
                                                <div class="ag-row-title">
                                                    <?= htmlspecialchars($met['nom_affaire']) ?>
                                                    <?php if ($is_today): ?>
                                                        <span class="badge badge-subtle-warning rounded-pill ms-2 small" style="font-size: 0.7em;">AUJ</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ag-row-subtitle">
                                                    <span class="ag-status-dot <?= $dot_class ?>"></span>
                                                    <?= htmlspecialchars($met['client_nom']) ?>
                                                    <?php if(!empty($met['ville_chantier'])): ?>
                                                        <span class="opacity-75"> • <?= htmlspecialchars($met['ville_chantier']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($is_urgent): ?>
                                            <div class="text-end">
                                                <span class="text-danger small fw-bold">J-<?= abs($jours) ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted opacity-50">
                                            <i class="fas fa-ruler-combined fa-4x mb-3"></i>
                                            <p class="fs-5">Aucun métrage prévu</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- TAB 2: POSES -->
                            <div class="tab-pane fade flex-grow-1" id="poses-pane" role="tabpanel" aria-labelledby="poses-tab" style="position: relative;">
                                <div class="agenda-scroll-area h-100 overflow-auto" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;">
                                    <?php if (count($agenda_poses) > 0): ?>
                                        <div class="ag-modern-list">
                                        <?php foreach ($agenda_poses as $pose): ?>
                                        <?php
                                        $jours = $pose['jours_avant_debut'];
                                        $is_today = $jours == 0;
                                        
                                        // Dot
                                        $dot_class = $is_today ? 'dot-today' : 'dot-future';
                                        
                                        $date = new DateTime($pose['date_rdv']);
                                        ?>
                                        <div class="ag-row" onclick="window.location='affaires_detail.php?id=<?= $pose['affaire_id'] ?>#poses'">
                                            <div class="ag-date-col">
                                                <div class="ag-date-day"><?= $date->format('d') ?></div>
                                                <div class="ag-date-month"><?= $date->format('M') ?></div>
                                            </div>
                                            <div class="ag-content-col">
                                                <div class="ag-row-title">
                                                    <?= htmlspecialchars($pose['nom_affaire']) ?>
                                                    <?php if ($is_today): ?>
                                                        <span class="badge badge-subtle-warning rounded-pill ms-2 small" style="font-size: 0.7em;">AUJ</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ag-row-subtitle">
                                                    <span class="ag-status-dot <?= $dot_class ?>"></span>
                                                    <?= htmlspecialchars($pose['client_nom']) ?>
                                                    <?php if (!empty($pose['notes'])): ?>
                                                        <span class="opacity-75"> • <?= htmlspecialchars($pose['notes']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted opacity-50">
                                            <i class="fas fa-hard-hat fa-4x mb-3"></i>
                                            <p class="fs-5">Aucune pose</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RIGHT COLUMN: POST-ITS SIDEBAR -->
            <div class="col-lg-8 d-flex flex-column">
                <div id="postits-container" class="card shadow-sm border-0 h-100 bg-transparent" style="position: relative; overflow: hidden;">
                    <!-- Full Card Canvas -->
                    <div id="postits-wrapper" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;">
                        <!-- JS injected here -->
                    </div>

                    <!-- Floating Header (Interactivity Layer) -->
                    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-end px-3 pt-3 pb-2" style="position: relative; z-index: 2; pointer-events: none;">
                        <button class="btn btn-sm btn-outline-primary btn-add-postit rounded-circle shadow-sm bg-white" data-bs-toggle="modal" data-bs-target="#modalAddPostit" style="pointer-events: auto; z-index: 10001;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            
        </div>
        
     
        
    </div>
</div>
<!-- End Dashboard Content -->



</div>
<!-- End Dashboard Content -->

<!-- Modal Add Post-it (Moved to Root) -->
<div class="modal fade" id="modalAddPostit" tabindex="-1" aria-hidden="true" style="z-index: 10000;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-dark">Nouveau Mémo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <textarea class="form-control text-dark bg-white" id="new-postit-content" rows="4" placeholder="Écrire une note..." style="border: 1px solid #dee2e6; opacity: 1;"></textarea>
                </div>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <div class="form-check form-check-inline p-0 m-0">
                        <input class="btn-check" type="radio" name="postit-color" id="color-jaune" value="jaune" checked>
                        <label class="btn btn-sm rounded-circle border-0" for="color-jaune" style="background-color: #fcf0ad; width: 30px; height: 30px;"></label>
                    </div>
                    <div class="form-check form-check-inline p-0 m-0">
                        <input class="btn-check" type="radio" name="postit-color" id="color-bleu" value="bleu">
                        <label class="btn btn-sm rounded-circle border-0" for="color-bleu" style="background-color: #cbf1f5; width: 30px; height: 30px;"></label>
                    </div>
                    <div class="form-check form-check-inline p-0 m-0">
                        <input class="btn-check" type="radio" name="postit-color" id="color-vert" value="vert">
                        <label class="btn btn-sm rounded-circle border-0" for="color-vert" style="background-color: #d4f1b4; width: 30px; height: 30px;"></label>
                    </div>
                    <div class="form-check form-check-inline p-0 m-0">
                        <input class="btn-check" type="radio" name="postit-color" id="color-rose" value="rose">
                        <label class="btn btn-sm rounded-circle border-0" for="color-rose" style="background-color: #ffcce0; width: 30px; height: 30px;"></label>
                    </div>
                </div>
                <button type="button" class="btn btn-primary w-100 rounded-pill" onclick="savePostit()">Coller</button>
            </div>
        </div>
    </div>
</div>

<link href="assets/css/dashboard_postits.css?v=<?= time() ?>" rel="stylesheet">
<script src="assets/js/dashboard_postits.js?v=<?= time() ?>"></script>
<?php require_once 'footer.php'; ?>