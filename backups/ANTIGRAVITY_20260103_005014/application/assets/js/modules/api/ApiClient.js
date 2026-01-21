/**
 * ApiClient.js - Client API Unifié V4.0
 * 
 * Responsabilité : Communication avec le backend
 * Sécurité : Retry logic, timeout, error handling
 * 
 * @version 4.0.0
 */

export class ApiClient {

    constructor(baseUrl = '/antigravity/api/api_metrage_v4.php') {
        this.baseUrl = baseUrl;
        this.timeout = 10000; // 10 secondes
        this.maxRetries = 3;
    }

    // =====================================================
    // MÉTHODES PRINCIPALES
    // =====================================================

    /**
     * Créer une intervention
     * 
     * @param {number|null} affaireId
     * @returns {Promise<Object>}
     */
    async createIntervention(affaireId = null) {
        const formData = new FormData();
        formData.append('action', 'create');
        if (affaireId !== null) {
            formData.append('affaire_id', affaireId);
        }

        return this._post(formData);
    }

    /**
     * Lier à une affaire
     * 
     * @param {number} interventionId
     * @param {number} affaireId
     * @returns {Promise<Object>}
     */
    async linkToAffaire(interventionId, affaireId) {
        const formData = new FormData();
        formData.append('action', 'link');
        formData.append('intervention_id', interventionId);
        formData.append('affaire_id', affaireId);

        return this._post(formData);
    }

    /**
     * Ajouter une ligne
     * 
     * @param {number} interventionId
     * @param {number} typeId
     * @param {string} localisation
     * @param {Object} donneesJson
     * @returns {Promise<Object>}
     */
    async addLigne(interventionId, typeId, localisation, donneesJson) {
        const formData = new FormData();
        formData.append('action', 'add_ligne');
        formData.append('intervention_id', interventionId);
        formData.append('type_id', typeId);
        formData.append('localisation', localisation);
        formData.append('donnees_json', JSON.stringify(donneesJson));

        return this._post(formData);
    }

    /**
     * Mettre à jour une ligne
     * 
     * @param {number} ligneId
     * @param {Object} donneesJson
     * @returns {Promise<Object>}
     */
    async updateLigne(ligneId, donneesJson) {
        const formData = new FormData();
        formData.append('action', 'update_ligne');
        formData.append('ligne_id', ligneId);
        formData.append('donnees_json', JSON.stringify(donneesJson));

        return this._post(formData);
    }

    /**
     * Supprimer une ligne
     * 
     * @param {number} ligneId
     * @returns {Promise<Object>}
     */
    async deleteLigne(ligneId) {
        const formData = new FormData();
        formData.append('action', 'delete_ligne');
        formData.append('ligne_id', ligneId);

        return this._post(formData);
    }

    /**
     * Récupérer une intervention
     * 
     * @param {number} id
     * @returns {Promise<Object>}
     */
    async getIntervention(id) {
        return this._get({ action: 'get_intervention', id });
    }

    /**
     * Récupérer les lignes
     * 
     * @param {number} interventionId
     * @returns {Promise<Array>}
     */
    async getLignes(interventionId) {
        const result = await this._get({ action: 'get_lignes', intervention_id: interventionId });
        return result.data || [];
    }

    /**
     * Récupérer les types
     * 
     * @returns {Promise<Array>}
     */
    async getTypes() {
        const result = await this._get({ action: 'get_types' });
        return result.data || [];
    }

    // =====================================================
    // REQUÊTES HTTP (AVEC RETRY)
    // =====================================================

    /**
     * POST avec retry
     * 
     * @private
     */
    async _post(formData, retryCount = 0) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), this.timeout);

            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Unknown error');
            }

            return data;

        } catch (error) {
            // Retry si erreur réseau et pas dépassé max retries
            if (retryCount < this.maxRetries && this._isNetworkError(error)) {
                console.warn(`Retry ${retryCount + 1}/${this.maxRetries}...`);
                await this._delay(1000 * (retryCount + 1)); // Backoff exponentiel
                return this._post(formData, retryCount + 1);
            }

            throw error;
        }
    }

    /**
     * GET avec retry
     * 
     * @private
     */
    async _get(params, retryCount = 0) {
        try {
            const url = new URL(this.baseUrl, window.location.origin);
            Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), this.timeout);

            const response = await fetch(url, {
                method: 'GET',
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Unknown error');
            }

            return data;

        } catch (error) {
            if (retryCount < this.maxRetries && this._isNetworkError(error)) {
                console.warn(`Retry ${retryCount + 1}/${this.maxRetries}...`);
                await this._delay(1000 * (retryCount + 1));
                return this._get(params, retryCount + 1);
            }

            throw error;
        }
    }

    /**
     * Vérifier si erreur réseau (retry possible)
     * 
     * @private
     */
    _isNetworkError(error) {
        return error.name === 'AbortError' ||
            error.message.includes('Failed to fetch') ||
            error.message.includes('NetworkError');
    }

    /**
     * Délai (pour retry)
     * 
     * @private
     */
    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
