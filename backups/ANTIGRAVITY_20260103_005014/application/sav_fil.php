<?php
// sav_fil.php - Fil d'actualité SAV
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'SAV - Fil d\'actualité';
require_once 'header.php';

// Récupération des tickets récents
$sql = "SELECT t.*, c.nom_principal as client_nom, c.ville as client_ville, u.nom_complet as createur_nom
        FROM sav_tickets t
        LEFT JOIN clients c ON t.client_id = c.id
        LEFT JOIN utilisateurs u ON t.created_by = u.id
        ORDER BY t.statut = 'RESOLU', t.urgence DESC, t.date_creation DESC
        LIMIT 50";
$tickets = $pdo->query($sql)->fetchAll();
?>

<div class="container-fluid px-4 py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0 text-primary">
            <i class="fas fa-stream me-2"></i>Fil d'actualité SAV
        </h2>
        <a href="sav_creation.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus-circle me-2"></i>Nouveau Ticket
        </a>
    </div>

    <!-- FILTRAGE RAPIDE (A implémenter plus tard si besoin) -->
    <div class="d-flex gap-2 mb-4 overflow-auto pb-2">
        <button class="btn btn-dark rounded-pill px-4">Tout</button>
        <button class="btn btn-outline-secondary rounded-pill px-4">En Diagnostic</button>
        <button class="btn btn-outline-secondary rounded-pill px-4">À Planifier</button>
        <button class="btn btn-outline-secondary rounded-pill px-4 text-danger fw-bold">Urgents (3)</button>
    </div>

    <div class="row g-3">
        <?php foreach($tickets as $t): 
            $nom = $t['client_nom'] ?? $t['prospect_nom'];
            $ville = $t['client_ville'] ?? $t['prospect_ville'];
            $badgeColor = match($t['statut']) {
                'OUVERT', 'EN_DIAGNOSTIC' => 'warning',
                'A_PLANIFIER', 'PIECE_A_COMMANDER' => 'info',
                'RESOLU', 'FACTURE' => 'success',
                default => 'secondary'
            };
            $urgenceIcon = ($t['urgence'] == 3) ? '<i class="fas fa-fire text-danger me-1" title="Urgent"></i>' : '';
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 folder-card animate__animated animate__fadeIn">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-<?= $badgeColor ?> mb-2"><?= $t['statut'] ?></span>
                        <span class="text-muted small"><?= date('d/m H:i', strtotime($t['date_creation'])) ?></span>
                    </div>
                    
                    <h5 class="fw-bold mb-1">
                        <?= $urgenceIcon ?>
                        <?= htmlspecialchars($nom) ?> 
                        <span class="fw-normal text-muted ms-1 fs-6">(<?= htmlspecialchars($ville) ?>)</span>
                    </h5>
                    <div class="text-muted small mb-2">Ticket #<?= $t['numero_ticket'] ?></div>
                    
                    <p class="text-secondary small bg-light p-2 rounded mb-3" style="min-height: 50px;">
                        <?= nl2br(htmlspecialchars(substr($t['description_initiale'] ?? '', 0, 100))) ?>...
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <div class="small text-muted"> <i class="fas fa-headset me-1"></i> <?= htmlspecialchars($t['createur_nom'] ?? 'Système') ?></div>
                        
                        <div class="d-flex gap-2">
                             <?php if($t['statut'] === 'PIECE_A_COMMANDER'): ?>
                                <form action="sav_generer_commande.php" method="POST" onsubmit="return confirm('Générer une commande fournisseur pour ce ticket ?');">
                                    <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm">
                                        <i class="fas fa-shopping-cart me-1"></i>Commander
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="sav_mobile_diag.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Ouvrir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
