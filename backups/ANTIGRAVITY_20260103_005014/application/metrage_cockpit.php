<?php
// metrage_cockpit.php - Cockpit Métrage Live (Version Corrigée V6)
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = "Cockpit Métrage";
require_once 'header.php'; 
// NOTE: header.php opens <div class="container-fluid px-4 ag-main-content">
// We must NOT open another container immediately unless nested properly.
?>

<!-- LEAFLET CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<!-- CUSTOM COCKPIT CSS (RESTORED) -->
<!-- Adding ID to easier enable/disable if needed -->
<link rel="stylesheet" href="assets/css/cockpit.css?v=<?= time() ?>" id="css-cockpit">

<div class="row g-0"> <!-- Row inside the header's container -->
    <!-- SUB HEADER -->
    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
            <div>
                <h4 class="fw-bold text-primary mb-0"><i class="fas fa-satellite-dish me-2"></i>Cockpit Métrage</h4>
                <small class="text-muted">Vue temps réel des opérations terrain.</small>
            </div>
                <a href="metrage_studio.php" class="btn btn-primary text-white fw-bold shadow-sm">
                    <i class="fas fa-desktop me-2"></i>STUDIO MÉTRAGE
                </a>
                <button type="button" class="btn btn-success shadow-sm ms-2" id="btn-new-metrage" onclick="window.openMetrageModal()">
                    <i class="fas fa-plus-circle me-2"></i>Planifier un métrage
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN GRID (Kanban + Map) -->
    <div class="col-12" id="cockpit-grid-area">
        <div class="cockpit-container" style="display: flex; gap: 20px; height: calc(100vh - 220px);">
            
            <!-- KANBAN BOARD (Flex 70%) -->
            <div class="kanban-board" id="kanban-area" style="flex: 3; display: flex; gap: 15px; overflow-x: auto; padding-bottom: 5px;">
                 <!-- COL 1 -->
                 <div class="kanban-column col-plan" style="flex: 1; min-width: 260px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(10px); border-radius: 12px; display: flex; flex-direction: column;">
                    <div class="col-header p-3 border-bottom border-light-subtle fw-bold text-center" style="background: rgba(255,255,255,0.02)">
                        <span class="text-white-50 text-uppercase small ls-1"><i class="far fa-calendar me-2"></i>A planifier</span>
                        <span class="badge bg-white text-dark rounded-pill ms-2 count-plan opacity-75">0</span>
                    </div>
                    <div class="col-body p-2 flex-grow-1 overflow-auto custom-scrollbar" id="col-plan-body"></div>
                </div>
                 <!-- COL 2 -->
                 <div class="kanban-column col-progress" style="flex: 1; min-width: 260px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(10px); border-radius: 12px; display: flex; flex-direction: column;">
                    <div class="col-header p-3 border-bottom border-light-subtle fw-bold text-center" style="background: rgba(255,255,255,0.02)">
                        <span class="text-warning text-uppercase small ls-1"><i class="fas fa-running me-2"></i>En cours</span>
                        <span class="badge bg-warning text-dark rounded-pill ms-2 count-progress opacity-75">0</span>
                    </div>
                    <div class="col-body p-2 flex-grow-1 overflow-auto custom-scrollbar" id="col-progress-body"></div>
                </div>
                 <!-- COL 3 -->
                 <div class="kanban-column col-validate" style="flex: 1; min-width: 260px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(10px); border-radius: 12px; display: flex; flex-direction: column;">
                    <div class="col-header p-3 border-bottom border-light-subtle fw-bold text-center" style="background: rgba(255,255,255,0.02)">
                        <span class="text-info text-uppercase small ls-1"><i class="fas fa-check-double me-2"></i>A Valider</span>
                        <span class="badge bg-info text-dark rounded-pill ms-2 count-validate opacity-75">0</span>
                    </div>
                    <div class="col-body p-2 flex-grow-1 overflow-auto custom-scrollbar" id="col-validate-body"></div>
                </div>
                 <!-- COL 4 -->
                 <div class="kanban-column col-done" style="flex: 1; min-width: 260px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(10px); border-radius: 12px; display: flex; flex-direction: column;">
                    <div class="col-header p-3 border-bottom border-light-subtle fw-bold text-center" style="background: rgba(255,255,255,0.02)">
                        <span class="text-success text-uppercase small ls-1"><i class="fas fa-archive me-2"></i>Terminé</span>
                        <span class="badge bg-success text-white rounded-pill ms-2 count-done opacity-75">0</span>
                    </div>
                    <div class="col-body p-2 flex-grow-1 overflow-auto custom-scrollbar" id="col-done-body"></div>
                </div>
            </div>

            <!-- MAP SIDEBAR (Flex 30%) -->
            <div class="map-sidebar" style="flex: 1; background: white; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; border: 1px solid #ddd;">
                <div id="map" style="flex: 1; width: 100%; min-height: 200px;"></div>
                <div class="p-3 bg-light border-top">
                    <h6 class="fw-bold small text-muted">Alertes</h6>
                    <small>Aucune alerte active.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECURE MODAL (MOVED TO BODY via JS) -->
