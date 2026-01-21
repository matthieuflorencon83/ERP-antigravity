<?php
// sav_creation.php - Interface Prise d'Appel Rapide
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'SAV - Prise d\'appel';
require_once 'header.php';
?>

<div class="container-fluid px-4 py-4">
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']); 
        ?>
    <?php endif; ?>

    <!-- HEADER PRO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-headset me-2 text-primary"></i>Prise d'Appel SAV</h2>
            <p class="text-muted mb-0">Accueil client & Qualification rapide</p>
        </div>
        <div>
            <a href="sav_fil.php" class="btn btn-outline-secondary position-relative">
                <i class="fas fa-stream me-2"></i>Fil d'actualité SAV
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    3
                </span>
            </a>
        </div>
    </div>

    <form id="savForm" action="sav_actions.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create_ticket">
        <input type="hidden" name="client_id" id="inputClientId">
        <input type="hidden" name="affaire_id" id="inputAffaireId">

        <div class="row g-4">
            
            <!-- COLONNE GAUCHE : RECHERCHE & CONTEXTE -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100 bg-white">
                    <div class="card-body p-4">
                        
                        <label class="form-label fw-bold text-uppercase text-muted small mb-3">1. Qui appelle ?</label>
                        
                        <!-- RECHERCHE UNIFIEE -->
                        <div class="position-relative mb-4">
                            <div class="input-group input-group-lg shadow-sm border rounded-pill overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-4"><i class="fas fa-search text-primary"></i></span>
                                <input type="text" id="savSearch" class="form-control border-0" placeholder="Nom, Ville, Tél, Ticket ou Affaire..." autocomplete="off">
                                <button class="btn btn-white border-0 px-3" type="button" onclick="resetSearch()"><i class="fas fa-times text-muted"></i></button>
                            </div>
                            <!-- Resultats Ajax -->
                            <div id="searchResults" class="list-group position-absolute w-100 mt-2 shadow-lg border-0 rounded-3" style="z-index: 1000; display:none;"></div>
                        </div>

                        <!-- CARD CLIENT SELECTIONNE -->
                        <div id="clientCard" class="card border-primary border-opacity-25 bg-primary bg-opacity-10 d-none animate__animated animate__fadeIn">
                            <div class="card-body position-relative">
                                <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="resetClient()"></button>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-user fa-lg"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0 text-primary" id="cardName">Jean Dupont</h5>
                                        <div class="text-muted small" id="cardAddress"><i class="fas fa-map-marker-alt me-1"></i>Paris</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <a href="#" class="btn btn-sm btn-light text-primary flex-fill"><i class="fas fa-history me-1"></i>Historique</a>
                                    <a href="#" class="btn btn-sm btn-light text-primary flex-fill"><i class="fas fa-folder-open me-1"></i>Fiche</a>
                                </div>
                                <div class="alert alert-warning py-2 small mb-0 d-none" id="alertEncours">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Ticket #SAV-2024-001 déjà ouvert !
                                </div>
                            </div>
                        </div>

                        <!-- MODE PROSPECT (Si non trouvé) -->
                        <div id="prospectForm" class="card border-warning border-opacity-50 bg-warning bg-opacity-10 d-none animate__animated animate__fadeIn">
                            <div class="card-body">
                                <h6 class="text-warning fw-bold mb-3"><i class="fas fa-user-plus me-2"></i>Nouveau Prospect / Inconnu</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="text" name="prospect_nom" id="newNom" class="form-control form-control-sm" placeholder="Nom complet">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="prospect_ville" id="newVille" class="form-control form-control-sm" placeholder="Ville">
                                    </div>
                                    <div class="col-12">
                                        <input type="text" name="prospect_telephone" id="newTel" class="form-control form-control-sm" placeholder="Téléphone">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : QUALIFICATION & PREUVES -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-uppercase text-muted">2. Le Problème</label>
                                <select name="type_panne" class="form-select form-select-lg fw-bold border-primary" required>
                                    <option value="" selected disabled>Quel est le souci ?</option>
                                    <option value="VOLET_ROULANT">Volet Roulant (Bloqué/Casse)</option>
                                    <option value="PORTAIL">Portail / Automatisme</option>
                                    <option value="GARAGE">Porte de Garage</option>
                                    <option value="STORE">Store Banne</option>
                                    <option value="VITRAGE">Vitrage / Menuiserie</option>
                                    <option value="AUTRE">Autre demande</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Urgence</label>
                                <select name="urgence" class="form-select form-select-lg border-danger text-danger fw-bold">
                                    <option value="1">Normale</option>
                                    <option value="2">Moyenne (48h)</option>
                                    <option value="3">URGENTE (Sécurité)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <textarea name="description" class="form-control bg-light" rows="4" placeholder="Décrivez le problème tel que le client le raconte..." required></textarea>
                        </div>

                        <!-- DROPZONE PREUVES -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">3. La Boîte à Preuves (Photos/Mails)</label>
                            <div id="dropZone" class="border-2 border-dashed border-secondary rounded-3 p-4 text-center bg-light position-relative" style="transition: all 0.2s;">
                                <input type="file" name="pj[]" id="fileInput" multiple class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor:pointer;" onchange="handleFiles(this.files)">
                                <div class="dz-message">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                    <p class="mb-0 fw-medium">Glissez les photos ou mails ici</p>
                                    <small class="text-muted">ou cliquez pour parcourir</small>
                                </div>
                                <div id="previewArea" class="d-flex gap-2 mt-3 flex-wrap justify-content-center"></div>
                            </div>
                        </div>

                        <!-- BOUTONS ACTIONS -->
                        <hr class="my-4">
                        <div class="d-flex gap-3">
                            <button type="submit" name="decision" value="DIAGNOSTIC" class="btn btn-primary btn-lg flex-fill shadow-sm">
                                <i class="fas fa-stethoscope me-2"></i>Lancer Diagnostic
                                <div class="small fw-normal opacity-75">Envoi d'un technicien pour voir</div>
                            </button>
                            <button type="submit" name="decision" value="REPARATION" class="btn btn-outline-success btn-lg flex-fill">
                                <i class="fas fa-tools me-2"></i>Réparation Directe
                                <div class="small fw-normal opacity-75">Si panne identifiée (ex: Sangle)</div>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
