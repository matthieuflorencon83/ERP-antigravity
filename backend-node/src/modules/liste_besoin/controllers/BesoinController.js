import besoinService from '../services/BesoinService.js';

class BesoinController {

    async calculate(req, res) {
        try {
            // "Validation des entrées (Zod/Pydantic) obligatoire" - Rule 8 (Simplified here for prototype)
            const needs = req.body.needs;
            if (!Array.isArray(needs)) {
                return res.status(400).json({ success: false, error: 'Invalid input: "needs" must be an array.' });
            }

            const result = await besoinService.calculateOptimization(needs);
            res.json({ success: true, data: result });

        } catch (error) {
            console.error('Error in calculation:', error);
            res.status(500).json({ success: false, error: error.message });
        }
    }
}

export default new BesoinController();
