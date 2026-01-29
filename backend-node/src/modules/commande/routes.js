import express from 'express';
import commandeController from './controllers/CommandeController.js';

const router = express.Router();

router.get('/', (req, res) => commandeController.getAll(req, res));
router.get('/:id', (req, res) => commandeController.getById(req, res));
router.post('/', (req, res) => commandeController.create(req, res));
router.put('/:id', (req, res) => commandeController.update(req, res));
// Special route for status transition
router.put('/:id/status', (req, res) => commandeController.updateStatus(req, res));

export default router;
