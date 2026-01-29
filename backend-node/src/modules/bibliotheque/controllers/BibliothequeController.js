import articleService from '../services/ArticleService.js';
import tiersService from '../services/TiersService.js';

class BibliothequeController {

    // --- Articles ---

    async getArticles(req, res) {
        try {
            const articles = await articleService.getAllArticles();
            res.json({ success: true, data: articles });
        } catch (error) {
            console.error('Error fetching articles:', error);
            res.status(500).json({ success: false, error: 'Internal Server Error' });
        }
    }

    async getArticleDetail(req, res) {
        try {
            const { code } = req.params;
            const article = await articleService.getArticle(code);
            if (!article) {
                return res.status(404).json({ success: false, error: 'Article not found' });
            }
            res.json({ success: true, data: article });
        } catch (error) {
            console.error('Error fetching article:', error);
            res.status(500).json({ success: false, error: 'Internal Server Error' });
        }
    }

    async createArticle(req, res) {
        try {
            const article = await articleService.createArticle(req.body);
            res.json({ success: true, data: article });
        } catch (error) {
            console.error('Error creating article:', error);
            res.status(500).json({ success: false, error: error.message });
        }
    }

    // --- Tiers ---

    async getClients(req, res) {
        try {
            const clients = await tiersService.getAllClientsFull();
            res.json({ success: true, data: clients });
        } catch (error) {
            console.error('Error fetching clients:', error);
            res.status(500).json({ success: false, error: 'Internal Server Error' });
        }
    }

    async createClient(req, res) {
        try {
            const client = await tiersService.createClient(req.body);
            res.json({ success: true, data: client });
        } catch (error) {
            console.error('Error creating client:', error);
            res.status(500).json({ success: false, error: error.message });
        }
    }

    async getFournisseurs(req, res) {
        try {
            const fournisseurs = await tiersService.getAllFournisseurs();
            res.json({ success: true, data: fournisseurs });
        } catch (error) {
            console.error('Error fetching fournisseurs:', error);
            res.status(500).json({ success: false, error: 'Internal Server Error' });
        }
    }

    async createFournisseur(req, res) {
        try {
            const fournisseur = await tiersService.createFournisseur(req.body);
            res.json({ success: true, data: fournisseur });
        } catch (error) {
            console.error('Error creating fournisseur:', error);
            res.status(500).json({ success: false, error: error.message });
        }
    }
}

export default new BibliothequeController();
