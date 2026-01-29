import express from 'express';
import affaireController from './controllers/AffaireController.js';

const router = express.Router();

router.get('/', (req, res) => affaireController.getAll(req, res));
router.get('/:id', (req, res) => affaireController.getById(req, res));
router.post('/', (req, res) => affaireController.create(req, res));
router.put('/:id', (req, res) => affaireController.update(req, res));

export default router;
