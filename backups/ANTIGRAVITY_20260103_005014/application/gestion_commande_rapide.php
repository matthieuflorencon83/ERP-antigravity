<?php
// gestion_commande_rapide.php
// Module : Commande Express & Imputation
// Architecture : Zone A (Contexte) + Zone B (Modules)
// Auteur: Antigravity Architect

require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'Commande Rapide';
require_once 'header.php';
?>

<!-- CUSTOM CSS FOR CARD GRID -->
<style>
    .module-card {
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        border-color: var(--bs-primary);
    }
    .module-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: var(--bs-primary);
    }
    .imputation-locked {
        opacity: 0.5;
        pointer-events: none;
        filter: grayscale(1);
    }
    .imputation-active {
        border: 2px solid var(--bs-success) !important;
        background-color: #f8fff9;
    }
</style>

<div class="container-fluid px-4 mt-4">

    <!-- ZONE A : IMPUTATION (Contexte) -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-4">
            <h5 class="fw-bold text-uppercase text-secondary mb-3"><i class="fas fa-tag me-2"></i>Imputation (Obligatoire)</h5>
            
            <div class="row align-items-center">
                <!-- SWITCH STOCK / AFFAIRE -->
                <div class="col-md-4">
                    <div class="btn-group w-100" role="group" aria-label="Choix Imputation">
                        <input type="radio" class="btn-check" name="imputation_type" id="imp_stock" value="STOCK" autocomplete="off">
                        <label class="btn btn-outline-danger fw-bold py-3" for="imp_stock">
                            <i class="fas fa-warehouse me-2"></i>STOCK / ATELIER
                        </label>

                        <input type="radio" class="btn-check" name="imputation_type" id="imp_affaire" value="AFFAIRE" autocomplete="off">
                        <label class="btn btn-outline-success fw-bold py-3" for="imp_affaire">
                            <i class="fas fa-briefcase me-2"></i>AFFAIRE CLIENT
                        </label>
                    </div>
                </div>

                <!-- SELECTEUR D'AFFAIRE (Masqué par défaut) -->
                <div class="col-md-6" id="affaire_selector_wrapper" style="display:none;">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="affaire_search" class="form-control" placeholder="Tapez le nom du client ou un n° commande..." list="affaires_list">
                        <datalist id="affaires_list">
                            <!-- Populated via AJAX later -->
                            <option value="DURAND - Pose Véranda (24-001)">
                            <option value="MARTIN - Fenêtres (24-002)">
                        </datalist>
                        <input type="hidden" id="selected_affaire_id">
                    </div>
                </div>

                <!-- VALIDATION VISUELLE -->
                <div class="col-md-2 text-center" id="imputation_status">
                    <span class="badge bg-secondary p-2">En attente...</span>
                </div>
            </div>
            
            <!-- Message Info -->
            <div class="mt-2 text-muted small fst-italic ps-1" id="imputation_helper">
                <i class="fas fa-info-circle me-1"></i>Veuillez choisir "STOCK" pour la maintenance interne ou "AFFAIRE" pour un client.
            </div>
        </div>
    </div>

    <!-- ZONE B : MODULES (Speed Dial) -->
    <!-- Grisé tant que l'imputation n'est pas valide -->
    <div id="modules_grid" class="imputation-locked">
        <h5 class="fw-bold text-secondary mb-3 ps-1">CHOISISSEZ UN MODULE :</h5>
        
        <div class="row g-4 row-cols-1 row-cols-md-3 row-cols-xl-6">
            
            <!-- 1. VITRAGE -->
            <div class="col">
                <div class="card h-100 module-card shadow-sm text-center p-4" onclick="loadModule('vitrage')">
                    <div class="module-icon"><i class="fas fa-border-all"></i></div>
                    <h6 class="fw-bold text-dark">VITRAGE</h6>
                    <small class="text-muted">Casse, Manque, Simple</small>
                </div>
            </div>

            <!-- 2. PLIAGE (Tôlerie) -->
            <div class="col">
                <div class="card h-100 module-card shadow-sm text-center p-4" onclick="loadModule('pliage')">
                    <div class="module-icon"><i class="fas fa-shapes"></i></div>
                    <h6 class="fw-bold text-dark">PLIAGE</h6>
                    <small class="text-muted">Bavettes, Équerres</small>
                </div>
            </div>

            <!-- 3. PROFIL ALU -->
            <div class="col">
                <div class="card h-100 module-card shadow-sm text-center p-4" onclick="loadModule('profil')">
                    <div class="module-icon"><i class="fas fa-bars"></i></div>
                    <h6 class="fw-bold text-dark">PROFIL ALU</h6>
                    <small class="text-muted">Barres, Coupes</small>
                </div>
            </div>

            <!-- 4. PANNEAUX -->
            <div class="col">
                <div class="card h-100 module-card shadow-sm text-center p-4" onclick="loadModule('panneaux')">
                    <div class="module-icon"><i class="fas fa-layer-group"></i></div>
                    <h6 class="fw-bold text-dark">PANNEAUX</h6>
                    <small class="text-muted">Toiture, Remplissage</small>
                </div>
            </div>

            <!-- 5. QUINCAILLERIE -->
            <div class="col">
                <div class="card h-100 module-card shadow-sm text-center p-4" onclick="loadModule('quincaillerie')">
                    <div class="module-icon"><i class="fas fa-tools"></i></div>
                    <h6 class="fw-bold text-dark">QUINCAILLERIE</h6>
                    <small class="text-muted">Vis, Silicone, Serrures</small>
                </div>
            </div>

            <!-- 6. LIBRE -->
            <div class="col">
                <div class="card h-100 module-card shadow-sm text-center p-4" onclick="loadModule('libre')">
                    <div class="module-icon"><i class="fas fa-pen-fancy"></i></div>
                    <h6 class="fw-bold text-dark">CHAMP LIBRE</h6>
                    <small class="text-muted">Divers, Location</small>
                </div>
            </div>
            
        </div>
    </div>

    <!-- ZONE C : FORMULAIRE DYNAMIQUE (Ajax Container) -->
    <div id="form_container" class="mt-5" style="display:none; min-height: 400px;">
        <!-- Chargé dynamiquement -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Chargement du module...</p>
        </div>
    </div>

