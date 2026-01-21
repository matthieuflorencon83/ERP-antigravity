/**
 * OfflineManager.js - Persistance Locale Crash-Proof V4.0
 * 
 * Responsabilité : Sauvegarde automatique localStorage
 * Constitution v3.0 : "L'utilisateur ne doit jamais perdre ses données"
 * 
 * @version 4.0.0
 */

export class OfflineManager {

    constructor(metrageId) {
        this.metrageId = metrageId;
        this.storageKey = `antigravity_metrage_${metrageId}`;
        this.saveTimeout = null;
        this.debounceDelay = 2000; // 2 secondes (Constitution v3.0)
        this.maxDrafts = 5; // Garder max 5 brouillons

        // APEX: Race Condition Protection
        this.saveInProgress = false;
        this.pendingSave = null;
    }

    // =====================================================
    // SAUVEGARDE (DEBOUNCED)
    // =====================================================

    /**
     * Sauvegarder l'état (avec debounce)
     * 
     * @param {Object} state - État complet depuis StudioState
     */
    save(state) {
        // APEX: Si sauvegarde en cours, mettre en attente
        if (this.saveInProgress) {
            this.pendingSave = state;
            return;
        }

        // Annuler sauvegarde précédente si en attente
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        // Programmer nouvelle sauvegarde dans 2s
        this.saveTimeout = setTimeout(() => {
            this._performSave(state);
        }, this.debounceDelay);
    }

    /**
     * Sauvegarde immédiate (sans debounce)
     * Utilisé lors de la fermeture de page
     * 
     * @param {Object} state
     */
    saveNow(state) {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        this._performSave(state);
    }

    /**
     * Exécution réelle de la sauvegarde
     * 
     * @private
     */
    _performSave(state) {
        // APEX: Lock pour éviter race condition
        this.saveInProgress = true;

        try {
            // Utiliser pendingSave si disponible (plus récent)
            const toSave = this.pendingSave || state;
            this.pendingSave = null;

            const payload = {
                metrageId: this.metrageId,
                timestamp: Date.now(),
                state: toSave
            };

            const json = JSON.stringify(payload);
            localStorage.setItem(this.storageKey, json);

            console.log(`✓ Draft saved (${this._formatBytes(json.length)})`);
            this._updateStatusUI('Sauvegardé', 'success');

            // Nettoyer anciens brouillons
            this._cleanOldDrafts();

        } catch (e) {
            // Quota dépassé
            if (e.name === 'QuotaExceededError') {
                console.error('❌ LocalStorage quota exceeded');
                this._updateStatusUI('Quota dépassé', 'error');
                this._cleanOldDrafts(true); // Force cleanup
            } else {
                console.error('❌ Save error:', e);
                this._updateStatusUI('Erreur sauvegarde', 'error');
            }
        } finally {
            // APEX: Unlock
            this.saveInProgress = false;
        }
    }

    // =====================================================
    // RESTAURATION
    // =====================================================

    /**
     * Restaurer un brouillon
     * 
     * @returns {Object|null} - État restauré ou null
     */
    restore() {
        try {
            const raw = localStorage.getItem(this.storageKey);
            if (!raw) {
                console.log('No draft found');
                return null;
            }

            const draft = JSON.parse(raw);
            const age = Date.now() - draft.timestamp;
            const ageHours = Math.floor(age / 3600000);

            // Si > 24h, demander confirmation
            if (age > 86400000) {
                const message = `Un brouillon de ${ageHours}h existe.\n\nLe restaurer ?`;
                if (!confirm(message)) {
                    return null;
                }
            }

            console.log(`✓ Draft restored (${ageHours}h old)`);
            this._updateStatusUI('Brouillon restauré', 'info');

            return draft.state;

        } catch (e) {
            console.error('❌ Restore error:', e);
            return null;
        }
    }

    /**
     * Vérifier si un brouillon existe
     * 
     * @returns {boolean}
     */
    hasDraft() {
        return localStorage.getItem(this.storageKey) !== null;
    }

    /**
     * Obtenir l'âge du brouillon
     * 
     * @returns {number|null} - Âge en millisecondes ou null
     */
    getDraftAge() {
        try {
            const raw = localStorage.getItem(this.storageKey);
            if (!raw) return null;

            const draft = JSON.parse(raw);
            return Date.now() - draft.timestamp;

        } catch (e) {
            return null;
        }
    }

    // =====================================================
    // NETTOYAGE
    // =====================================================

