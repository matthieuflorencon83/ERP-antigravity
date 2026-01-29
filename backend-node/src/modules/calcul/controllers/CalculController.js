import { calculService } from '../services/CalculService.js';
import { z } from 'zod';

// Schéma de validation STRICT (Shift-Left Security)
const optimizationSchema = z.object({
    stock_options: z.array(z.object({
        ref: z.string(),
        len_mm: z.number().int().positive()
    })).min(1),
    cuts_mm: z.array(z.number().int().positive()).min(1),
    saw_kerf: z.number().int().nonnegative().default(4),
    scrap_end: z.number().int().nonnegative().default(0)
});

class CalculController {

    /**
     * POST /optimize
     * Endpoint public pour lancer un calcul.
     */
    async optimize(req, res) {
        try {
            // 1. Validation Shift-Left
            const validatedData = optimizationSchema.parse(req.body);

            // 2. Appel Service
            const result = await calculService.lancerOptimisation(validatedData);

            // 3. Réponse 200 OK
            res.json(result);
        } catch (error) {
            if (error instanceof z.ZodError) {
                return res.status(400).json({ error: "Données invalides", details: error.errors });
            }
            res.status(500).json({ error: error.message });
        }
    }
}

export const calculController = new CalculController();
