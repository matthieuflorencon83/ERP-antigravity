<?php
// besoins_saisie_v2.php
require_once 'db.php';      
require_once 'functions.php'; 
require_once 'auth.php';    
require_once 'header.php';  

// Initial List for Step 1
$stmt = $pdo->query("SELECT id, nom_affaire FROM affaires WHERE statut != 'TERMINE' ORDER BY nom_affaire");
$affaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

$affaire_id = $_GET['id'] ?? 0;
?>

<style>
    .funnel-step {
        border-left: 3px solid #e9ecef;
        padding-left: 15px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    .funnel-step.active {
        border-color: var(--bs-primary);
    }
    .funnel-step.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
    .step-number {
        font-weight: bold;
        color: var(--bs-primary);
        margin-right: 5px;
    }
    .article-card.selected {
        border: 2px solid var(--bs-success);
        background-color: #f0fff4;
    }
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- LEFT: THE FUNNEL (1/3) -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Saisie</h5>
                    <button class="btn btn-sm btn-outline-light" onclick="resetFunnel()">Reset</button>
                </div>
                <div class="card-body p-3">
                    <form id="form_besoin" onsubmit="event.preventDefault();">
                        
                        <!-- STEP 1: AFFAIRE -->
                        <div class="funnel-step active" id="step_1">
                            <label class="form-label fw-bold small text-muted text-uppercase">1. Contexte</label>
                            <select class="form-select form-select-sm mb-2" name="affaire_id" id="affaire_id" required>
                                <option value="">Sélectionner Affaire...</option>
                                <?php foreach ($affaires as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $affaire_id == $a['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['nom_affaire']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-control form-control-sm" name="zone" id="zone_chantier" placeholder="Zone (ex: Façade Sud)">
                        </div>

                        <!-- STEP 2: FOURNISSEUR -->
                        <div class="funnel-step disabled" id="step_2">
                            <label class="form-label fw-bold small text-muted text-uppercase">2. Fournisseur</label>
                            <select class="form-select form-select-sm" name="fournisseur_id" id="fournisseur_id">
                                <option value="">Chargement...</option>
                            </select>
                        </div>

                        <!-- STEP 3: FAMILLE -->
                        <div class="funnel-step disabled" id="step_3">
                            <label class="form-label fw-bold small text-muted text-uppercase">3. Famille</label>
                            <div class="d-grid gap-2" style="grid-template-columns: repeat(2, 1fr);" id="family_grid">
                                <!-- JS injected buttons -->
                            </div>
                            <input type="hidden" name="famille_id" id="famille_id">
                        </div>

                        <!-- STEP 4: SOUS-FAMILLE -->
                        <div class="funnel-step disabled" id="step_4">
                            <label class="form-label fw-bold small text-muted text-uppercase">4. Sous-Famille</label>
                            <select class="form-select form-select-sm" name="sous_famille_id" id="sous_famille_id">
                                <option value="">Choisir...</option>
                            </select>
                        </div>

                        <!-- STEP 5: ARTICLE -->
                        <div class="funnel-step disabled" id="step_5">
                            <label class="form-label fw-bold small text-muted text-uppercase">5. Produit</label>
                            <select class="form-select form-select-sm" name="article_id" id="article_id" size="5" style="height: 150px;">
                                <option value="">Sélectionner Produit...</option>
                            </select>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: DETAILS & LIST (2/3) -->
        <div class="col-md-8">
            
            <!-- DETAIL PANEL (New) -->
            <div class="card shadow-sm border-0 mb-3" id="detail_panel" style="background: #f8f9fa;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center text-muted">
                            <i class="fas fa-box-open fa-3x" id="detail_icon"></i>
                            <img src="" id="detail_img" class="img-fluid rounded d-none" style="max-height: 80px;">
                        </div>
                        <div class="col-md-10">
                            <h5 class="card-title mb-1" id="detail_title">Sélectionnez un article...</h5>
                            <p class="card-text text-muted small mb-2" id="detail_ref">-</p>
                            
                            <!-- CRITERIA INJECTION -->
                            <div id="criteria_panel" class="bg-white p-3 rounded border shadow-sm d-none">
                                <!-- JS Injected Fields (Length, Color Select, Qty) -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

             <!-- LIST OF ITEMS -->
             <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-secondary fw-bold">PANIER EN COURS</h6>
                    <span class="badge bg-primary rounded-pill" id="list_count">0</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-sm" id="table_besoins">
                        <thead class="table-light">
                            <tr>
                                <th>Designation</th>
                                <th>Dimensions / Finition</th>
                                <th>Qté</th>
                                <th>Optimisation</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" class="text-center text-muted py-5 small">Votre panier est vide.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/modules/besoins_v2.js?v=<?= time() ?>"></script>
<?php require_once 'footer.php'; ?>
