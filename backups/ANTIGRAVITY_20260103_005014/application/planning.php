<?php
// planning.php - VERSION STABLE SANS MODAL
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

$page_title = 'Planning Général - Control Tower';
require_once 'header.php';

// Récupération des techniciens pour assignation
$techs = $pdo->query("SELECT id, nom_complet FROM utilisateurs WHERE role IN ('POSEUR', 'ATELIER', 'ADMIN') ORDER BY nom_complet")->fetchAll();
?>

<!-- FullCalendar v6 -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<!-- SweetAlert2 for popups -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .planning-wrapper {
        padding: 20px;
        height: calc(100vh - 120px);
    }
    
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
    
    .planning-toolbar .form-check-input:checked {
        background-color: #fff;
        border-color: #fff;
    }
    
    #calendar {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        height: calc(100vh - 220px);
        padding: 20px;
    }
    
    [data-bs-theme="dark"] #calendar {
        background: #1e293b;
        color: #e2e8f0;
    }
</style>

<div class="planning-wrapper">
    <!-- TOOLBAR -->
    <div class="planning-toolbar d-flex align-items-center gap-3 p-3 mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-tower-broadcast me-2"></i>Control Tower
        </h5>
        
        <div class="d-flex gap-3 ms-auto">
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" id="filterMetrage" checked onchange="filterEvents()">
                <label class="form-check-label" for="filterMetrage">📏 Métrages</label>
            </div>
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" id="filterPose" checked onchange="filterEvents()">
                <label class="form-check-label" for="filterPose">🔨 Poses</label>
            </div>
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" id="filterSav" checked onchange="filterEvents()">
                <label class="form-check-label" for="filterSav">🚑 SAV</label>
            </div>
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" id="filterLiv" checked onchange="filterEvents()">
                <label class="form-check-label" for="filterLiv">📦 Livraisons</label>
            </div>
        </div>
    </div>
    
    <!-- CALENDAR -->
    <div id="calendar"></div>
</div>

<script>
    // Technicians data from PHP
    const technicians = <?= json_encode($techs) ?>;
    
    let calendar;

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            
            navLinks: true,
            editable: true,
            dayMaxEvents: true,
            
            events: function(info, successCallback, failureCallback) {
                const url = `api/planning_events.php?start=${info.startStr}&end=${info.endStr}`;
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        const visibleTypes = [];
                        if(document.getElementById('filterMetrage').checked) visibleTypes.push('METRAGE');
                        if(document.getElementById('filterPose').checked) visibleTypes.push('POSE');
                        if(document.getElementById('filterSav').checked) visibleTypes.push('SAV');
                        if(document.getElementById('filterLiv').checked) visibleTypes.push('LIVRAISON');
                        
                        const filtered = data.filter(evt => visibleTypes.includes(evt.extendedProps.type));
                        successCallback(filtered);
                    })
                    .catch(err => failureCallback(err));
            },

            eventDrop: function(info) {
                updateEvent(info.event);
            },
            
            eventResize: function(info) {
                updateEvent(info.event);
            },
            
            eventClick: function(info) {
                showEditPopup(info.event);
            }
        });

        calendar.render();
    });

    function filterEvents() {
        calendar.refetchEvents();
    }

    function showEditPopup(event) {
        const eventType = event.extendedProps.type;
        const eventId = event.id;
        const currentTitle = event.title.replace(/^[📏🔨🚑📦]\s+\w+:\s+/, '');
        const currentDate = event.startStr.split('T')[0];
        const currentTime = event.startStr.split('T')[1]?.substring(0, 5) || '09:00';
        
        let techOptions = '<option value="">-- Non assigné --</option>';
        technicians.forEach(tech => {
            techOptions += `<option value="${tech.id}">${tech.nom_complet}</option>`;
        });
        
        Swal.fire({
            title: 'Modifier l\'événement',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Client / Affaire</label>
                        <input type="text" id="editTitle" class="form-control" value="${currentTitle}">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" id="editDate" class="form-control" value="${currentDate}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Heure</label>
                            <input type="time" id="editTime" class="form-control" value="${currentTime}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Technicien</label>
                        <select id="editTech" class="form-select">
                            ${techOptions}
                        </select>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Enregistrer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#0d6efd',
            width: '500px',
            didOpen: () => {
                document.getElementById('editTitle').focus();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                saveEventChanges(eventId, eventType);
            }
        });
    }
    
    function saveEventChanges(eventId, eventType) {
        const title = document.getElementById('editTitle').value;
        const date = document.getElementById('editDate').value;
        const time = document.getElementById('editTime').value;
        const techId = document.getElementById('editTech').value;
        
        if (!title || !date) {
            Swal.fire('Erreur', 'Veuillez remplir tous les champs', 'error');
            return;
        }
        
        const payload = {
            id: eventId,
            start: `${date}T${time}:00`,
            title: title,
            tech_id: techId || null
        };
        
        fetch('api/planning_events.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                Swal.fire('Erreur', data.error, 'error');
            } else {
                Swal.fire('Succès', 'Événement mis à jour', 'success');
                calendar.refetchEvents();
            }
        })
        .catch(err => {
            Swal.fire('Erreur', 'Erreur réseau', 'error');
        });
    }

    function updateEvent(event) {
        const payload = {
            id: event.id,
            start: event.startStr,
            end: event.endStr || event.startStr
        };

        fetch('api/planning_events.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data.error) {
                alert("Erreur: " + data.error);
                calendar.refetchEvents();
            } else {
                console.log("✅ Événement mis à jour");
            }
        })
        .catch(err => {
            console.error('Update failed:', err);
            alert('Erreur réseau');
            calendar.refetchEvents();
        });
    }
</script>

<?php require_once 'footer.php'; ?>
