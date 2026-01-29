import axios from 'axios';

/**
 * Service infra pour communiquer avec le moteur Python (FastAPI).
 * Respecte la Règle #4 : Node orchestre, Python calcule.
 */
class PythonService {
    constructor() {
        // L'URL pourrait être dans .env, mais on hardcode 127.0.0.1 pour simplifier en local
        this.baseUrl = 'http://127.0.0.1:8000';
    }

    /**
     * Envoie une requête d'optimisation 1D (Bin Packing).
     * @param {object} payload - Structuré selon OptimizationRequest (pydantic)
     * @returns {Promise<object>} - Résultat JSON
     */
    async optimize(payload) {
        try {
            // "Standard I/O" via HTTP interne
            const response = await axios.post(`${this.baseUrl}/optimize`, payload, {
                timeout: 60000 // 60s max pour les calculs complexes
            });
            return response.data;
        } catch (error) {
            // Gestion d'erreur explicite (Règle #7 Clean Code)
            if (error.code === 'ECONNREFUSED') {
                console.error("❌ Moteur Python éteint (Port 8000).");
                throw new Error("Le service de calcul est indisponible. Vérifiez que le backend Python tourne.");
            }
            console.error("⚠️ Erreur Python:", error.message);
            throw new Error(`Erreur calcul: ${error.message}`);
        }
    }

    /**
     * Vérifie si le moteur est en vie.
     */
    async checkHealth() {
        try {
            const res = await axios.get(`${this.baseUrl}/health`, { timeout: 2000 });
            return res.status === 200;
        } catch (e) {
            return false;
        }
    }
}

export const pythonService = new PythonService();
