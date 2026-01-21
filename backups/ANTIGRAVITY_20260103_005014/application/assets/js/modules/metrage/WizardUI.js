import { MetrageCanvas } from './MetrageCanvas.js';

/**
 * WizardUI.js - Interface Bête de Course V4.5 (Restored)
 * 
 * Responsabilité : Rendu Split Screen (Canvas + Wizard)
 * UX : Assistant Persona, Boutons Géants, Feedback Immédiat
 */

export class WizardUI {

    constructor(state, api) {
        this.state = state;
        this.api = api;
        // Fix: Target MAIN area normally, preserving Sidebar
        this.container = document.querySelector('.studio-main');
        this.canvasEngine = null;
    }

    init() {
        console.log('🎨 WizardUI Initializing...');
        this._setupLayout();

        // Init Canvas Engine with slight delay to ensure CSS layout is applied
        setTimeout(() => {
            this.canvasEngine = new MetrageCanvas('metrageCanvas');
            this.canvasEngine.resize();
        }, 100);

        this._bindEvents();
        this.render();
    }

    _setupLayout() {
        // Cleaning existing Main content (Topbar, Assistant stream)
        // We might want to KEEP Main styles but replace content.

        this.container.innerHTML = `
            <div class="wizard-split-screen">
                <!-- ZONE VISUELLE (CENTRE) -->
                <div class="wizard-visual">
                    <canvas id="metrageCanvas"></canvas>
                    <div class="visual-overlays"></div>
                </div>

                <!-- ZONE INTERACTION (DROITE) -->
                <div class="wizard-panel">
                    <div class="wizard-header">
                        <h2 id="wizardTitle">Configuration</h2>
                        <div class="wizard-progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div id="wizardContent" class="wizard-content"></div>

                    <!-- FOOTER SUPPRIMÉ SUR DEMANDE (Navigation automatique ou contextuelle) -->
                    <div class="wizard-footer" style="display:none;"></div>
                </div>
            </div>
        `;

        this._injectStyles();
    }

