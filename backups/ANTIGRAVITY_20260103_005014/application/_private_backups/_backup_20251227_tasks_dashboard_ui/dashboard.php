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

try {
    // 1. LISTES DE COMMANDES POUR LES TUILES
    
    // Commandes en attente (date_en_attente renseignée mais pas date_commande)
    $stmt = $pdo->query("
        SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        LEFT JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_en_attente IS NOT NULL AND ca.date_commande IS NULL
        ORDER BY ca.date_en_attente DESC
        LIMIT 10
    ");
    $commandes_en_attente = $stmt->fetchAll();
    $stats['en_attente'] = count($commandes_en_attente);
    
    // Commandes commandées (date_commande renseignée mais pas ARC)
    $stmt = $pdo->query("
        SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire, ca.date_commande
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        LEFT JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_commande IS NOT NULL AND ca.date_arc_recu IS NULL
        ORDER BY ca.date_commande DESC
        LIMIT 10
    ");
    $commandes_commandees = $stmt->fetchAll();
    $stats['commandees'] = count($commandes_commandees);
    
    // Commandes ARC reçu (ARC reçu mais pas encore livrées)
    $stmt = $pdo->query("
        SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire, ca.date_arc_recu
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        LEFT JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_arc_recu IS NOT NULL AND ca.date_livraison_reelle IS NULL
        ORDER BY ca.date_arc_recu DESC
        LIMIT 10
    ");
    $commandes_arc_recus = $stmt->fetchAll();
    $stats['arc_recus'] = count($commandes_arc_recus);
    
    // Livraisons prévues (date_livraison_prevue renseignée mais pas livrées)
    $stmt = $pdo->query("
        SELECT ca.id, ca.ref_interne, f.nom as fournisseur_nom, a.nom_affaire, ca.date_livraison_prevue,
               DATEDIFF(ca.date_livraison_prevue, CURDATE()) as jours_restants
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        LEFT JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_livraison_prevue IS NOT NULL AND ca.date_livraison_reelle IS NULL
        ORDER BY ca.date_livraison_prevue ASC
        LIMIT 10
    ");
    $commandes_livraisons = $stmt->fetchAll();
    $stats['livraisons_prevues'] = count($commandes_livraisons);
    
    // 2. AGENDA LIVRAISONS (30 prochains jours)
    $stmt = $pdo->query("
        SELECT ca.*, f.nom as fournisseur_nom, a.nom_affaire,
               DATEDIFF(ca.date_prevue_cible, CURDATE()) as jours_restants
        FROM commandes_achats ca
        JOIN fournisseurs f ON ca.fournisseur_id = f.id
        LEFT JOIN affaires a ON ca.affaire_id = a.id
        WHERE ca.date_prevue_cible BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND ca.date_livraison_reelle IS NULL
        ORDER BY ca.date_prevue_cible ASC
        LIMIT 15
    ");
    $agenda_livraisons = $stmt->fetchAll();
    
    // 3. AGENDA POSES CHANTIER (30 prochains jours)
    $stmt = $pdo->query("
        SELECT a.*, c.nom_principal as client_nom,
               DATEDIFF(a.date_pose_debut, CURDATE()) as jours_avant_debut
        FROM affaires a
        JOIN clients c ON a.client_id = c.id
        WHERE a.date_pose_debut BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND a.statut_chantier IN ('Planifié', 'En Cours')
        ORDER BY a.date_pose_debut ASC
        LIMIT 15
    ");
    $agenda_poses = $stmt->fetchAll();
    
    // 4. ALERTES POUR LE TICKER ET CLOCHE
    $alertes = getGlobalAlerts($pdo);
    $nb_alertes = count($alertes);
    
} catch (Exception $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<!-- Redirecting to correct structure: header.php handles structure now -->
<?php 
$page_title = 'Dashboard';
require_once 'header.php'; 
?>

<!-- FIX LAYOUT DASHBOARD -->
<div id="dashboard-content" style="position: relative; z-index: 10; margin-top: -75px; padding-top: 20px;">
    <div class="container-fluid px-4">
<!-- Container is already opened in header.php -->



<style>
    /* CSS RESTAURE (Glassmorphism & Gradients) */
    
    /* Tuiles Glassmorphism */
    .stat-card {
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
        height: 100%;
        cursor: pointer;
        z-index: 1; 
    }
    .stat-card:hover { transform: translateY(-8px); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2); background: rgba(255, 255, 255, 0.15); }
    
    /* Couleurs & Gradients */
    .stat-card.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; border-color: rgba(245, 158, 11, 0.3); }
    .stat-card.bg-primary { background: linear-gradient(135deg, #0f4c75, #1e6091) !important; border-color: rgba(15, 76, 117, 0.3); }
    .stat-card.bg-info { background: linear-gradient(135deg, #0d9488, #0f766e) !important; border-color: rgba(13, 148, 136, 0.3); }
    .stat-card.bg-success { background: linear-gradient(135deg, #10b981, #059669) !important; border-color: rgba(16, 185, 129, 0.3); }

    /* Icones */
    .stat-icon { font-size: 1.5rem; opacity: 0.2; position: absolute; right: 15px; top: 15px; z-index: 1; }
    .stat-value { 
        font-size: 1.5rem; 
        font-weight: 700; 
        line-height: 1; 
        margin-bottom: 0; 
        position: relative; 
        z-index: 2; 
        color: white !important; 
    }
    .stat-label { 
        font-size: 0.8rem; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        opacity: 0.9; 
        margin: 0; 
        position: relative; 
        z-index: 2; 
        padding-top: 2px; 
        color: rgba(255,255,255,0.9) !important; 
    }
    
    /* Scroll Vertical Configuration */
    .stat-list-wrapper {
        height: 145px; 
        overflow: hidden;
        position: relative;
        margin-top: 5px;
        z-index: 5;
        /* Masks for fade effect */
        mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
        -webkit-mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
    }
    .stat-list-scroll { 
        animation: scrollVertical 20s linear infinite; 
    }
    .stat-list-wrapper:hover .stat-list-scroll { 
        animation-play-state: paused; 
    }
    .stat-list-item {
        padding: 8px 5px;
        border-bottom: 1px solid rgba(255,255,255,0.15);
        font-size: 0.8rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: background 0.2s;
        color: white;
    }
    .stat-list-item:hover { background: rgba(255,255,255,0.2); border-radius: 4px; }
    
    .stat-item-sub { color: rgba(255,255,255,0.7) !important; }

    /* Animation Keyframes */
    @keyframes scrollVertical { 
        0% { transform: translateY(0); } 
        100% { transform: translateY(-50%); } 
    }

    /* Ticker styles if needed override */
    .alert-ticker { background: #0f4c75 !important; }

</style>

        
        <!-- En-tête avec Bandeau et Cloche -->

        
        <!-- TUILES KPI -->
        <div class="row g-4 mb-4">
            <!-- En Attente -->
            <div class="col-md-3">
                <div class="stat-card card bg-warning text-white" onclick="window.location='commandes_liste.php?statut=brouillon'" style="cursor: pointer;">
                    <div class="card-body">
                        <i class="fas fa-hourglass-half stat-icon"></i>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <p class="stat-value"><?= $stats['en_attente'] ?></p>
                            <p class="stat-label mb-0">En Attente</p>
                        </div>
                        <div class="stat-list-wrapper">
                            <div class="stat-list-scroll">
                                <?php if (!empty($commandes_en_attente)): ?>
                                    <?php for($i=0; $i<2; $i++): foreach ($commandes_en_attente as $cmd): ?>
                                    <div class="stat-list-item" onclick="event.stopPropagation(); window.location='commandes_detail.php?id=<?= $cmd['id'] ?>'" style="cursor: pointer;">
                                        <div>
                                            <div class="stat-item-main"><?= htmlspecialchars($cmd['nom_affaire'] ?? 'Sans affaire') ?></div>
                                            <div class="stat-item-sub text-white-50"><?= htmlspecialchars($cmd['fournisseur_nom']) ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; endfor; ?>
                                <?php else: ?>
                                    <div class="text-center p-3 text-white-50">Aucun brouillon</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Commandées -->
            <div class="col-md-3">
                <div class="stat-card card bg-primary text-white" onclick="window.location='commandes_liste.php?statut=commandee'" style="cursor: pointer;">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart stat-icon"></i>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <p class="stat-value"><?= $stats['commandees'] ?></p>
                            <p class="stat-label mb-0">Commandées</p>
                        </div>
                        <div class="stat-list-wrapper">
                            <div class="stat-list-scroll">
                                <?php if (!empty($commandes_commandees)): ?>
                                    <?php for($i=0; $i<2; $i++): foreach ($commandes_commandees as $cmd): ?>
                                    <div class="stat-list-item" onclick="event.stopPropagation(); window.location='commandes_detail.php?id=<?= $cmd['id'] ?>'" style="cursor: pointer;">
                                        <div>
                                            <div class="stat-item-main"><?= htmlspecialchars($cmd['nom_affaire'] ?? 'Sans affaire') ?></div>
                                            <div class="stat-item-sub text-white-50"><?= htmlspecialchars($cmd['fournisseur_nom']) ?></div>
                                        </div>
                                        <div class="text-end fw-bold"><?= date('d/m', strtotime($cmd['date_commande'])) ?></div>
                                    </div>
                                    <?php endforeach; endfor; ?>
                                <?php else: ?>
                                    <div class="text-center p-3 text-white-50">Aucune commande</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
             <!-- ARC Reçus -->
            <div class="col-md-3">
                <div class="stat-card card bg-info text-white" onclick="window.location='commandes_liste.php?statut=arc_recu'" style="cursor: pointer;">
                    <div class="card-body">
                        <i class="fas fa-envelope-open stat-icon"></i>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <p class="stat-value"><?= $stats['arc_recus'] ?></p>
                            <p class="stat-label mb-0">ARC Reçus</p>
                        </div>
                        <div class="stat-list-wrapper">
                            <div class="stat-list-scroll">
                                <?php if (!empty($commandes_arc_recus)): ?>
                                    <?php for($i=0; $i<2; $i++): foreach ($commandes_arc_recus as $cmd): ?>
                                    <div class="stat-list-item" onclick="event.stopPropagation(); window.location='commandes_detail.php?id=<?= $cmd['id'] ?>'" style="cursor: pointer;">
                                        <div>
                                            <div class="stat-item-main"><?= htmlspecialchars($cmd['nom_affaire'] ?? 'Sans affaire') ?></div>
                                            <div class="stat-item-sub text-white-50"><?= htmlspecialchars($cmd['fournisseur_nom']) ?></div>
                                        </div>
                                        <div class="text-end fw-bold"><?= date('d/m', strtotime($cmd['date_arc_recu'])) ?></div>
                                    </div>
                                    <?php endforeach; endfor; ?>
                                <?php else: ?>
                                    <div class="text-center p-3 text-white-50">Aucun ARC</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Livraisons -->
            <div class="col-md-3">
                <div class="stat-card card bg-success text-white" onclick="window.location='commandes_liste.php?statut=livraison'" style="cursor: pointer;">
                    <div class="card-body">
                        <i class="fas fa-truck stat-icon"></i>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <p class="stat-value"><?= $stats['livraisons_prevues'] ?></p>
                            <p class="stat-label mb-0">LIVRAISON PRÉVUE</p>
                        </div>
                        <div class="stat-list-wrapper">
                            <div class="stat-list-scroll">
                                <?php if (!empty($commandes_livraisons)): ?>
                                    <?php for($i=0; $i<2; $i++): foreach ($commandes_livraisons as $cmd): ?>
                                    <div class="stat-list-item" onclick="event.stopPropagation(); window.location='commandes_detail.php?id=<?= $cmd['id'] ?>'" style="cursor: pointer;">
                                        <div>
                                            <div class="stat-item-main"><?= htmlspecialchars($cmd['nom_affaire'] ?? 'Sans affaire') ?></div>
                                            <div class="stat-item-sub text-white-50"><?= htmlspecialchars($cmd['fournisseur_nom']) ?></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="small badge bg-white text-success mb-1">J-<?= $cmd['jours_restants'] ?></div>
                                            <div class="fw-bold"><?= date('d/m', strtotime($cmd['date_livraison_prevue'])) ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; endfor; ?>
                                <?php else: ?>
                                    <div class="text-center p-3 text-white-50">Aucune livraison</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- AGENDAS -->
        <div class="row g-4" id="agenda-livraisons">
            
            <!-- AGENDA LIVRAISONS -->
            <div class="col-lg-6">
                <div class="agenda-card card">
                    <div class="card-header">
                        <i class="fas fa-truck me-2 text-success"></i>
                        Agenda Livraisons (30 prochains jours)
                    </div>
                    <div class="card-body p-0" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                        <?php if (count($agenda_livraisons) > 0): ?>
                            <?php foreach ($agenda_livraisons as $liv): ?>
                            <?php
                            $jours = $liv['jours_restants'];
                            $is_today = $jours == 0;
                            $is_urgent = $jours < 0;
                            $item_class = $is_today ? 'today' : ($is_urgent ? 'urgent' : '');
                            $date = new DateTime($liv['date_prevue_cible']);
                            ?>
                            <div class="agenda-item <?= $item_class ?>" onclick="window.location='commandes_detail.php?id=<?= $liv['id'] ?>'">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="date-badge">
                                        <div class="day"><?= $date->format('d') ?></div>
                                        <div class="month"><?= $date->format('M') ?></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?= htmlspecialchars($liv['fournisseur_nom']) ?>
                                            <span class="badge bg-secondary ms-2">#<?= htmlspecialchars($liv['ref_interne']) ?></span>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-folder me-1"></i><?= htmlspecialchars($liv['nom_affaire'] ?? 'Sans affaire') ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($is_urgent): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Retard: <?= abs($jours) ?>j
                                            </span>
                                        <?php elseif ($is_today): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>
                                                Aujourd'hui
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info">
                                                J-<?= $jours ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="mt-1 small text-muted">
                                            <?= number_format($liv['montant_total_ht'] ?? 0, 0, ',', ' ') ?> €
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fas fa-calendar-check fa-3x mb-3"></i>
                                <p>Aucune livraison prévue dans les 30 prochains jours</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- AGENDA POSES CHANTIER -->
            <div class="col-lg-6">
                <div class="agenda-card card">
                    <div class="card-header">
                        <i class="fas fa-hard-hat me-2 text-warning"></i>
                        Agenda Poses Chantier (30 prochains jours)
                    </div>
                    <div class="card-body p-0" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                        <?php if (count($agenda_poses) > 0): ?>
                            <?php foreach ($agenda_poses as $pose): ?>
                            <?php
                            $jours = $pose['jours_avant_debut'];
                            $is_today = $jours == 0;
                            $is_soon = $jours >= 0 && $jours <= 3;
                            $item_class = $is_today ? 'today' : '';
                            $date = new DateTime($pose['date_pose_debut']);
                            ?>
                            <div class="agenda-item <?= $item_class ?>" onclick="window.location='affaires_detail.php?id=<?= $pose['id'] ?>'">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="date-badge">
                                        <div class="day"><?= $date->format('d') ?></div>
                                        <div class="month"><?= $date->format('M') ?></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?= htmlspecialchars($pose['nom_affaire']) ?>
                                            <span class="badge bg-primary ms-2">#<?= htmlspecialchars($pose['numero_prodevis']) ?></span>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($pose['client_nom']) ?>
                                        </small>
                                        <?php if ($pose['equipe_pose']): ?>
                                        <br><small class="text-muted">
                                            <i class="fas fa-users me-1"></i><?= htmlspecialchars($pose['equipe_pose']) ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($is_today): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>
                                                Aujourd'hui
                                            </span>
                                        <?php elseif ($is_soon): ?>
                                            <span class="badge bg-info">
                                                J-<?= $jours ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                Dans <?= $jours ?>j
                                            </span>
                                        <?php endif; ?>
                                        <div class="mt-1">
                                            <!-- badge_statut function assumed to output proper HTML -->
                                            <span class="badge bg-secondary"><?= $pose['statut_chantier'] ?? 'Planifié' ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p>Aucune pose planifiée dans les 30 prochains jours</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
        

        
    </div>
</div>
<!-- End Dashboard Content -->

<?php require_once 'footer.php'; ?>