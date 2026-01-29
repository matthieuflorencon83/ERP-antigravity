import knex from 'knex';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

// Load env vars if not already loaded
dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const config = {
    client: 'mysql2',
    connection: {
        host: process.env.DB_HOST,
        user: process.env.DB_USER,
        password: process.env.DB_PASSWORD,
        database: process.env.DB_NAME || 'erp_arts_alu',
        port: Number(process.env.DB_PORT) || 3306,
        dateStrings: true // Important for handling dates as strings in JSON/API
    },
    pool: {
        min: 2,
        max: 10
    }
};

const db = knex(config);

// "Virtual MCP" Check: Verify connection on startup
db.raw('SELECT 1')
    .then(() => console.log('✅ [Knex] Connected to Database'))
    .catch((err) => {
        console.error('❌ [Knex] Connection Failed:', err.message);
        process.exit(1);
    });

export default db;
