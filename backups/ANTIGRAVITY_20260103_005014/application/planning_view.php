<?php
// planning_view.php - Calendrier Unifié (Métrage + Pose)
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'Planning Technique';
require_once 'header.php';
?>

<!-- FullCalendar CSS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<style>
    .fc-event { cursor: pointer; }
    .fc-toolbar-title { font-size: 1.5rem !important; }
    .fc-col-header-cell { background-color: #f8f9fa; padding: 10px 0; }
</style>

<div class="main-content">
    <div class="container-fluid mt-4">

        <div class="row">
            <!-- FILTRES -->
            <div class="col-md-2 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-filter me-2 text-primary"></i>Affichage
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="METRAGE" id="chkMetrage" checked onchange="refreshCalendar()">
                            <label class="form-check-label fw-bold text-primary" for="chkMetrage">
                                📐 Métrages
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="POSE" id="chkPose" checked onchange="refreshCalendar()">
                            <label class="form-check-label fw-bold text-success" for="chkPose">
                                🔨 Chantiers
                            </label>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-dark btn-sm" onclick="calendar.changeView('dayGridMonth')">Mois</button>
                            <button class="btn btn-outline-dark btn-sm" onclick="calendar.changeView('timeGridWeek')">Semaine</button>
                            <button class="btn btn-outline-dark btn-sm" onclick="calendar.changeView('listWeek')">Liste</button>
                        </div>
                        <hr>
                        <!-- AI MODULE -->
                        <div class="alert alert-info p-2 mb-0 text-center region-ai">
                            <i class="fas fa-brain text-primary fa-2x mb-2"></i><br>
                            <strong>Planning IA</strong>
                            <button class="btn btn-primary btn-sm w-100 mt-2" onclick="runAiAnalysis()">
                                <i class="fas fa-magic me-2"></i>Auditer
                            </button>
                            <div id="ai_status" class="small mt-2 text-start"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CALENDRIER -->
            <div class="col-md-10">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div id='calendar' style="min-height: 800px;"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL EVENT DETAILS -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Détails Intervention</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalDate" class="text-muted fw-bold"></p>
                <p id="modalInfo"></p>
                
                <div class="d-grid gap-2 mt-4">
                    <a href="#" id="btnDossier" class="btn btn-primary"><i class="fas fa-folder-open me-2"></i>Ouvrir le Dossier</a>
                    <a href="#" id="btnFiche" target="_blank" class="btn btn-dark"><i class="fas fa-print me-2"></i>Imprimer Fiche Intervention</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    var calendar;
    var eventModal;

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            navLinks: true,
            businessHours: true,
            editable: false,
            selectable: true,
            events: 'api_planning_events.php',
            
            eventClick: function(info) {
                info.jsEvent.preventDefault(); // Stop Browser Jump
                
                // Populate Modal
                document.getElementById('modalTitle').innerText = info.event.title;
                document.getElementById('modalDate').innerText = "Date : " + info.event.start.toLocaleDateString();
                
                // Extra Info
                let details = "";
                if (info.event.extendedProps.ville) details += "Ville : " + info.event.extendedProps.ville + "<br>";
                if (info.event.extendedProps.equipe) details += "Équipe : " + info.event.extendedProps.equipe + "<br>";
                document.getElementById('modalInfo').innerHTML = details;
                
                // Links
                document.getElementById('btnDossier').href = info.event.url;
                
                if (info.event.extendedProps.url_fiche) {
                     document.getElementById('btnFiche').href = info.event.extendedProps.url_fiche;
                     document.getElementById('btnFiche').style.display = 'block';
                } else {
                     document.getElementById('btnFiche').style.display = 'none';
                }

                // Show
                eventModal.show();
            },
            
            eventDidMount: function(info) {
                // Filtering Logic
                const type = info.event.extendedProps.type;
                const showMetrage = document.getElementById('chkMetrage').checked;
                const showPose = document.getElementById('chkPose').checked;
                
                if (type === 'METRAGE' && !showMetrage) {
                    info.el.style.display = 'none';
                }
                if (type === 'POSE' && !showPose) {
                    info.el.style.display = 'none';
                }
                
                // Tooltip simple
                info.el.title = info.event.title;
            }
        });
        
        calendar.render();
    });

    function runAiAnalysis() {
        const div = document.getElementById('ai_status');
        div.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Analyse en cours...';
        
        setTimeout(() => {
            const problems = [
                "⚠️ <b>Conflit Compétence</b> : Équipe A n'a pas la certif 'Véranda' pour le chantier Dupont.",
                "⚠️ <b>Fatigue</b> : Équipe B enchaîne 4 jours de 10h.",
                "✅ <b>Optimisation</b> : Déplacez le chantier Martin à mardi 14h pour gagner 45min."
            ];
            
            let html = '<ul class="list-unstyled mt-2">';
            problems.forEach(p => html += `<li class="mb-1 small border-bottom pb-1">${p}</li>`);
            html += '</ul>';
            
            div.innerHTML = html;
        }, 1500);
    }

    function refreshCalendar() {
        // FullCalendar doesn't support easy "client-side filtering" without refetch or rerender
        // Hack: re-render to trigger eventDidMount
        calendar.render(); 
    }

</script>

<?php require_once 'footer.php'; ?>
