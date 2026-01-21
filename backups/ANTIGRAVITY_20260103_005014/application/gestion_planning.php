<?php
// gestion_planning.php - CONTROL TOWER (VERSION COMPLÈTE)
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'Planning Général - Control Tower';
require_once 'header.php';
?>

<!-- FullCalendar v6 -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<style>
    /* LAYOUT */
    .planning-wrapper {
        padding: 20px;
        height: calc(100vh - 120px);
    }
    
    /* TOOLBAR */
    .planning-toolbar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .planning-toolbar .form-check-label {
        color: white;
        font-weight: 500;
        cursor: pointer;
    }
    
    .planning-toolbar .form-check-input {
        cursor: pointer;
    }
    
    .planning-toolbar .form-check-input:checked {
        background-color: #fff;
        border-color: #fff;
    }
    
    /* CALENDAR */
    #calendar {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        height: calc(100vh - 280px);
        padding: 20px;
    }
    
    /* FAB BUTTON */
    .fab-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        z-index: 1000;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .fab-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    }
    
    /* DARK MODE */
    [data-bs-theme="dark"] #calendar {
        background: #1e293b;
        color: #e2e8f0;
    }
    
    [data-bs-theme="dark"] .planning-toolbar {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    }
    /* FILTER DOTS */
    .filter-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
        border: 2px solid rgba(255,255,255,0.8);
        box-shadow: 0 0 4px rgba(0,0,0,0.2);
    }
    .dot-metrage { background-color: #0d6efd; }
    .dot-pose { background-color: #198754; }
    .dot-sav { background-color: #dc3545; }
    .dot-liv { background-color: #ffc107; }
    /* MODAL Z-INDEX FIX */
    .modal { z-index: 10000 !important; }
    .modal-backdrop { z-index: 9999 !important; }
</style>

<div class="planning-wrapper">
    <!-- TOOLBAR -->
    <div class="planning-toolbar d-flex align-items-center gap-3 p-3 mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-tower-broadcast me-2"></i>Control Tower
        </h5>
        
        <div class="d-flex gap-3 ms-auto">
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" value="METRAGE" id="filterMetrage" checked onchange="filterEvents()">
                <label class="form-check-label d-flex align-items-center" for="filterMetrage"><span class="filter-dot dot-metrage"></span>Métrages</label>
            </div>
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" value="POSE" id="filterPose" checked onchange="filterEvents()">
                <label class="form-check-label d-flex align-items-center" for="filterPose"><span class="filter-dot dot-pose"></span>Poses</label>
            </div>
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" value="SAV" id="filterSav" checked onchange="filterEvents()">
                <label class="form-check-label d-flex align-items-center" for="filterSav"><span class="filter-dot dot-sav"></span>SAV</label>
            </div>
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" value="LIVRAISON" id="filterLiv" checked onchange="filterEvents()">
                <label class="form-check-label d-flex align-items-center" for="filterLiv"><span class="filter-dot dot-liv"></span>Livraisons</label>
            </div>
        </div>
    </div>
    
    <!-- CALENDAR -->
    <div id="calendar"></div>
    
    <!-- FAB BUTTON -->
    <button class="btn btn-primary fab-btn" onclick="openCreateModal()" title="Nouveau RDV">
        <i class="fas fa-plus fa-lg"></i>
    </button>
</div>

    <!-- MODAL CREATION (Release Static) -->
    <div class="modal fade" id="modalCreate" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau RDV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCreate">
                        <div class="mb-3">
                            <label class="form-label">Type d'intervention</label>
                            <select id="createType" class="form-select">
                                <option value="METRAGE">📏 Métrage</option>
                                <option value="SAV">🚑 SAV</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Client / Affaire</label>
                            <input type="text" id="createTitle" class="form-control" placeholder="Nom du client" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" id="createDate" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Heure</label>
                                <input type="time" id="createTime" class="form-control" value="09:00">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="submitCreate()">Créer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETAIL -->
    <div class="modal fade" id="modalDetail" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header border-0 bg-lighter">
                    <h5 class="modal-title fw-bold" id="detailTitle">Détail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <span class="badge" id="detailBadge" style="font-size: 0.9em;">TYPE</span>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-6">
                            <h6 class="text-uppercase text-muted small fw-bold mb-1">Date & Heure</h6>
                            <div class="d-flex align-items-center text-dark fw-bold">
                                <i class="far fa-clock me-2 text-primary"></i>
                                <span id="detailDate"></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-uppercase text-muted small fw-bold mb-1">Technicien / Ressource</h6>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-hard-hat me-2 text-secondary"></i>
                                <span id="detailTech">Non assigné</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="text-uppercase text-muted small fw-bold mb-1">Client</h6>
                            <div class="bg-light p-3 rounded-3 border">
                                <div class="fw-bold mb-1" id="detailClient" style="font-size: 1.1em;"></div>
                                <div class="text-muted small mb-1" id="detailAddress"><i class="fas fa-map-marker-alt me-1"></i></div>
                                <div class="text-primary small fw-bold" id="detailPhone"><i class="fas fa-phone me-1"></i></div>
                                <div class="mt-2 pt-2 border-top small text-secondary">
                                    <i class="fas fa-info-circle me-1"></i> Note: <span id="detailDesc" class="fst-italic"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom">
                    <a href="#" id="detailLink" class="btn btn-primary w-100 shadow-sm fw-bold">
                        Accéder au dossier <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

<script>
    let calendar;
    let modalDetailInstance = null;
    let modalCreateInstance = null;

    document.addEventListener('DOMContentLoaded', function() {
        try {
            // GLOBAL SAFETY: Escape Key Nuke
            document.addEventListener('keydown', function(event) {
                if (event.key === "Escape") {
                    console.warn("Safety Cleanup Triggered by ESC");
                    cleanupBackdrops();
                    if(modalDetailInstance) modalDetailInstance.hide();
                    if(modalCreateInstance) modalCreateInstance.hide();
                }
            });

            // MOVE MODALS TO BODY (Container Fix)
            const detailEl = document.getElementById('modalDetail');
            const createEl = document.getElementById('modalCreate');
            if(detailEl) document.body.appendChild(detailEl);
            if(createEl) document.body.appendChild(createEl);

            var calendarEl = document.getElementById('calendar');

            // ROBUST INIT: Use getOrCreateInstance
            if(detailEl) {
                modalDetailInstance = bootstrap.Modal.getOrCreateInstance(detailEl);
                detailEl.addEventListener('hidden.bs.modal', cleanupBackdrops);
            }

            if(createEl) {
                modalCreateInstance = bootstrap.Modal.getOrCreateInstance(createEl);
                createEl.addEventListener('hidden.bs.modal', cleanupBackdrops);
            }

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'fr',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                // themeSystem: 'bootstrap5', // Removed to fix colors
                navLinks: true,
                editable: true,
                dayMaxEvents: true,
                
                // FILTRES
                events: function(info, successCallback, failureCallback) {
                    const url = `api/planning_events.php?start=${info.startStr}&end=${info.endStr}`;
                    
                    fetch(url)
                        .then(response => {
                            if(!response.ok) throw new Error('API Error');
                            return response.json();
                        })
                        .then(data => {
                            const visibleTypes = [];
                            if(document.getElementById('filterMetrage').checked) visibleTypes.push('METRAGE');
                            if(document.getElementById('filterPose').checked) visibleTypes.push('POSE');
                            if(document.getElementById('filterSav').checked) visibleTypes.push('SAV');
                            if(document.getElementById('filterLiv').checked) visibleTypes.push('LIVRAISON');
                            
                            // Si le serveur renvoie une erreur ou vide
                            if(!Array.isArray(data)) return successCallback([]);

                            const filtered = data.filter(evt => {
                                // Sécurité s'il manque extendedProps
                                if (!evt.extendedProps) return true;
                                return visibleTypes.includes(evt.extendedProps.type);
                            });
                            
                            successCallback(filtered);
                        })
                        .catch(err => {
                            console.error('Fetch error:', err);
                            failureCallback(err);
                        });
                },

                eventDrop: function(info) { updateEvent(info.event); },
                eventResize: function(info) { updateEvent(info.event); },
                
                // CLICK POPUP
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if(!modalDetailInstance) return;

                    const props = info.event.extendedProps;
                    
                    // Remplissage Modal
                    document.getElementById('detailTitle').textContent = info.event.title;
                    // document.getElementById('detailType').textContent = props.type; // Element removed in previous edit? Check HTML if needed, but safe to omit if not critical
                    
                    // Couleur Badge
                    const badge = document.getElementById('detailBadge');
                    badge.className = 'badge mb-2'; // Reset
                    badge.textContent = props.type || 'AUTRE';

                    if (props.type === 'METRAGE') badge.classList.add('bg-primary');
                    else if (props.type === 'POSE') badge.classList.add('bg-success');
                    else if (props.type === 'SAV') badge.classList.add('bg-danger');
                    else badge.classList.add('bg-warning', 'text-dark');

                    // Dates
                    let dateStr = info.event.start.toLocaleDateString('fr-FR', {weekday: 'long', day: 'numeric', month: 'long'});
                    if (!info.event.allDay) {
                        dateStr += ' à ' + info.event.start.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
                    }
                    document.getElementById('detailDate').textContent = dateStr;
                    
                    // Tech
                    document.getElementById('detailTech').textContent = props.technicien_nom || 'Non assigné';
                    
                    // Client Info (Parsing title si besoin ou props)
                    // L'API renvoie client_ville, adresse etc
                    // On extrait le nom du titre si pas dispo (Titre = "ICON Client - Affaire")
                    const fullTitle = info.event.title.substring(2) || 'Client'; // Remove icon
                    document.getElementById('detailClient').textContent = fullTitle;
                    
                    document.getElementById('detailAddress').textContent = props.client_adresse || props.client_ville || '';
                    document.getElementById('detailPhone').textContent = props.client_tel || '';
                    document.getElementById('detailDesc').textContent = props.description || 'Aucune note';
                    
                    // Link
                    const linkBtn = document.getElementById('detailLink');
                    if (props.link) {
                        linkBtn.href = props.link;
                        linkBtn.style.display = 'block';
                    } else {
                        linkBtn.style.display = 'none';
                    }

                    // Async show to prevent freezing
                    setTimeout(() => modalDetailInstance.show(), 10);
                }
            });

            calendar.render();

        } catch(e) {
            console.error("Critical Calendar Error:", e);
            alert("Erreur de chargement du planning. Vérifiez la console.");
        }
    });

    // Helper to kill stuck backdrops
    function cleanupBackdrops() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if(backdrops.length > 0) {
            backdrops.forEach(bd => bd.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    }

    function filterEvents() {
        if(calendar) calendar.refetchEvents();
    }

    function updateEvent(event) {
        const payload = {
            id: event.id,
            start: event.startStr,
            end: event.endStr || event.startStr
        };
        
        // POST to API
        fetch('api/planning_events.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if(data.error) alert('Erreur: ' + data.error);
        })
        .catch(err => {
            console.error('Update failed:', err);
            alert('Erreur réseau');
            calendar.refetchEvents();
        });
    }

    function openCreateModal() {
        if(!modalCreateInstance) {
             const el = document.getElementById('modalCreate');
             if(el) modalCreateInstance = bootstrap.Modal.getOrCreateInstance(el);
             else return alert("Erreur: Fenêtre de création introuvable");
        }
        
        // Set default date to today
        document.getElementById('createDate').value = new Date().toISOString().split('T')[0];
        setTimeout(() => modalCreateInstance.show(), 10);
    }

    function submitCreate() {
        const title = document.getElementById('createTitle').value;
        const date = document.getElementById('createDate').value;
        
        if (!title || !date) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        alert('Création d\'événements sera disponible prochainement.\nPour l\'instant, créez les métrages via le module Affaires.');
        
        if(modalCreateInstance) modalCreateInstance.hide();
        document.getElementById('formCreate').reset();
    }
</script>

<?php require_once 'footer.php'; ?>
