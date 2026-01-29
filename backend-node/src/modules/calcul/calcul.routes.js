import express from 'express';
import { calculController } from './controllers/CalculController.js';

const router = express.Router();

// Routes /api/calcul
router.post('/optimize', (req, res) => calculController.optimize(req, res));

export default router;
