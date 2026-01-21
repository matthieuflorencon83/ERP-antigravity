/**
 * StudioUI.js - Gestion Interface Utilisateur V4.0
 * 
 * Responsabilité : Manipulation DOM, rendering, événements
 * Constitution v3.0 : "L'IMAGE est prioritaire, le TEXTE est secondaire"
 * 
 * @version 4.0.0
 */

import { CalculateurMetier } from '../business/CalculateurMetier.js';

export class StudioUI {

    constructor(state, api) {
        this.state = state;
        this.api = api;
        this.calculateur = new CalculateurMetier();

        // Conteneurs DOM
        this.containers = {
            assistant: document.getElementById('assistant_messages'),
            input: document.getElementById('input_container'),
            inputWrapper: document.getElementById('input_zone_wrapper'),
            tree: document.getElementById('tree_products')
        };

        // APEX: Debug logging
        console.log('🔍 StudioUI Containers:', this.containers);
        Object.entries(this.containers).forEach(([name, el]) => {
            if (!el) console.error(`❌ Container ${name} is NULL`);
            else console.log(`✅ Container ${name} found`);
        });
    }

    /**
     * Initialiser UI
     */
    async init() {
        // Vérifier conteneurs
        Object.entries(this.containers).forEach(([name, el]) => {
            if (!el) {
                throw new Error(`Container ${name} not found`);
            }
        });

        // Écouter événements state
        this.state.on('product-added', (product) => this.onProductAdded(product));
        this.state.on('product-updated', (product) => this.onProductUpdated(product));
        this.state.on('product-deleted', (product) => this.onProductDeleted(product));
        this.state.on('state-changed', (data) => this.onStateChanged(data));

        console.log('✅ UI initialized');
    }

    // =====================================================
    // ÉCRANS PRINCIPAUX
    // =====================================================

    /**
     * Afficher écran d'accueil (nouveau métrage)
     */
    showWelcome() {
        this.clearAssistant();
        this.showAssistant(`
            <div class="welcome-screen">
                <div class="welcome-icon">
                    <i class="fas fa-ruler-combined fa-3x text-primary"></i>
                </div>
                <h2 class="mt-4">Nouveau Métrage</h2>
                <p class="text-muted">Lier à une affaire ou créer un métrage libre</p>
            </div>
        `);

        this.showInput('custom', {
            html: this._buildAffaireSelector()
        });

        // APEX: Attacher événements APRÈS insertion DOM
        this._attachAffaireSelectorEvents();
    }

    /**
     * Afficher dashboard (métrage existant)
     */
    showDashboard() {
        this.clearAssistant();

        const products = this.state.getProducts();
        const intervention = this.state.intervention;

        this.showAssistant(`
            <div class="dashboard-header">
                <h3>${intervention.nom_affaire || 'Métrage Libre'}</h3>
                <p class="text-muted">${intervention.client_nom || 'Non lié'}</p>
                <div class="stats mt-3">
                    <span class="badge bg-primary">${products.length} produit(s)</span>
                </div>
            </div>
        `);

        this.renderProductTree();
        this.showInput('options', [
            {
                label: '+ Nouveau Produit',
                icon: 'fa-plus',
                action: () => this.showTypeSelector()
            }
        ]);
    }

    /**
     * Afficher sélecteur de type produit
     */
    showTypeSelector() {
        this.clearAssistant();
        this.showAssistant(`
            <h3>Choisir un type de produit</h3>
            <p class="text-muted">Sélectionnez la catégorie</p>
        `);

        // Grouper types par catégorie
        const grouped = this.state.types.reduce((acc, type) => {
            if (!acc[type.categorie]) {
                acc[type.categorie] = [];
            }
            acc[type.categorie].push(type);
            return acc;
        }, {});

        const options = [];
        Object.entries(grouped).forEach(([categorie, types]) => {
            options.push({
                label: this._getCategorieLabel(categorie),
                icon: this._getCategorieIcon(categorie),
                action: () => this._showTypesInCategorie(categorie, types)
            });
        });

        this.showInput('options', options);
    }

