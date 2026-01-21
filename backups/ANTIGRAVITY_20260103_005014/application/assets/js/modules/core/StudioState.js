/**
 * StudioState.js - Gestion Centralisée de l'État V4.0
 * 
 * Responsabilité : State management (Single Source of Truth)
 * Pattern : Observer (événements pour notifier les changements)
 * 
 * @version 4.0.0
 */

export class StudioState {

    constructor() {
        // État métrage
        this.metrageId = null;
        this.intervention = null;
        this.products = [];
        this.currentProduct = null;
        this.editMode = false;

        // Workflow
        this.currentStep = 0;
        this.workflow = [];
        this.tempData = {};

        // Références
        this.types = [];
        this.affaires = [];

        // Listeners (Observer pattern)
        this.listeners = {
            'state-changed': [],
            'product-added': [],
            'product-updated': [],
            'product-deleted': []
        };
    }

    // =====================================================
    // INITIALISATION
    // =====================================================

    /**
     * Initialiser l'état depuis les données PHP
     * 
     * @param {Object} config - {metrageId, intervention, lignes, types, affaires}
     */
    init(config) {
        this.metrageId = config.metrageId || null;
        this.intervention = config.intervention || this.getDefaultIntervention();
        this.types = config.types || [];
        this.affaires = config.affaires || [];

        // Transformer lignes en products
        if (config.lignes && Array.isArray(config.lignes)) {
            this.products = config.lignes.map(ligne => this.transformLigneToProduct(ligne));
        }

        this.emit('state-changed', { type: 'init' });
    }

    /**
     * Intervention par défaut (métrage libre)
     */
    getDefaultIntervention() {
        return {
            nom_affaire: 'Métrage Libre',
            client_nom: 'Non lié',
            statut: 'A_PLANIFIER'
        };
    }

    /**
     * Transformer une ligne DB en product
     */
    transformLigneToProduct(ligne) {
        return {
            id: ligne.id,
            typeId: ligne.metrage_type_id,
            typeName: ligne.type_nom || 'Inconnu',
            categorie: ligne.categorie,
            localisation: ligne.localisation,
            data: ligne.donnees_json, // JSON V3 déjà décodé
            complete: true
        };
    }

    // =====================================================
    // GESTION PRODUITS
    // =====================================================

    /**
     * Ajouter un produit
     * 
     * @param {Object} product - {typeId, typeName, localisation, data}
     */
    addProduct(product) {
        // Générer ID temporaire si pas encore sauvegardé
        if (!product.id) {
            product.id = `temp_${Date.now()}`;
            product.needsSync = true;
        }

        this.products.push(product);
        this.emit('product-added', product);
        this.emit('state-changed', { type: 'product-added', product });
    }

    /**
     * Finaliser la synchro backend (ID temporaire -> ID réel)
     * 
     * @param {string} tempId
     * @param {number} realId
     */
    finalizeProductSync(tempId, realId) {
        const index = this.products.findIndex(p => p.id === tempId);
        if (index === -1) return;

        // Mise à jour atomique sans déclencher newSync
        this.products[index] = {
            ...this.products[index],
            id: realId,
            needsSync: false
        };

        this.emit('product-updated', this.products[index]);
        this.emit('state-changed', { type: 'sync-completed', oldId: tempId, newId: realId });
    }

    /**
     * Mettre à jour un produit
     * 
     * @param {string|number} productId
     * @param {Object} updates - Données à mettre à jour
     */
    updateProduct(productId, updates) {
        const index = this.products.findIndex(p => p.id === productId);
        if (index === -1) {
            console.error(`Product ${productId} not found`);
            return;
        }

        this.products[index] = {
            ...this.products[index],
            ...updates,
            needsSync: true
        };

        this.emit('product-updated', this.products[index]);
        this.emit('state-changed', { type: 'product-updated', product: this.products[index] });
    }

    /**
     * Supprimer un produit
     * 
     * @param {string|number} productId
     */
    deleteProduct(productId) {
        const index = this.products.findIndex(p => p.id === productId);
        if (index === -1) {
            console.error(`Product ${productId} not found`);
            return;
        }

        const deleted = this.products.splice(index, 1)[0];
        this.emit('product-deleted', deleted);
        this.emit('state-changed', { type: 'product-deleted', product: deleted });
    }

    /**
     * Récupérer un produit par ID
     * 
     * @param {string|number} productId
     * @returns {Object|null}
     */
    getProduct(productId) {
        return this.products.find(p => p.id === productId) || null;
    }

    /**
     * Récupérer tous les produits
     * 
     * @returns {Array}
     */
    getProducts() {
        return [...this.products]; // Clone pour éviter mutations externes
    }

    /**
     * Compter les produits
     * 
     * @returns {number}
     */
    getProductCount() {
        return this.products.length;
    }