// MOTEUR DE RECHERCHE UX
const searchInput = document.getElementById('savSearch');
const resultsBox = document.getElementById('searchResults');
const clientCard = document.getElementById('clientCard');
const prospectForm = document.getElementById('prospectForm');

let debounceTimer;

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const q = this.value;
    
    if (q.length < 2) {
        resultsBox.style.display = 'none';
        return;
    }

    debounceTimer = setTimeout(() => {
        fetch(`sav_search_ajax.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                resultsBox.innerHTML = '';
                if (data.length === 0) showNoResults();
                else {
                    data.forEach(item => {
                        const a = document.createElement('a');
                        a.className = 'list-group-item list-group-item-action py-3';
                        a.innerHTML = `<div class='fw-bold'>${item.label}</div>`;
                        a.href = '#';
                        a.onclick = (e) => { e.preventDefault(); selectItem(item); };
                        resultsBox.appendChild(a);
                    });
                    resultsBox.style.display = 'block';
                }
            });
    }, 300);
});

function showNoResults() {
    resultsBox.innerHTML = `
        <a href="#" class="list-group-item list-group-item-action py-3 text-warning fw-bold" onclick="enableProspectMode()">
            <i class="fas fa-plus-circle me-2"></i>Client inconnu ? Créer un Prospect SAV
        </a>
    `;
    resultsBox.style.display = 'block';
}

function selectItem(item) {
    resultsBox.style.display = 'none';
    searchInput.value = ''; // On vide pour le look
    
    if (item.type === 'client') {
        document.getElementById('inputClientId').value = item.id;
        document.getElementById('cardName').innerText = item.data.nom;
        document.getElementById('cardAddress').innerText = item.data.ville;
        
        prospectForm.classList.add('d-none');
        clientCard.classList.remove('d-none');
    }
}

function enableProspectMode() {
    resultsBox.style.display = 'none';
    clientCard.classList.add('d-none');
    prospectForm.classList.remove('d-none');
    // On focuse sur le nom
    setTimeout(() => document.getElementById('newNom').focus(), 100);
}

function resetSearch() {
    searchInput.value = '';
    resultsBox.style.display = 'none';
}

function resetClient() {
    document.getElementById('inputClientId').value = '';
    clientCard.classList.add('d-none');
    prospectForm.classList.add('d-none');
}

// DROPZONE UX
const dropZone = document.getElementById('dropZone');
const previewArea = document.getElementById('previewArea');

dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('bg-primary', 'bg-opacity-10'); });
dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.classList.remove('bg-primary', 'bg-opacity-10'); });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('bg-primary', 'bg-opacity-10');
    handleFiles(e.dataTransfer.files);
    document.getElementById('fileInput').files = e.dataTransfer.files; // Sync input
});

function handleFiles(files) {
    previewArea.innerHTML = '';
    Array.from(files).forEach(file => {
        const d = document.createElement('div');
        d.className = 'badge bg-secondary p-2';
        d.innerHTML = `<i class="fas fa-paperclip me-1"></i>${file.name}`;
        previewArea.appendChild(d);
    });
}
</script>

<?php require_once 'footer.php'; ?>
