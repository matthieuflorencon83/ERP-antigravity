import express from 'express';
import bibliothequeController from './controllers/BibliothequeController.js';

const router = express.Router();

// Articles
router.get('/articles', (req, res) => bibliothequeController.getArticles(req, res));
router.get('/articles/:code', (req, res) => bibliothequeController.getArticleDetail(req, res));
router.post('/articles', (req, res) => bibliothequeController.createArticle(req, res));

// Tiers
router.get('/tiers/clients', (req, res) => bibliothequeController.getClients(req, res));
router.post('/tiers/clients', (req, res) => bibliothequeController.createClient(req, res));

router.get('/tiers/fournisseurs', (req, res) => bibliothequeController.getFournisseurs(req, res));
router.post('/tiers/fournisseurs', (req, res) => bibliothequeController.createFournisseur(req, res));

export default router;
