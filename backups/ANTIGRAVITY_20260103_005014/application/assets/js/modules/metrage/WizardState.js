/**
 * WizardState.js - Cerveau du Wizard V4
 * 
 * Responsabilité : Gestion de l'état du métrage en mode "Split Screen"
 * Architecture : Event-Driven (Observer Pattern)
 * Sécurité : Sauvegarde LocalStorage Crash-Proof
 * 
 * @version 4.1.0 (Wizard Edition)
 */

export class WizardState {

    constructor() {
        // État Global
        this.metrageId = window.METRAGE_ID || 0;
        this.produits = []; // Liste des ouvrages
        this.currentProduit = null; // Ouvrage en cours d'édition

        // État UI (Split Screen)
        this.uiState = {
            step: 0, // Étape du wizard (0=Config, 1=Dimensions, 2=Options)
            mode: 'VIEW', // VIEW | EDIT | WIZARD
            sidebarOpen: true,
            recapOpen: true
        };

        // Système d'événements
        this.listeners = {};
    }

    /**
     * Tente de lier le métrage à une affaire
     * Note: Ceci est une action critique qui va recharger la page
     */
    async linkAffaire(affaireId) {
        // Cette méthode sera appelée par l'UI qui a accès à l'API
        // On émet juste l'intention pour que l'UI réagisse si besoin
        this.emit('system:link-request', affaireId);
    }

    // ============================================================
    // GESTION DES DONNÉES (CRUD)
    // ============================================================

    /**
     * Initialise l'état avec les données du serveur
     */
    init(serverData) {
        this.produits = serverData.lignes || [];
        this.emit('init', this.produits);
        console.log('WizardState Initialized 🚀');
    }

    /**
     * Démarre un nouveau wizard pour un produit
     */
    startWizard(categorie, typeObj = null, localisation = 'RDC', pose = 'APPLIQUE') {
        this.currentProduit = {
            tempId: Date.now(), // ID temporaire
            categorie: categorie,
            data: {
                localisation: localisation,
                technique: {
                    pose: pose,
                    type_nom: typeObj ? typeObj.nom : '',
                    type_slug: typeObj ? typeObj.slug : '',
                    type_id: typeObj ? typeObj.id : 0
                }
            },
            stepIndex: 0
        };
        this.uiState.mode = 'WIZARD';
        this.emit('wizard:start', this.currentProduit);
    }

    /**
     * Met à jour une donnée du produit en cours
     */
    updateCurrent(field, value) {
        if (!this.currentProduit) return;

        this.currentProduit.data[field] = value;
        this.emit('wizard:update', { field, value, product: this.currentProduit });

        // Auto-Save Draft
        this.saveToLocalStorage();
    }

    /**
     * Valide l'étape et passe à la suivante
     */
    nextStep() {
        if (!this.currentProduit) return;
        this.currentProduit.stepIndex++;
        this.emit('wizard:step', this.currentProduit.stepIndex);
    }

    // ============================================================
    // PERSISTANCE (CRASH-PROOF)
    // ============================================================

    saveToLocalStorage() {
        const key = `wizard_draft_${this.metrageId}`;
        const payload = {
            timestamp: Date.now(),
            product: this.currentProduit
        };
        localStorage.setItem(key, JSON.stringify(payload));
        // console.log('Draft saved 💾');
    }

    restoreFromCrash() {
        const key = `wizard_draft_${this.metrageId}`;
        const raw = localStorage.getItem(key);
        if (raw) {
            const draft = JSON.parse(raw);
            // Si le brouillon a moins de 24h
            if (Date.now() - draft.timestamp < 86400000) {
                this.currentProduit = draft.product;
                this.uiState.mode = 'WIZARD';
                this.emit('wizard:restore', this.currentProduit);
                return true;
            }
        }
        return false;
    }

    // ============================================================
    // EVENT SYSTEM
    // ============================================================

    on(event, callback) {
        if (!this.listeners[event]) this.listeners[event] = [];
        this.listeners[event].push(callback);
    }

    emit(event, payload) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(cb => cb(payload));
        }
    }
}