    _injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .wizard-split-screen { display: grid; grid-template-columns: 1fr 450px; height: 100%; width: 100%; overflow: hidden; }
            .wizard-visual { background: #1e1e1e; position: relative; display: flex; align-items: center; justify-content: center; }
            .wizard-panel { background: var(--bs-body-bg); border-left: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; padding: 2rem; height: 100%; }
            .wizard-content { flex: 1; display: flex; flex-direction: column; gap: 1.5rem; overflow-y: auto; padding-right: 10px; justify-content: flex-start; }
            .wizard-header { flex-shrink: 0; margin-bottom: 2rem; }
            .wizard-footer { flex-shrink: 0; margin-top: 2rem; }
            .response-btn-large { padding: 1.5rem; font-size: 1.2rem; border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--bs-body-color); transition: all 0.2s; text-align: left; display: flex; align-items: center; gap: 1rem; }
            .response-btn-large:hover, .response-btn-large.active { background: var(--bs-primary); color: white; transform: translateX(5px); }
            canvas#metrageCanvas { max-width: 90%; max-height: 90%; box-shadow: 0 0 50px rgba(0,0,0,0.5); }
            .assistant-bubble { border-left: 4px solid var(--bs-primary); }
        `;
        document.head.appendChild(style);
    }

    _bindEvents() {
        this.state.on('wizard:step', (stepIndex) => this.renderStep(stepIndex));
        this.state.on('wizard:restore', () => this.render());
        // Footer buttons removed, no listeners needed
        // document.getElementById('btnNext').addEventListener('click', () => this.state.nextStep());

        // Canvas Redraw
        this.state.on('wizard:update', (payload) => {
            if (this.canvasEngine) this.canvasEngine.draw(payload.product);
        });

        // Init Draw & Fetch Questions
        this.state.on('wizard:start', async (product) => {
            console.log('🔄 Fetching steps for', product.categorie);

            try {
                // 1. Fetch Steps from API
                const response = await fetch(`api/api_get_etapes.php?categorie=${encodeURIComponent(product.categorie)}`);
                const data = await response.json();

                if (data.success) {
                    // 2. Filter steps based on Context (Pose, Type, etc.)
                    product.questions = this._filterSteps(data.etapes, product);
                    console.log('✅ Steps loaded:', product.questions.length);

                    // 3. Render first step
                    this.renderStep(0);

                    // 4. Init Canvas
                    if (this.canvasEngine) setTimeout(() => this.canvasEngine.draw(product), 100);
                } else {
                    alert("Erreur chargement des étapes: " + data.error);
                }
            } catch (e) {
                console.error("Critical Error fetching steps:", e);
                alert("Erreur critique chargement étapes");
            }
        });

        this.state.on('wizard:cancel', () => location.reload());
        this.state.on('wizard:save-request', (product) => this._handleSave(product));
        this.state.on('ui:ask-category', () => this.renderCategorySelection());
        this.state.on('system:link-request', (aid) => this._handleLinkAffaire(aid));

        this._setupGlobalHandlers();
    }

    _setupGlobalHandlers() {
        // SECURITE: S'assurer que l'app globale existe
        if (!window.MetrageApp) window.MetrageApp = {};

        // SELF-REGISTRATION: Je suis l'UI.
        window.MetrageApp.ui = this;

        if (this.handlersBound) return;

        // Définition des handlers globaux pour les onclick HTML
        this.handleInput = (field, value) => {
            if (!value && value !== 0) return;
            this.state.updateCurrent(field, value);
            this.state.nextStep();
        };

        this.handleMultiInput = (field) => {
            const m1 = parseFloat(document.getElementById('multi_1').value) || 99999;
            const m2 = parseFloat(document.getElementById('multi_2').value) || 99999;
            const m3 = parseFloat(document.getElementById('multi_3').value) || 99999;

            if (m1 === 99999 && m2 === 99999 && m3 === 99999) {
                alert("Veuillez saisir au moins une mesure.");
                return;
            }

            const min = Math.min(m1, m2, m3);
            this.state.updateCurrent(field, min);
            this.state.nextStep();
        };

        this.handlePhoto = (field, input) => {
            const file = input.files[0];
            if (!file) return;
            console.log("Photo selected:", file.name);
            const btn = document.querySelector('label[for="photoInput"]');
            btn.innerHTML = `<i class="fas fa-check-circle fa-3x text-success mb-3"></i><div class="fw-bold">Photo enregistrée !</div>`;
            setTimeout(() => {
                this.state.updateCurrent(field, `img:${file.name}`);
                this.state.nextStep();
            }, 800);
        };

        this.handlersBound = true;
    }

    render() {
        if (this.state.uiState.mode === 'WIZARD') {
            this.renderStep(this.state.currentProduit.stepIndex);
        } else {
            this.renderDashboard();
        }
    }

    renderDashboard() {
        const affaire = window.INTERVENTION?.nom_affaire;
        const isLinked = affaire && affaire !== 'Métrage Libre';

        let html = `
            <div class="text-center animate__animated animate__fadeIn">
                <div class="mb-4">
                    <i class="fas fa-hard-hat fa-4x text-muted mb-3"></i>
                    <h2>Bienvenue dans le Studio</h2>
                    <p class="lead text-muted">${isLinked ? `Dossier lié : <strong>${affaire}</strong>` : 'Mode Métrage Libre (Non lié)'}</p>
                </div>
                <div class="d-grid gap-3 col-md-8 mx-auto">
                    <button class="response-btn-large" onclick="window.MetrageApp.state.emit('ui:ask-category')">
                        <i class="fas fa-plus-circle fa-2x text-primary"></i>
                        <div class="text-start"><div class="fw-bold">Ajouter un ouvrage</div><small class="text-muted">Fenêtre, Porte, Volet...</small></div>
                    </button>
                    ${!isLinked ? `<button class="response-btn-large" onclick="window.MetrageApp.ui.renderAffaireSelection()"><i class="fas fa-link fa-2x text-warning"></i><div class="text-start"><div class="fw-bold">Lier à une affaire</div><small class="text-muted">Rechercher un dossier client</small></div></button>` : ''}
                </div>
            </div>
        `;
        document.getElementById('wizardContent').innerHTML = html;
        document.getElementById('wizardTitle').innerText = "Accueil";
    }

    renderAffaireSelection() {
        document.getElementById('wizardTitle').innerText = "Lier à une Affaire";
        const container = document.getElementById('wizardContent');
        const affaires = window.AFFAIRES || [];

        let html = `
            <div class="assistant-message mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-robot fa-2x text-primary"></i>
                    <div class="assistant-bubble bg-light p-3 rounded-3">
                        Veuillez sélectionner le dossier client dans la liste ci-dessous.
                    </div>
                </div>
            </div>
            
