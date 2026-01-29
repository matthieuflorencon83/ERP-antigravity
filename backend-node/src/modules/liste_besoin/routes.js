import express from 'express';
import besoinController from './controllers/BesoinController.js';

const router = express.Router();

router.post('/calculate', (req, res) => besoinController.calculate(req, res));

export default router;
