/**
 * main.js - Point d'Entrée Principal V4.0
 * 
 * Responsabilité : Initialisation et orchestration
 * Architecture : Composition des modules
 * 
 * @version 4.0.0
 */

import { WizardState } from './metrage/WizardState.js';
import { WizardUI } from './metrage/WizardUI.js';
import { OfflineManager } from './core/OfflineManager.js';
import { ApiClient } from './api/ApiClient.js';

class MetrageStudioApp {

    constructor() {
        this.state = null;
        this.offline = null;
        this.api = null;
        this.ui = null;
    }

    /**
     * Initialisation de l'application
     */
    async init() {
        try {
            console.log('🚀 Metrage Wizard V4.0 - Launching...');

            // 1. Vérifier DOM ready
            if (document.readyState === 'loading') {
                await new Promise(resolve => {
                    document.addEventListener('DOMContentLoaded', resolve);
                });
            }

            // 2. Initialiser modules core
            this.api = new ApiClient();
            this.state = new WizardState();

            // 3. Charger données depuis PHP globals
            const config = {
                metrageId: window.METRAGE_ID || null,
                intervention: window.INTERVENTION || null,
                lignes: window.LIGNES || [],
                types: window.TYPES || [],
                affaires: window.AFFAIRES || []
            };

            this.state.init(config);

            // 4. Initialiser OfflineManager
            if (config.metrageId) {
                this.offline = new OfflineManager(config.metrageId);
                this.offline.installHooks(() => this.state.saveToLocalStorage()); // Adapter si besoin

                // Restauration Crash-Proof
                if (this.offline.hasDraft()) {
                    // La logique spécifique est dans WizardState.restoreFromCrash normalement, 
                    // mais OfflineManager est générique. On peut laisser WizardState gérer son propre draft ou utiliser OfflineManager.
                    // Pour l'instant, faisons simple :
                    this.state.restoreFromCrash();
                }
            }

            // 5. Charger UI
            this.ui = new WizardUI(this.state, this.api);
            this.ui.init();

            console.log('✅ Wizard V4.0 - Ready to Rock');

        } catch (error) {
            console.error('❌ Initialization failed:', error);
            alert(`Erreur critique d'initialisation:\n\n${error.message}`);
        }
    }


}

// =====================================================
// DÉMARRAGE APPLICATION
// =====================================================

const app = new MetrageStudioApp();
// Exposer globalement IMMÉDIATEMENT pour que les modules puissent s'y accrocher
window.MetrageApp = app;

app.init();