            <div class="mb-3 animate__animated animate__fadeInUp">
                <input type="text" class="form-control form-control-lg" id="searchAffaire" placeholder="Rechercher (Nom, Client...)" onkeyup="window.MetrageApp.ui.filterAffaires(this.value)">
            </div>

            <div class="list-group animate__animated animate__fadeInUp" id="affairesList" style="max-height: 400px; overflow-y: auto;">
        `;

        if (affaires.length === 0) {
            html += `<div class="alert alert-warning">Aucune affaire disponible.</div>`;
        } else {
            affaires.forEach(aff => {
                html += `
                    <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3" 
                            onclick="window.MetrageApp.state.linkAffaire(${aff.id})">
                        <div>
                            <div class="fw-bold">${aff.nom_affaire}</div>
                            <small class="text-muted">${aff.client_nom || 'Client inconnu'}</small>
                        </div>
                        <i class="fas fa-link text-muted"></i>
                    </button>
                `;
            });
        }

        html += `</div>`;
        html += `<button class="btn btn-outline-secondary w-100 mt-3" onclick="location.reload()">Annuler</button>`;

        container.innerHTML = html;

        // Reset Visual
        if (this.canvasEngine) this.canvasEngine.clear();

        // Helper Filter
        this.filterAffaires = (val) => {
            const term = val.toLowerCase();
            const list = document.getElementById('affairesList');
            const items = list.getElementsByTagName('button');
            Array.from(items).forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(term) ? '' : 'none';
            });
        };
    }

    async _handleLinkAffaire(affaireId) {
        // Logique de liaison
        const container = document.getElementById('wizardContent');
        container.innerHTML = `<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i><h3>Liaison en cours...</h3></div>`;

        try {
            // Cas 1: Nouveau Métrage (ID=0) -> Create with Link
            if (!window.METRAGE_ID) {
                const formData = new FormData();
                formData.append('affaire_id', affaireId);
                const res = await fetch('api/api_init_metrage.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    window.location.href = `metrage_studio_v4.php?id=${json.id}`;
                } else {
                    throw new Error(json.error);
                }
            }
            // Cas 2: Métrage Existant -> Update Link
            else {
                const formData = new FormData();
                formData.append('metrage_id', window.METRAGE_ID);
                formData.append('affaire_id', affaireId);
                const res = await fetch('api/api_update_metrage_link.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    window.location.reload();
                } else {
                    throw new Error(json.error);
                }
            }
        } catch (e) {
            console.error(e);
            alert("Erreur lors de la liaison : " + e.message);
            location.reload();
        }
    }

    renderCategorySelection() {
        document.getElementById('wizardTitle').innerText = "Nouvel Ouvrage";
        const container = document.getElementById('wizardContent');
        const types = window.TYPES || [];
        const categories = [...new Set(types.map(t => t.categorie))];

        if (categories.length === 0) {
            container.innerHTML = '<div class="alert alert-warning">Aucun type de produit configuré.</div>';
            return;
        }

        let html = `<div class="assistant-message mb-3"><div class="d-flex align-items-center gap-3"><i class="fas fa-robot fa-2x text-primary"></i><div class="assistant-bubble bg-light p-3 rounded-3">Quelle est la <strong>famille</strong> du produit ?</div></div></div><div class="d-grid gap-3 animate__animated animate__fadeInUp">`;

        categories.forEach(cat => {
            let icon = 'fa-cube';
            const l = cat.toLowerCase();
            if (l.includes('fen')) icon = 'fa-window-maximize';
            if (l.includes('porte')) icon = 'fa-door-open';
            if (l.includes('volet')) icon = 'fa-blinds';
            if (l.includes('garage')) icon = 'fa-warehouse';

            html += `<button class="response-btn-large" onclick="window.MetrageApp.ui.renderTypeSelection('${cat}')"><i class="fas ${icon} fa-2x"></i><span class="fw-bold">${cat}</span></button>`;
        });

        html += `<button class="btn btn-outline-secondary mt-3" onclick="location.reload()">Annuler</button>`;

        // Petit lien pour lier à une affaire si pas déjà fait
        const affaire = window.INTERVENTION?.nom_affaire;
        const isLinked = affaire && affaire !== 'Métrage Libre';
        if (!isLinked) {
            html += `<div class="text-center mt-3"><a href="#" onclick="window.MetrageApp.ui.renderAffaireSelection(); return false;" class="text-muted small text-decoration-none"><i class="fas fa-link me-1"></i>Lier à une affaire</a></div>`;
        }

        html += `</div>`;
        container.innerHTML = html;
        if (this.canvasEngine) this.canvasEngine.clear();
    }

    renderTypeSelection(category) {
        document.getElementById('wizardTitle').innerText = category;
        const container = document.getElementById('wizardContent');

        // Filtrer les types par catégorie
        const allTypes = window.TYPES || [];
        const types = allTypes.filter(t => t.categorie === category); // Attention: sensible à la casse selon DB

        // Si un seul type ou aucun, on passe direct (ou message) -> mais mieux vaut laisser le choix si possible
        // Si vide, step suivant direct
        if (types.length === 0) {
            this.renderPoseSelection(category, null);
            return;
        }

        let html = `
            <div class="assistant-message mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-robot fa-2x text-primary"></i>
                    <div class="assistant-bubble bg-light p-3 rounded-3">
                        Précisez le <strong>type</strong> de ${category.toLowerCase()} :
                    </div>
                </div>
            </div>
            <div class="d-grid gap-3 animate__animated animate__fadeInUp" style="max-height: 400px; overflow-y: auto;">
        `;

        types.forEach(t => {
            // Sécurisation basique pour onclick
            const safeType = JSON.stringify(t).replace(/"/g, '&quot;');
            html += `
                <button class="response-btn-large" onclick='window.MetrageApp.ui.renderPoseSelection("${category}", ${safeType})'>
                    <i class="fas fa-chevron-right text-muted"></i>
                    <span class="fw-bold">${t.nom}</span>
                </button>
            `;
        });

        html += `<button class="btn btn-outline-secondary mt-3" onclick="window.MetrageApp.ui.renderCategorySelection()">Retour</button></div>`;
        container.innerHTML = html;
    }

    renderPoseSelection(category, typeObj) {
        document.getElementById('wizardTitle').innerText = "Type de Pose";
        const container = document.getElementById('wizardContent');

        // Sécurisation
        const safeType = typeObj ? JSON.stringify(typeObj).replace(/"/g, '&quot;') : 'null';
        const catUpper = category.toUpperCase();
        let poses = [];
        if (catUpper.includes('FENETRE') || catUpper.includes('PORTE') || catUpper.includes('CHASSIS') || catUpper.includes('COULISSANT') || catUpper.includes('MENUISERIE')) {
            poses = [
                { code: 'APPLIQUE', nom: 'Neuf / Applique', desc: 'Pose en applique intérieure', icon: 'fa-layer-group', color: 'text-info' },
                { code: 'RENOVATION', nom: 'Rénovation', desc: 'Sur dormant existant', icon: 'fa-hammer', color: 'text-warning' },
                { code: 'TUNNEL', nom: 'Tunnel', desc: "Dans l'épaisseur du mur", icon: 'fa-dungeon', color: 'text-success' },
                { code: 'FEUILLURE', nom: 'Feuillure', desc: 'Dans la maçonnerie existante', icon: 'fa-crop-alt', color: 'text-secondary' }
            ];
        } else if (catUpper.includes('VOLET')) {
            poses = [
                { code: 'FACADE', nom: 'Façade / Applique', desc: 'Pose en façade extérieure', icon: 'fa-window-maximize', color: 'text-info' },
                { code: 'TABLEAU', nom: 'Sous Linteau', desc: 'dans le tableau (enroulement ext/int)', icon: 'fa-compress-alt', color: 'text-primary' },
                { code: 'TRADITIONNEL', nom: 'Traditionnel', desc: 'Dans coffre existant (Titan/Menuisé)', icon: 'fa-box-open', color: 'text-secondary' }
            ];
        } else if (catUpper.includes('PORTAIL')) {
            poses = [
                { code: 'TABLEAU', nom: 'Entre Piliers', desc: 'Pose standard (Tableau)', icon: 'fa-arrows-alt-h', color: 'text-primary' },
                { code: 'APPLIQUE', nom: 'En Applique', desc: 'Arrière pilier (Ouv. 180° / Coulissant)', icon: 'fa-expand-alt', color: 'text-info' }
            ];
        } else if (catUpper.includes('GARAGE')) {
            poses = [
                { code: 'APPLIQUE_INT', nom: 'Applique Intérieure', desc: 'Standard (Sectionnelle/Enroulable)', icon: 'fa-arrow-circle-up', color: 'text-primary' },
                { code: 'TABLEAU', nom: 'En Tableau', desc: 'Sous linteau (Enroulable)', icon: 'fa-compress', color: 'text-warning' },
                { code: 'APPLIQUE_EXT', nom: 'Applique Extérieure', desc: 'Pose en façade (Rare)', icon: 'fa-external-link-alt', color: 'text-secondary' }
            ];
        } else if (catUpper.includes('STORE') || catUpper.includes('BANNE')) {
            poses = [
                { code: 'FACADE', nom: 'Façade', desc: 'Fixation murale', icon: 'fa-building', color: 'text-primary' },
                { code: 'PLAFOND', nom: 'Plafond', desc: 'Sous dalle ou balcon', icon: 'fa-arrow-up', color: 'text-info' }
            ];
        } else {
            poses = [
                { code: 'STANDARD', nom: 'Pose Standard', desc: 'Installation classique', icon: 'fa-check', color: 'text-primary' }
            ];
        }
        let html = `
            <div class="assistant-message mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-robot fa-2x text-primary"></i>
                    <div class="assistant-bubble bg-light p-3 rounded-3">
                        Comment sera posé cet ouvrage ?
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-3 animate__animated animate__fadeInUp">
        `;
        poses.forEach(p => {
            html += `
                <button class="response-btn-large" onclick='window.MetrageApp.ui.renderLocationSelection("${category}", ${safeType}, "${p.code}")'>
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas ${p.icon} fa-2x ${p.color}"></i>
                        <div>
                            <div class="fw-bold">${p.nom}</div>
                            <small class="text-muted">${p.desc}</small>
                        </div>
                    </div>
                </button>
            `;
        });
        html += `
            </div>

