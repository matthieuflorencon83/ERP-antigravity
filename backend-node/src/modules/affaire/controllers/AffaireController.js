import affaireService from '../services/AffaireService.js';

class AffaireController {
    async getAll(req, res) {
        try {
            const affaires = await affaireService.getAll();
            res.json(affaires);
        } catch (error) {
            res.status(500).json({ error: error.message });
        }
    }

    async getById(req, res) {
        try {
            const affaire = await affaireService.getById(req.params.id);
            if (!affaire) return res.status(404).json({ error: 'Affaire non trouvée' });
            res.json(affaire);
        } catch (error) {
            res.status(500).json({ error: error.message });
        }
    }

    async create(req, res) {
        try {
            const affaire = await affaireService.create(req.body);
            res.status(201).json(affaire);
        } catch (error) {
            res.status(400).json({ error: error.message });
        }
    }

    async update(req, res) {
        try {
            const affaire = await affaireService.update(req.params.id, req.body);
            res.json(affaire);
        } catch (error) {
            res.status(400).json({ error: error.message });
        }
    }
}

export default new AffaireController();
