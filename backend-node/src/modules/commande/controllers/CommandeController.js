import commandeService from '../services/CommandeService.js';
import { z } from 'zod';

// Zod Schemas
const createCommandeSchema = z.object({
    num_oa: z.string().min(1, "Num OA Requis"),
    code_fou: z.string().min(1, "Code Fournisseur Requis"),
    num_cde_vente: z.string().optional(),
    designation: z.string().optional(),
    Statut: z.enum(['BROUILLON', 'ENVOYEE', 'CONFIRMEE', 'LIVREE']).default('BROUILLON'),
    montant_ht: z.number().optional()
});

const updateStatusSchema = z.object({
    statut: z.enum(['BROUILLON', 'ENVOYEE', 'CONFIRMEE', 'LIVREE']),
    num_cde_fou: z.string().optional(),
    date_conf: z.string().optional(), // Date string expected
    date_liv: z.string().optional()
});

class CommandeController {
    async getAll(req, res) {
        try {
            const list = await commandeService.getAll();
            res.json(list);
        } catch (error) {
            res.status(500).json({ error: error.message });
        }
    }

    async getById(req, res) {
        try {
            const item = await commandeService.getById(req.params.id);
            if (!item) return res.status(404).json({ error: 'Commande non trouvée' });
            res.json(item);
        } catch (error) {
            res.status(500).json({ error: error.message });
        }
    }

    async create(req, res) {
        try {
            // Validation Shift-Left
            const validatedData = createCommandeSchema.parse(req.body);

            const item = await commandeService.create(validatedData);
            res.status(201).json(item);
        } catch (error) {
            if (error instanceof z.ZodError) {
                return res.status(400).json({ error: 'Validation Error', details: error.errors });
            }
            res.status(400).json({ error: error.message });
        }
    }

    async update(req, res) {
        try {
            const item = await commandeService.update(req.params.id, req.body);
            res.json(item);
        } catch (error) {
            res.status(400).json({ error: error.message });
        }
    }

    async updateStatus(req, res) {
        try {
            const { statut, ...extra } = req.body;

            // Validate Logic inputs
            const validated = updateStatusSchema.parse(req.body);

            const item = await commandeService.updateStatus(req.params.id, validated.statut, extra);
            res.json(item);
        } catch (error) {
            if (error instanceof z.ZodError) {
                return res.status(400).json({ error: 'Validation Error', details: error.errors });
            }
            res.status(400).json({ error: error.message });
        }
    }
}

export default new CommandeController();