            <button class="btn btn-outline-secondary w-100 mt-3" onclick="window.MetrageApp.ui.renderTypeSelection('${category}')">Retour</button>
        `;

        container.innerHTML = html;
    }

    renderLocationSelection(category, typeObj, pose) {
        document.getElementById('wizardTitle').innerText = "Localisation";
        const container = document.getElementById('wizardContent');
        const safeType = typeObj ? JSON.stringify(typeObj).replace(/"/g, '&quot;') : 'null';

        let html = `
            <div class="assistant-message mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-robot fa-2x text-primary"></i>
                    <div class="assistant-bubble bg-light p-3 rounded-3">
                        Où se situe cet ouvrage ?
                    </div>
                </div>
            </div>
            
            <div class="animate__animated animate__fadeInUp">
                <div class="form-floating mb-4">
                    <input type="text" class="form-control form-control-lg" id="locInput" placeholder="Ex: Cuisine" value="RDC">
                    <label>Pièce ou Etage</label>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    ${['RDC', 'Étage 1', 'Salon', 'Cuisine', 'Suite Parentale', 'Garage'].map(tag =>
            `<button class="btn btn-outline-primary btn-sm rounded-pill" onclick="document.getElementById('locInput').value='${tag}'">${tag}</button>`
        ).join('')}
                </div>

