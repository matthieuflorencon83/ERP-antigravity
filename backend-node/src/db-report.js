import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

async function generateReport() {
    const connection = await mysql.createConnection({
        host: process.env.DB_HOST,
        user: process.env.DB_USER,
        password: process.env.DB_PASSWORD,
        database: process.env.DB_NAME || 'erp_arts_alu',
        port: process.env.DB_PORT || 3306
    });

    try {
        console.log(`\n# RAPPORT BASE DE DONNÉES: ${process.env.DB_NAME || 'erp_arts_alu'}`);
        console.log(`Generated: ${new Date().toISOString()}\n`);

        const [tables] = await connection.query('SHOW TABLES');
        const tableKey = `Tables_in_${process.env.DB_NAME || 'erp_arts_alu'}`;

        if (tables.length === 0) {
            console.log("⚠️ Aucune table trouvée.");
        } else {
            console.log(`## Tables (${tables.length} trouvées)\n`);
            for (const table of tables) {
                const tableName = table[tableKey];
                const [rows] = await connection.query(`SELECT COUNT(*) as count FROM \`${tableName}\``);
                const [columns] = await connection.query(`SHOW COLUMNS FROM \`${tableName}\``);

                console.log(`### 📦 Table: ${tableName}`);
                console.log(`- **Lignes** : ${rows[0].count}`);
                console.log(`- **Colonnes** : ${columns.length}`);
                console.log(`- **Structure** : ${columns.map(c => c.Field).join(', ')}`);
                console.log('');
            }
        }
    } catch (err) {
        console.error('❌ Erreur:', err.message);
    } finally {
        await connection.end();
    }
}

generateReport();
