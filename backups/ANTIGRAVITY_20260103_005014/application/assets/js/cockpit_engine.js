/**
 * COCKPIT ENGINE V2.0 (Refonte Complète)
 * Moteur de gestion du Kanban Métrage et de la Carte Tactique.
 * Aligné sur API V2.0 (Strict Mapping).
 */

const Cockpit = {
    map: null,
    markers: [],
    updateInterval: null,

    /**
     * Initialisation principale
     */
    init: function () {
        console.log("[Cockpit] Initializing V2.0...");

        // 1. Initialiser la carte (si Leaflet est prêt)
        this.initMap();

        // 2. Premier chargement des données
        this.refreshData();

        // 3. Polling automatique (DÉSACTIVÉ POUR DEBUG)
        // if (this.updateInterval) clearInterval(this.updateInterval);
        // this.updateInterval = setInterval(() => this.refreshData(), 60000);
    },

    /**
     * Initialisation de la carte Leaflet
     */
    initMap: function () {
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            console.warn("[Cockpit] Element #map introuvable.");
            return;
        }

        if (typeof L === 'undefined') {
            console.error("[Cockpit] Erreur critique : Leaflet L is undefined.");
            return;
        }

        // Centrage par défaut (Marseille/Sud est, plus pertinent pour ArtsAlu)
        this.map = L.map('map').setView([43.2965, 5.3698], 9);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(this.map);
    },

    /**
     * Rafraîchissement des données depuis l'API
     */
    refreshData: function () {
        // Indicateur visuel discret de chargement (optionnel)

        $.ajax({
            url: 'api_metrage_cockpit.php?action=get_tasks',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success && Array.isArray(response.tasks)) {
                    this.renderKanban(response.tasks);
                    this.updateMap(response.tasks);
                } else {
                    console.error("[Cockpit] Format de réponse invalide", response);
                }
            },
            error: (xhr, status, error) => {
                console.error("[Cockpit] Erreur API:", error);
                // Feedback utilisateur en cas d'erreur réseau
                $('#col-plan-body').html('<div class="text-center p-3 text-danger"><i class="fas fa-wifi me-2"></i>Erreur connexion</div>');
            }
        });
    },

    /**
     * Rendu des colonnes Kanban
     * @param {Array} tasks Liste des interventions
     */
    renderKanban: function (tasks) {
        // Vider les colonnes
        $('#col-plan-body, #col-progress-body, #col-validate-body, #col-done-body').empty();

        const counts = { plan: 0, progress: 0, validate: 0, done: 0 };

        if (tasks.length === 0) {
            $('#col-plan-body').html('<div class="text-center py-5 text-muted opacity-50">Aucune mission</div>');
            this.updateBadges(counts);
            return;
        }

        tasks.forEach(task => {
            // MAPPING STRICT DES STATUTS (ENUM BDD -> COLONNES)
            let targetCol = null;

            // MAPPING LOGIQUE V2.1 (Utilisateur)
            // 1. Col 1 (A Planifier) = Pas de date
            // 2. Col 2 (Planifié) = A une date (et pas démarré/fini)
            // 3. Col 3 (A Valider) = En cours (Commencé)
            // 4. Col 4 (Validé/Terminé) = Fini

            targetCol = '#col-plan-body';

            // Priorité aux statuts finaux
            if (task.statut === 'VALIDE' || task.statut === 'TERMINE') {
                targetCol = '#col-done-body'; // Col 4
                counts.done++;
            }
            // Puis statut commencer (En Cours)
            else if (task.statut === 'EN_COURS' || task.statut === 'A_REVOIR') {
                targetCol = '#col-validate-body'; // Col 3
                counts.validate++;
            }
            // Puis logique de date pour le reste
            else if (task.date_prevue && task.date_prevue !== '0000-00-00 00:00:00') {
                targetCol = '#col-progress-body'; // Col 2
                counts.progress++;
            }
            // Par défaut (Pas de date ou A_PLANIFIER sans date)
            else {
                targetCol = '#col-plan-body'; // Col 1
                counts.plan++;
            }

            if (targetCol) {
                $(targetCol).append(this.createCardHtml(task));
            }
        });

        this.updateBadges(counts);
    },

    /**
     * Génération HTML d'une carte
     * @param {Object} task Donnée brute API
     */
    createCardHtml: function (task) {
        // Mapping des champs API v2
        const clientName = task.client_nom || 'Client Inconnu';
        const ville = task.client_ville || ''; // Peut être null
        const techName = task.technicien_nom || 'Non assigné';
        const dateFmt = task.date_fmt || '--/--';
        const isUrgent = task.statut === 'EN_COURS';

        // Initials du technicien
        const techInitials = techName !== 'Non assigné' ? techName.charAt(0) : '?';
        const techHtml = techName !== 'Non assigné'
            ? `<div class="k-avatar bg-primary text-white" title="${techName}">${techInitials}</div>`
            : `<div class="k-avatar bg-light text-muted" title="Non assigné"><i class="fas fa-user-slash" style="font-size:0.7em"></i></div>`;

        return `
            <div class="k-card ${isUrgent ? 'border-primary' : ''} bg-white shadow-sm mb-2 p-2 rounded-3 positions-relative" id="task-${task.id}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-light text-dark border">${task.numero_prodevis || 'MISSION'}</span>
                    <small class="text-muted" style="font-size:0.7rem">${dateFmt}</small>
                </div>
                
                <h6 class="fw-bold mb-1 text-truncate" style="max-width: 100%;" title="${clientName}">
                    ${clientName}
                </h6>
                <div class="text-truncate text-muted small mb-2">${task.nom_affaire || ''}</div>
                
                <div class="d-flex justify-content-between align-items-center mt-2 border-top pt-2">
                    <div class="text-primary small">
                        <i class="fas fa-map-marker-alt me-1"></i>${ville}
                    </div>
                    ${techHtml}
                </div>
                
                <div class="mt-2 text-end">
                    <button onclick="Cockpit.openTask(${task.id})" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                        VOIR
                    </button>
                </div>
            </div>
        `;
    },

    updateBadges: function (counts) {
        $('.count-plan').text(counts.plan);
        $('.count-progress').text(counts.progress);
        $('.count-validate').text(counts.validate);
        $('.count-done').text(counts.done);
    },

    updateMap: function (tasks) {
        if (!this.map) return;

        // Nettoyage markers existants
        this.markers.forEach(m => this.map.removeLayer(m));
        this.markers = [];

        tasks.forEach(task => {
            // Mapping API: gps_lat / gps_lon
            const lat = parseFloat(task.gps_lat);
            const lon = parseFloat(task.gps_lon);

            if (!isNaN(lat) && !isNaN(lon) && lat !== 0) {
                let color = '#0f4c75'; // Default Blue
                if (task.statut === 'EN_COURS') color = '#ffc107'; // Orange
                if (task.statut === 'TERMINE') color = '#198754'; // Green

                const marker = L.circleMarker([lat, lon], {
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.7,
                    radius: 8,
                    weight: 1
                }).addTo(this.map);

                marker.bindPopup(`
                    <strong>${task.client_nom}</strong><br>
                    ${task.nom_affaire}<br>
                    <small>${task.statut}</small>
                `);

                this.markers.push(marker);
            }
        });
    },

    openTask: function (id) {
        // Navigation sécurisée vers le détail
        window.location.href = 'metrage_validator.php?id=' + id;
    }
};

// Auto-init sécurisé
$(document).ready(function () {
    Cockpit.init();
});