    // =====================================================
    // WORKFLOW
    // =====================================================

    /**
     * Démarrer un nouveau workflow de saisie
     * 
     * @param {number} typeId - ID du type de produit
     */
    startWorkflow(typeId) {
        const type = this.types.find(t => t.id === typeId);
        if (!type) {
            throw new Error(`Type ${typeId} not found`);
        }

        this.currentProduct = {
            typeId: type.id,
            typeName: type.nom,
            categorie: type.categorie,
            localisation: '',
            data: this.getEmptyJsonV3(),
            complete: false
        };

        // Charger workflow depuis type (si défini en JSON)
        this.workflow = type.workflow_json || this.getDefaultWorkflow();
        this.currentStep = 0;
        this.tempData = {};

        this.emit('state-changed', { type: 'workflow-started', typeId });
    }

    /**
     * Passer à l'étape suivante
     */
    nextStep() {
        if (this.currentStep < this.workflow.length - 1) {
            this.currentStep++;
            this.emit('state-changed', { type: 'step-changed', step: this.currentStep });
        }
    }

    /**
     * Revenir à l'étape précédente
     */
    previousStep() {
        if (this.currentStep > 0) {
            this.currentStep--;
            this.emit('state-changed', { type: 'step-changed', step: this.currentStep });
        }
    }

    /**
     * Finaliser le workflow (sauvegarder produit)
     */
    finalizeWorkflow() {
        if (!this.currentProduct) {
            throw new Error('No active workflow');
        }

        // Construire JSON V3 depuis tempData
        this.currentProduct.data = this.buildJsonV3FromTempData();
        this.currentProduct.complete = true;

        this.addProduct(this.currentProduct);

        // Reset workflow
        this.currentProduct = null;
        this.workflow = [];
        this.currentStep = 0;
        this.tempData = {};

        this.emit('state-changed', { type: 'workflow-completed' });
    }

    /**
     * Annuler le workflow
     */
    cancelWorkflow() {
        this.currentProduct = null;
        this.workflow = [];
        this.currentStep = 0;
        this.tempData = {};

        this.emit('state-changed', { type: 'workflow-cancelled' });
    }

    // =====================================================
    // HELPERS JSON V3
    // =====================================================

    /**
     * Structure JSON V3 vide
     */
    getEmptyJsonV3() {
        return {
            dimensions: {},
            technique: {},
            accessoires: [],
            securite: {},
            logistique: {},
            business: {},
            metadata: {
                version_schema: '3.0',
                saisie_date: new Date().toISOString()
            }
        };
    }

    /**
     * Construire JSON V3 depuis tempData
     */
    buildJsonV3FromTempData() {
        const json = this.getEmptyJsonV3();

        // Mapper tempData vers structure V3
        // TODO: Logique de mapping selon le type de produit
        Object.assign(json.dimensions, this.tempData.dimensions || {});
        Object.assign(json.technique, this.tempData.technique || {});

        return json;
    }

    /**
     * Workflow par défaut (générique)
     */
    getDefaultWorkflow() {
        return [
            { type: 'text', field: 'localisation', label: 'Localisation' },
            { type: 'number', field: 'largeur', label: 'Largeur (mm)' },
            { type: 'number', field: 'hauteur', label: 'Hauteur (mm)' }
        ];
    }

    // =====================================================
    // OBSERVER PATTERN
    // =====================================================

    /**
     * S'abonner à un événement
     * 
     * @param {string} event - Nom de l'événement
     * @param {Function} callback - Fonction à appeler
     */
    on(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);
    }

    /**
     * Se désabonner d'un événement
     * 
     * @param {string} event
     * @param {Function} callback
     */
    off(event, callback) {
        if (!this.listeners[event]) return;
        this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
    }

    /**
     * Émettre un événement
     * 
     * @param {string} event
     * @param {*} data
     */
    emit(event, data) {
        if (!this.listeners[event]) return;
        this.listeners[event].forEach(callback => {
            try {
                callback(data);
            } catch (e) {
                console.error(`Error in listener for ${event}:`, e);
            }
        });
    }

    // =====================================================
    // EXPORT / IMPORT (Pour OfflineManager)
    // =====================================================

    /**
     * Exporter l'état complet
     * 
     * @returns {Object}
     */
    export() {
        return {
            metrageId: this.metrageId,
            intervention: this.intervention,
            products: this.products,
            currentProduct: this.currentProduct,
            tempData: this.tempData,
            timestamp: Date.now()
        };
    }

    /**
     * Importer un état (restauration)
     * 
     * @param {Object} state
     */
    import(state) {
        this.metrageId = state.metrageId;
        this.intervention = state.intervention;
        this.products = state.products || [];
        this.currentProduct = state.currentProduct;
        this.tempData = state.tempData || {};

        this.emit('state-changed', { type: 'import', state });
    }
}