</div>

<!-- JAVASCRIPT CORE -->
<script src="assets/js/modules/commande_rapide/vitrage.js?v=<?= time() ?>"></script>
<script src="assets/js/modules/commande_rapide/pliage_canvas.js?v=<?= time() ?>"></script>
<script src="assets/js/modules/commande_rapide/profil.js?v=<?= time() ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const impStock = document.getElementById('imp_stock');
    const impAffaire = document.getElementById('imp_affaire');
    const affSelector = document.getElementById('affaire_selector_wrapper');
    const affSearch = document.getElementById('affaire_search');
    const modulesGrid = document.getElementById('modules_grid');
    const statusBadge = document.getElementById('imputation_status');

    // 1. GESTION IMPUTATION
    function updateImputationState() {
        if (impStock.checked) {
            // MODE STOCK
            affSelector.style.display = 'none';
            unlockModules(true);
            statusBadge.innerHTML = '<span class="badge bg-danger p-2"><i class="fas fa-check me-1"></i>STOCK</span>';
        } 
        else if (impAffaire.checked) {
            // MODE AFFAIRE
            affSelector.style.display = 'block';
            affSearch.focus();
            
            // On re-verrouille tant que l'affaire n'est pas choisie
            if (affSearch.value.trim() !== '') {
               unlockModules(true);
               statusBadge.innerHTML = '<span class="badge bg-success p-2"><i class="fas fa-check me-1"></i>AFFAIRE VALIDE</span>';
            } else {
               unlockModules(false);
               statusBadge.innerHTML = '<span class="badge bg-warning text-dark p-2">SÉLECTIONNER AFFAIRE</span>';
            }
        }
    }

    function unlockModules(unlock) {
        if (unlock) {
            modulesGrid.classList.remove('imputation-locked');
            modulesGrid.style.opacity = '1';
            modulesGrid.style.pointerEvents = 'auto';
            modulesGrid.style.filter = 'none';
        } else {
            modulesGrid.classList.add('imputation-locked');
            modulesGrid.style.opacity = '0.5';
            modulesGrid.style.pointerEvents = 'none';
            modulesGrid.style.filter = 'grayscale(1)';
            // Hide form container if locked
            document.getElementById('form_container').style.display = 'none';
        }
    }

    // Bind Events
    impStock.addEventListener('change', updateImputationState);
    impAffaire.addEventListener('change', updateImputationState);
    
    // Simulation Autocomplete Validation
    affSearch.addEventListener('input', function() {
        if (this.value.length > 3) {
            // Simulate selection
             unlockModules(true);
             statusBadge.innerHTML = '<span class="badge bg-success p-2"><i class="fas fa-check me-1"></i>AFFAIRE VALIDE</span>';
        } else {
             unlockModules(false);
             statusBadge.innerHTML = '<span class="badge bg-warning text-dark p-2">SÉLECTIONNER AFFAIRE</span>';
        }
    });

    // 2. CHARGEMENT MODULES (Real AJAX)
    window.loadModule = function(moduleName) {
        const container = document.getElementById('form_container');
        container.style.display = 'block';
        
        // Scroll smooth
        container.scrollIntoView({ behavior: 'smooth' });

        // Highlight selected card
        document.querySelectorAll('.module-card').forEach(c => c.style.borderColor = 'rgba(0,0,0,0.05)');
        // (Visual feedback skipped for simplicity)

        // Loading State
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Chargement du module ${moduleName}...</p>
            </div>
        `;

        // Fetch HTML
        fetch(`ajax/load_commande_module.php?module=${moduleName}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                
                // Init specific JS if needed
                if(moduleName === 'vitrage' && typeof window.initVitrageModule === 'function') window.initVitrageModule();
                if(moduleName === 'pliage' && typeof window.initPliageModule === 'function') window.initPliageModule();
                if(moduleName === 'profil' && typeof window.initProfilModule === 'function') window.initProfilModule();
                // Panneaux has inline script but let's be safe
            })
            .catch(err => {
                container.innerHTML = `<div class="alert alert-danger">Erreur de chargement : ${err}</div>`;
            });
    }

    // 3. SUBMIT MODULE (Generic)
    window.submitModule = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        // Basic HTML5 validation
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);

        // Add Context (Imputation)
        if (impStock.checked) {
            formData.append('imputation_type', 'STOCK');
        } else if (impAffaire.checked) {
            formData.append('imputation_type', 'AFFAIRE');
            // Assuming we have a hidden input or getting value from selector logic
            // For now, simulating Affaire ID logic or referencing a global/hidden field
            // Let's grab it from a hypothetical hidden field value if it existed
            // Or better, let's grab the dummy value since search is simulated
            formData.append('affaire_id', '9999'); // Placeholder for now as per previous simulation
        } else {
            alert("Veuillez choisir une imputation (Stock ou Affaire)");
            return;
        }

        // Special Handling for Pliage (Canvas)
        if (formId === 'form_pliage' && typeof window.getPliageCanvasData === 'function') {
            formData.append('canvas_image', window.getPliageCanvasData());
        }

        // Loading UI
        const btn = form.querySelector('button[type="button"], button[type="submit"]'); // Target the action button
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';

        fetch('ajax/save_commande_rapide.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Success Modal or Alert
                alert(data.message);
                
                // Open PDF in new tab
                if (data.pdf_url) {
                    window.open(data.pdf_url, '_blank');
                }

                // Reset ?
                // container.innerHTML = ...
            } else {
                alert("Erreur: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Erreur fatale lors de l'envoi.");
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

});
</script>

<?php require_once 'footer.php'; ?>
