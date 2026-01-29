require('dotenv').config();
const knex = require('knex')({
    client: 'mysql2',
    connection: {
        host: process.env.DB_HOST || '127.0.0.1',
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_NAME || 'erp_arts_alu'
    }
});

async function check() {
    try {
        const arts = await knex('article').count('code_art as count').first();
        const prices = await knex('article_fournisseur').count('code_art as count').first();
        const suppliers = await knex('fournisseur').count('code_fou as count').first();

        console.log(`\n📊 STATS BASE DE DONNEES :`);
        console.log(` - Articles : ${arts.count}`);
        console.log(` - Prix Fournisseur : ${prices.count}`);
        console.log(` - Fournisseurs : ${suppliers.count}`);

    } catch (e) {
        console.error(e);
    } finally {
        knex.destroy();
    }
}

check();