    // =====================================================
    // MANIPULATION DOM
    // =====================================================

    /**
     * Afficher message assistant
     */
    showAssistant(html) {
        const bubble = document.createElement('div');
        bubble.className = 'assistant-bubble animate__animated animate__fadeInUp';
        bubble.innerHTML = html;
        this.containers.assistant.appendChild(bubble);
        this._scrollToBottom();
    }

    /**
     * Afficher message utilisateur
     */
    showUserMessage(text) {
        const bubble = document.createElement('div');
        bubble.className = 'user-bubble animate__animated animate__fadeInUp';
        bubble.textContent = text;
        this.containers.assistant.appendChild(bubble);
        this._scrollToBottom();
    }

    /**
     * Vider assistant
     */
    clearAssistant() {
        this.containers.assistant.innerHTML = '';
    }

    /**
     * Afficher zone input
     * 
     * @param {string} type - 'text'|'number'|'options'|'custom'
     * @param {*} config
     */
    showInput(type, config) {
        this.containers.inputWrapper.style.display = 'block';

        switch (type) {
            case 'text':
            case 'number':
                this._renderTextInput(type, config);
                break;

            case 'options':
                this._renderOptions(config);
                break;

            case 'custom':
                this.containers.input.innerHTML = config.html;
                break;
        }

        this._scrollToBottom();
    }

    /**
     * Cacher zone input
     */
    hideInput() {
        this.containers.inputWrapper.style.display = 'none';
    }

    // =====================================================
    // RENDERING HELPERS
    // =====================================================

