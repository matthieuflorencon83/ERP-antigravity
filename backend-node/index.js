import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import bibliothequeRoutes from './src/modules/bibliotheque/routes.js';
import besoinRoutes from './src/modules/liste_besoin/routes.js';

import affaireRoutes from './src/modules/affaire/routes.js';
import commandeRoutes from './src/modules/commande/routes.js'; // Import
import db from './src/config/knex.js';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// Routes
import calculRoutes from './src/modules/calcul/calcul.routes.js';

// ...
app.use('/api/bibliotheque', bibliothequeRoutes);
app.use('/api/besoins', besoinRoutes);
// app.use('/api/optimize', optimizationRoutes); // DEPRECATED V1
app.use('/api/calcul', calculRoutes); // NEW MODULE V2
app.use('/api/affaires', affaireRoutes);
app.use('/api/commandes', commandeRoutes);


// Health Check
app.get('/health', async (req, res) => {
    try {
        await db.raw('SELECT 1');
        res.json({ status: 'OK', db: 'Connected' });
    } catch (err) {
        res.status(500).json({ status: 'ERROR', db: err.message });
    }
});

// Start Server
if (process.env.NODE_ENV !== 'test') {
    app.listen(PORT, () => {
        console.log(`✅ Server running on port ${PORT}`);
        console.log(`👉 Docs: http://localhost:${PORT}/api/bibliotheque/articles`);
    });
}

export default app;