<div class="modal fade modal-secure" id="modal-new-inter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-backdrop-secure">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Nouveau Métrage</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-new-inter">
                <div class="modal-body bg-white">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Affaire</label>
                        <select name="affaire_id" id="select-affaire" class="form-select" required>
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Technicien</label>
                        <select name="technicien_id" id="select-technicien" class="form-select">
                            <option value="">Non assigné</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Prévue</label>
                        <input type="datetime-local" name="date_prevue" class="form-control">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Valider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="assets/js/cockpit_engine.js?v=<?= time() ?>"></script>

<script>
window.openMetrageModal = function() {
    console.log("[SECURE] Open Request");
    const el = document.getElementById('modal-new-inter');
    if(el) {
        new bootstrap.Modal(el).show();
    } else {
        alert("Erreur: Modal introuvable (DOM Missing)");
    }
};

$(document).ready(function() {
    console.log("[SECURE] Init");

    // 1. Move to Body
    if ($('#modal-new-inter').length > 0) {
        $('#modal-new-inter').appendTo("body");
        console.log("[SECURE] Modal moved to body");
    }

    // 2. Data Loading
    $('#modal-new-inter').on('show.bs.modal', function () {
        console.log("[SECURE] Loading Data...");
        loadDataSecure();
    });

    // 3. Submit
    $('#form-new-inter').on('submit', function(e) {
        e.preventDefault();
        if(!$('#select-affaire').val()) {
            alert("Veuillez choisir une affaire.");
            return;
        }
        const formData = new FormData(this);
        formData.append('action', 'create_intervention');
        fetch('api_metrage_cockpit.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                const el = document.getElementById('modal-new-inter');
                const modal = bootstrap.Modal.getInstance(el);
                if(modal) modal.hide();
                if(typeof Cockpit !== 'undefined' && Cockpit.refreshData) Cockpit.refreshData();
                else window.location.reload();
            } else {
                alert("Erreur: " + data.error);
            }
        })
        .catch(err => console.error(err));
    });

    function loadDataSecure() {
        $.getJSON('api_metrage_cockpit.php?action=get_affaires_sans_metrage', function(data) {
             const $sel = $('#select-affaire');
             $sel.empty().append('<option value="">-- Choisir Affaire --</option>');
             if(data.success && data.affaires) {
                 data.affaires.forEach(function(a) {
                     $sel.append('<option value="'+a.id+'">'+a.nom_affaire+' ('+a.client+')</option>');
                 });
             }
        });
        $.getJSON('api_metrage_cockpit.php?action=get_techniciens', function(data) {
             const $sel = $('#select-technicien');
             $sel.empty().append('<option value="">Non assigné</option>');
             if(data.success && data.techniciens) {
                 data.techniciens.forEach(function(t) {
                     $sel.append('<option value="'+t.id+'">'+t.nom+'</option>');
                 });
             }
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>