    /**
     * Supprimer le brouillon actuel
     */
    clear() {
        try {
            localStorage.removeItem(this.storageKey);
            console.log('✓ Draft cleared');
            this._updateStatusUI('', '');
        } catch (e) {
            console.error('❌ Clear error:', e);
        }
    }

    /**
     * Nettoyer les anciens brouillons (autres métrages)
     * 
     * @param {boolean} force - Si true, nettoie agressivement
     * @private
     */
    _cleanOldDrafts(force = false) {
        try {
            const keys = Object.keys(localStorage);
            const draftKeys = keys.filter(k => k.startsWith('antigravity_metrage_'));

            if (draftKeys.length <= this.maxDrafts && !force) {
                return; // Pas besoin de nettoyer
            }

            // Trier par timestamp (plus ancien en premier)
            const drafts = draftKeys.map(key => {
                try {
                    const data = JSON.parse(localStorage.getItem(key));
                    return { key, timestamp: data.timestamp || 0 };
                } catch {
                    return { key, timestamp: 0 };
                }
            }).sort((a, b) => a.timestamp - b.timestamp);

            // Supprimer les plus anciens
            const toDelete = force
                ? drafts.slice(0, Math.floor(drafts.length / 2)) // Supprimer 50%
                : drafts.slice(0, drafts.length - this.maxDrafts);

            toDelete.forEach(d => {
                if (d.key !== this.storageKey) { // Ne pas supprimer le brouillon actuel
                    localStorage.removeItem(d.key);
                    console.log(`Cleaned old draft: ${d.key}`);
                }
            });

        } catch (e) {
            console.error('❌ Cleanup error:', e);
        }
    }

    // =====================================================
    // UI FEEDBACK
    // =====================================================

    /**
     * Mettre à jour l'indicateur UI
     * 
     * @param {string} message
     * @param {string} type - success|error|info|warning
     * @private
     */
    _updateStatusUI(message, type) {
        let indicator = document.getElementById('offline-status');

        // Créer si inexistant
        if (!indicator && message) {
            indicator = document.createElement('div');
            indicator.id = 'offline-status';
            indicator.className = 'offline-status';
            document.body.appendChild(indicator);
        }

        if (!indicator) return;

        if (!message) {
            indicator.style.display = 'none';
            return;
        }

        // Icônes selon type
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-triangle',
            info: 'fa-info-circle',
            warning: 'fa-exclamation-circle'
        };

        indicator.style.display = 'flex';
        indicator.className = `offline-status offline-status-${type}`;
        indicator.innerHTML = `
            <i class="fas ${icons[type] || 'fa-circle'}"></i>
            <span>${message}</span>
        `;

        // Auto-hide après 3s si success
        if (type === 'success') {
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }
    }

    // =====================================================
    // HOOKS NAVIGATEUR
    // =====================================================

    /**
     * Installer les hooks de sauvegarde automatique
     * 
     * @param {Function} getStateCallback - Fonction pour récupérer l'état
     */
    installHooks(getStateCallback) {
        // Sauvegarde avant fermeture de page
        window.addEventListener('beforeunload', (e) => {
            const state = getStateCallback();
            if (state && state.products && state.products.length > 0) {
                this.saveNow(state);
            }
        });

        // Sauvegarde lors de la perte de focus (mobile)
        window.addEventListener('blur', () => {
            const state = getStateCallback();
            if (state) {
                this.saveNow(state);
            }
        });

        // Sauvegarde lors du passage offline
        window.addEventListener('offline', () => {
            const state = getStateCallback();
            if (state) {
                this.saveNow(state);
                this._updateStatusUI('Mode hors ligne', 'warning');
            }
        });

        // Notification retour online
        window.addEventListener('online', () => {
            this._updateStatusUI('Connexion rétablie', 'success');
        });
    }

    // =====================================================
    // UTILITAIRES
    // =====================================================

    /**
     * Formater taille en bytes
     * 
     * @param {number} bytes
     * @returns {string}
     * @private
     */
    _formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    /**
     * Obtenir info quota localStorage
     * 
     * @returns {Promise<Object>}
     */
    async getQuotaInfo() {
        if (!navigator.storage || !navigator.storage.estimate) {
            return null;
        }

        try {
            const estimate = await navigator.storage.estimate();
            return {
                used: estimate.usage,
                total: estimate.quota,
                available: estimate.quota - estimate.usage,
                percentUsed: Math.round((estimate.usage / estimate.quota) * 100)
            };
        } catch (e) {
            console.error('Quota check error:', e);
            return null;
        }
    }
}
