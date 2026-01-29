import { pythonService } from './PythonService.js';
// Import d'autres services si besoin (Affaire, Article...) pour récupérer les données
// import { affaireService } from '../../affaire/services/AffaireService.js';

/**
 * Service métier qui orchestre le calcul.
 * Prépare les données -> Appelle Python -> Sauvegarde le résultat.
 */
class CalculService {

    /**
     * Lance une optimisation pour une liste de découpes et un stock donné.
     * @param {object} data - Données validées par le Controller (Zod)
     */
    async lancerOptimisation(data) {
        // 1. Préparation (si besoin de transformer les données DB en format Python)
        // Ici on suppose que le Controller a déjà validé/formatté le payload ou qu'on le reçoit brut
        // Pour la V1, on passe le payload directement.

        const payload = {
            stock_options: data.stock_options || [],
            cuts_mm: data.cuts_mm || [],
            saw_kerf: data.saw_kerf || 4,
            scrap_end: data.scrap_end || 0
        };

        // 2. Appel du Cerveau (Python)
        const result = await pythonService.optimize(payload);

        // 3. Sauvegarde (Optionnel : stocker le résultat en BDD)
        // await db('resultat_calcul').insert({ ... })

        return result;
    }
}

export const calculService = new CalculService();