    /**
     * Render input texte/nombre
     * 
     * @private
     */
    _renderTextInput(type, config) {
        const inputType = type === 'number' ? 'number' : 'text';
        const placeholder = config.placeholder || config.label || '';

        this.containers.input.innerHTML = `
            <div class="input-group input-group-lg">
                <input 
                    type="${inputType}" 
                    class="form-control" 
                    placeholder="${placeholder}"
                    id="studio-input"
                    ${type === 'number' ? 'min="0" step="1"' : ''}
                >
                <button class="btn btn-primary" id="studio-submit">
                    <i class="fas fa-check"></i> Valider
                </button>
            </div>
        `;

        const input = document.getElementById('studio-input');
        const submit = document.getElementById('studio-submit');

        // Focus auto
        setTimeout(() => input.focus(), 100);

        // Submit sur Enter
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                submit.click();
            }
        });

        // Action submit
        submit.addEventListener('click', () => {
            const value = type === 'number' ? parseFloat(input.value) : input.value;
            if (config.onSubmit) {
                config.onSubmit(value);
            }
        });
    }

    /**
     * Render boutons options
     * 
     * @private
     */
    _renderOptions(options) {
        const html = options.map(opt => `
            <button class="btn btn-lg btn-outline-primary w-100 mb-2 option-btn" data-action="${opt.label}">
                ${opt.icon ? `<i class="fas ${opt.icon} me-2"></i>` : ''}
                ${opt.label}
            </button>
        `).join('');

        this.containers.input.innerHTML = `<div class="options-grid">${html}</div>`;

        // Attacher événements
        options.forEach((opt, index) => {
            const btn = this.containers.input.querySelectorAll('.option-btn')[index];
            if (btn && opt.action) {
                btn.addEventListener('click', opt.action);
            }
        });
    }

    /**
     * Construire sélecteur affaire (Select2)
     * 
     * @private
     */
    _buildAffaireSelector() {
        const html = `
            <div class="affaire-selector">
                <label class="form-label">Rechercher une affaire</label>
                <select id="affaire-select" class="form-select" style="width: 100%">
                    <option value="">-- Rechercher --</option>
                    ${this.state.affaires.map(a => `
                        <option value="${a.id}">${a.nom_affaire} - ${a.client_nom}</option>
                    `).join('')}
                </select>
                <div class="mt-3 d-grid gap-2">
                    <button class="btn btn-primary btn-lg" id="btn-link-create">
                        <i class="fas fa-link me-2"></i> Lier & Créer
                    </button>
                    <button class="btn btn-outline-secondary btn-lg" id="btn-libre">
                        <i class="fas fa-file me-2"></i> Métrage Libre
                    </button>
                </div>
            </div>
        `;

        // Attacher événements après insertion DOM
        setTimeout(() => {
            const btnLink = document.getElementById('btn-link-create');
            const btnLibre = document.getElementById('btn-libre');
            const select = document.getElementById('affaire-select');

            if (btnLink) {
                btnLink.addEventListener('click', async () => {
                    const affaireId = select.value;
                    if (!affaireId) {
                        alert('Veuillez sélectionner une affaire');
                        return;
                    }
                    await this._createMetrageWithAffaire(parseInt(affaireId));
                });
            }

            if (btnLibre) {
                btnLibre.addEventListener('click', async () => {
                    await this._createMetrageLibre();
                });
            }

            // Initialiser Select2 si disponible
            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery('#affaire-select').select2({
                    placeholder: 'Rechercher une affaire...',
                    allowClear: true
                });
            }
        }, 100);

        return html;
    }

    /**
     * Créer métrage lié à une affaire
     * @private
     */
    async _createMetrageWithAffaire(affaireId) {
        try {
            const result = await this.api.createIntervention(affaireId);
            if (result.success) {
                window.location.href = `metrage_studio_v4.php?id=${result.id}`;
            }
        } catch (error) {
            alert(`Erreur: ${error.message}`);
        }
    }

    /**
     * Créer métrage libre
     * @private
     */
    async _createMetrageLibre() {
        try {
            const result = await this.api.createIntervention(null);
            if (result.success) {
                window.location.href = `metrage_studio_v4.php?id=${result.id}`;
            }
        } catch (error) {
            alert(`Erreur: ${error.message}`);
        }
    }

    /**
     * Attacher événements sélecteur affaire
     * @private
     */
    _attachAffaireSelectorEvents() {
        setTimeout(() => {
            const btnLink = document.getElementById('btn-link-create');
            const btnLibre = document.getElementById('btn-libre');
            const select = document.getElementById('affaire-select');

            console.log('🔍 Attaching events:', { btnLink, btnLibre, select });

            if (btnLink) {
                btnLink.addEventListener('click', async () => {
                    console.log('✅ Lier & Créer clicked');
                    const affaireId = select.value;
                    if (!affaireId) {
                        alert('Veuillez sélectionner une affaire');
                        return;
                    }
                    await this._createMetrageWithAffaire(parseInt(affaireId));
                });
                console.log('✅ Event attached to btnLink');
            } else {
                console.error('❌ btnLink not found');
            }

            if (btnLibre) {
                btnLibre.addEventListener('click', async () => {
                    console.log('✅ Métrage Libre clicked');
                    await this._createMetrageLibre();
                });
                console.log('✅ Event attached to btnLibre');
            } else {
                console.error('❌ btnLibre not found');
            }

            // Initialiser Select2 si disponible
            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery('#affaire-select').select2({
                    placeholder: 'Rechercher une affaire...',
                    allowClear: true
                });
            }
        }, 200);
    }

    /**
     * Render arborescence produits
     */
    renderProductTree() {
        if (!this.containers.tree) return;

        const products = this.state.getProducts();

        if (products.length === 0) {
            this.containers.tree.innerHTML = '<p class="text-muted text-center">Aucun produit</p>';
            return;
        }

        const html = products.map(p => `
            <div class="product-item" data-id="${p.id}">
                <div class="product-icon">
                    <i class="fas ${this._getCategorieIcon(p.categorie)}"></i>
                </div>
                <div class="product-info">
                    <strong>${p.typeName}</strong>
                    <small class="text-muted">${p.localisation}</small>
                </div>
                <div class="product-actions">
                    <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${p.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        this.containers.tree.innerHTML = html;

        // Attacher événements
        this.containers.tree.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                this.deleteProduct(id);
            });
        });
    }

    // =====================================================
    // ACTIONS
    // =====================================================

    /**
     * Supprimer un produit
     */
    async deleteProduct(productId) {
        if (!confirm('Supprimer ce produit ?')) {
            return;
        }

        try {
            // Si ID temporaire, juste supprimer du state
            if (String(productId).startsWith('temp_')) {
                this.state.deleteProduct(productId);
                return;
            }

            // Sinon, appeler API
            await this.api.deleteLigne(productId);
            this.state.deleteProduct(productId);

        } catch (error) {
            alert(`Erreur suppression: ${error.message}`);
        }
    }

    // =====================================================
    // EVENT HANDLERS
    // =====================================================

    /**
     * Produit ajouté
     */
    async onProductAdded(product) {
        this.renderProductTree();
        this.showUserMessage(`✓ ${product.typeName} ajouté`);

        // APEX: Sauvegarde Backend Immédiate
        if (product.needsSync) {
            try {
                // Feedback visuel (op optionnel)
                console.log('💾 Saving to backend...', product);

                const result = await this.api.addLigne(
                    window.METRAGE_ID, // Global
                    product.typeId,
                    product.localisation,
                    product.data
                );

                if (result.success) {
                    // Confirmer synchro et mettre à jour ID
                    this.state.finalizeProductSync(product.id, result.id);
                    console.log(`✅ Saved with ID: ${result.id}`);
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('❌ Save failed:', error);
                this.showUserMessage(`⚠️ Erreur sauvegarde: ${error.message}`);
                // TODO: Marquer comme "Erreur Synchro" dans l'UI
            }
        }
    }

    /**
     * Produit mis à jour
     */
    onProductUpdated(product) {
        this.renderProductTree();
    }

    /**
     * Produit supprimé
     */
    onProductDeleted(product) {
        this.renderProductTree();
        this.showUserMessage(`✓ ${product.typeName} supprimé`);
    }

    /**
     * État changé
     */
    onStateChanged(data) {
        // Mettre à jour compteurs, etc.
        console.log('State changed:', data.type);
    }

    // =====================================================
    // UTILITAIRES
    // =====================================================

    /**
     * Scroll vers le bas
     * 
     * @private
     */
    _scrollToBottom() {
        const scroll = document.getElementById('assistant_scroll');
        if (scroll) {
            setTimeout(() => {
                scroll.scrollTop = scroll.scrollHeight;
            }, 100);
        }
    }

    /**
     * Icône catégorie
     * 
     * @private
     */
    _getCategorieIcon(categorie) {
        const icons = {
            'menuiserie': 'fa-window-maximize',
            'garage': 'fa-warehouse',
            'portail': 'fa-door-open',
            'pergola': 'fa-umbrella',
            'store': 'fa-blind',
            'volet': 'fa-bars',
            'veranda': 'fa-home',
            'moustiquaire': 'fa-bug',
            'tav': 'fa-tools'
        };
        return icons[categorie] || 'fa-cube';
    }

    /**
     * Label catégorie
     * 
     * @private
     */
    _getCategorieLabel(categorie) {
        const labels = {
            'menuiserie': '🪟 Menuiserie',
            'garage': '🚗 Garage',
            'portail': '🚪 Portail',
            'pergola': '☀️ Pergola',
            'store': '🌂 Store',
            'volet': '🔲 Volet',
            'veranda': '🏠 Véranda',
            'moustiquaire': '🦟 Moustiquaire',
            'tav': '🔧 TAV'
        };
        return labels[categorie] || categorie;
    }

    /**
     * Afficher types dans une catégorie
     * 
     * @private
     */
    _showTypesInCategorie(categorie, types) {
        this.clearAssistant();
        this.showAssistant(`
            <h3>${this._getCategorieLabel(categorie)}</h3>
            <p class="text-muted">Choisir un type</p>
        `);

        const options = types.map(type => ({
            label: type.nom,
            action: () => this.state.startWorkflow(type.id)
        }));

        options.push({
            label: '← Retour',
            icon: 'fa-arrow-left',
            action: () => this.showTypeSelector()
        });

        this.showInput('options', options);
    }
}