                <button class="btn btn-primary btn-lg w-100" onclick="window.MetrageApp.ui.finishConfigAndStart('${category}', '${typeObj ? typeObj.id : ''}', '${pose}')">
                    Commencer le métré <i class="fas fa-arrow-right ms-2"></i>
                </button>
                <button class="btn btn-outline-secondary w-100 mt-3" onclick='window.MetrageApp.ui.renderPoseSelection("${category}", ${safeType})'>Retour</button>
            </div>
        `;

        container.innerHTML = html;

        // Store temp reference for finish
        this._tempTypeObj = typeObj;
    }

    finishConfigAndStart(category, typeId, pose) {
        const loc = document.getElementById('locInput').value || 'RDC';
        this.state.startWizard(category, this._tempTypeObj, loc, pose);
    }

    renderStep(index) {
        const product = this.state.currentProduit;
        if (!product || !product.questions) return;
        if (index >= product.questions.length) { this.renderSummary(); return; }

        const q = product.questions[index];
        const currentVal = product.data[q.code_etape] || '';

        document.getElementById('wizardTitle').innerText = product.categorie;
        const progress = Math.round(((index) / product.questions.length) * 100);
        document.querySelector('.progress-bar').style.width = `${progress}%`;

        const container = document.getElementById('wizardContent');
        container.innerHTML = '';

        // 1. ASSISTANT BUBBLE
        let html = `
            <div class="assistant-message mb-4 animate__animated animate__fadeInLeft">
                <div class="d-flex align-items-start gap-3">
                    <div class="assistant-avatar"><i class="fas fa-robot fa-2x text-primary"></i></div>
                    <div class="assistant-bubble bg-light p-3 rounded-3 shadow-sm border">
                        <h5 class="mb-1 text-primary">${q.message_assistant || `Question ${index + 1}`}</h5>
                        ${q.rappel ? `<div class="text-muted small mt-1"><i class="fas fa-info-circle"></i> ${q.rappel}</div>` : ''}
                        ${q.schema_url ? `<div class="mt-2"><img src="${q.schema_url}" class="img-fluid rounded border" alt="Schéma" style="max-height: 200px;"></div>` : ''}
                    </div>
                </div>
            </div>
            <div class="input-zone animate__animated animate__fadeInUp">
        `;

        switch (q.type_saisie) {
            case 'liste':
            case 'choix':
            case 'binaire':
                html += `<div class="d-flex flex-column gap-3">`;
                const opts = q.options || (q.type_saisie === 'binaire' ? ['Oui', 'Non'] : []);
                opts.forEach(opt => {
                    const cleanOpt = opt.replace(/'/g, "\\'");
                    const active = (opt === currentVal) ? 'active' : '';
                    html += `<button class="response-btn-large ${active}" onclick="window.MetrageApp.ui.handleInput('${q.code_etape}', '${cleanOpt}')"><i class="far ${active ? 'fa-check-circle' : 'fa-circle'}"></i> ${opt}</button>`;
                });
                html += `</div>`;
                break;
            case 'mm':
            case 'nombre':
                html += `<div class="form-floating mb-3"><input type="number" class="form-control form-control-lg fs-1" id="wizardInput" value="${currentVal}" placeholder="0" autofocus onkeydown="if(event.key==='Enter') window.MetrageApp.ui.handleInput('${q.code_etape}', this.value)"><label>Valeur en ${q.type_saisie === 'mm' ? 'mm' : ''}</label></div><button class="btn btn-primary btn-lg w-100" onclick="window.MetrageApp.ui.handleInput('${q.code_etape}', document.getElementById('wizardInput').value)">Valider</button>`;
                break;
            case 'multi_mm':
                html += `<div class="animate__animated animate__fadeInUp"><label class="form-label text-muted mb-3"><i class="fas fa-ruler-combined"></i> Prenez 3 mesures (Haut/Milieu/Bas), je garderai la plus petite.</label><div class="row g-2 mb-3"><div class="col-4"><div class="form-floating"><input type="number" class="form-control text-center" id="multi_1" placeholder="M1"><label>M1</label></div></div><div class="col-4"><div class="form-floating"><input type="number" class="form-control text-center" id="multi_2" placeholder="M2"><label>M2</label></div></div><div class="col-4"><div class="form-floating"><input type="number" class="form-control text-center" id="multi_3" placeholder="M3"><label>M3</label></div></div></div><button class="btn btn-primary btn-lg w-100" onclick="window.MetrageApp.ui.handleMultiInput('${q.code_etape}')">Calculer & Valider</button></div>`;
                break;
            case 'photo':
                html += `<div class="animate__animated animate__fadeInUp text-center"><input type="file" id="photoInput" accept="image/*" capture="environment" hidden onchange="window.MetrageApp.ui.handlePhoto('${q.code_etape}', this)"><label for="photoInput" class="response-btn-large justify-content-center flex-column py-5" style="border-style: dashed; cursor: pointer;"><i class="fas fa-camera fa-3x mb-3 text-primary"></i><div class="fw-bold fs-5">Prendre une photo</div><div class="text-muted small">ou choisir depuis la galerie</div></label><button class="btn btn-link text-muted mt-3" onclick="window.MetrageApp.ui.handleInput('${q.code_etape}', 'Ignoré')">Passer cette étape</button></div>`;
                break;
            default:
                html += `<div class="form-floating mb-3"><input type="text" class="form-control form-control-lg" id="wizardInput" value="${currentVal}" placeholder="..." autofocus onkeydown="if(event.key==='Enter') window.MetrageApp.ui.handleInput('${q.code_etape}', this.value)"><label>Votre réponse</label></div><button class="btn btn-primary btn-lg w-100" onclick="window.MetrageApp.ui.handleInput('${q.code_etape}', document.getElementById('wizardInput').value)">Valider</button>`;
        }
        html += `</div>`;
        container.innerHTML = html;
        const input = document.getElementById('wizardInput');
        if (input) setTimeout(() => input.focus(), 100);
        if (this.canvasEngine) this.canvasEngine.draw(product);
    }

    renderSummary() {
        document.getElementById('wizardTitle').innerText = "Récapitulatif";
        document.querySelector('.progress-bar').style.width = `100%`;
        const data = this.state.currentProduit.data;
        let html = '<div class="list-group list-group-flush">';
        if (data.dimensions) { html += '<div class="list-group-item active">Dimensions</div>'; for (const [k, v] of Object.entries(data.dimensions)) html += `<div class="list-group-item d-flex justify-content-between"><span>${k}</span><strong>${v}</strong></div>`; }
        if (data.technique) { html += '<div class="list-group-item active mt-3">Technique</div>'; for (const [k, v] of Object.entries(data.technique)) html += `<div class="list-group-item d-flex justify-content-between"><span>${k}</span><strong>${v}</strong></div>`; }
        if (data.metadata?.surface_m2) html += `<div class="list-group-item bg-light mt-3"><strong>Surface: ${data.metadata.surface_m2} m²</strong></div>`;
        html += '</div>';
        html += `<button id="btnSaveFinal" class="btn btn-success btn-lg w-100 mt-4" onclick="window.MetrageApp.state.saveProduct()"><i class="fas fa-save"></i> Enregistrer</button>`;
        document.getElementById('wizardContent').innerHTML = html;
    }

    async _handleSave(product) {
        const btn = document.getElementById('btnSaveFinal');
        if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...'; btn.disabled = true; }
        try {
            const typeName = product.data.technique?.type_nom || product.categorie;
            const typeObj = window.TYPES.find(t => t.slug === typeName || t.nom === typeName) || window.TYPES[0];
            const result = await this.api.addLigne(window.METRAGE_ID, typeObj ? typeObj.id : 0, product.data.localisation || 'RDC', product.data);
            if (result.success) { alert("Produit enregistré avec succès ! 🚀"); location.reload(); }
            else throw new Error(result.error);
        } catch (e) { console.error(e); alert(`Erreur: ${e.message}`); if (btn) { btn.innerHTML = '<i class="fas fa-save"></i> Réessayer'; btn.disabled = false; } }
    }
}

/**
 * Filtre les étapes selon le contexte (Pose, Type, etc.)
 * @param {Array} steps - Étapes brutes de la BDD
 * @param {Object} product - Produit en cours (avec ses data)
 */
_filterSteps(steps, product) {
    return steps.filter(step => {
        // 1. Si pas de condition, on garde
        if (!step.condition) return true;

        // 2. Analyse condition
        const cond = step.condition; // { field: 'technique.pose', value: 'RENOVATION', operator: 'eq' }

        // Récupérer valeur actuelle dans le produit
        const fieldPath = cond.field.split('.');
        let value = product.data;
        for (const key of fieldPath) {
            value = value ? value[key] : null;
        }

        // Comparaison
        if (cond.operator === 'neq') {
            return value !== cond.value;
        }
        // Default 'eq'
        return value === cond.value;
    });
}
}
