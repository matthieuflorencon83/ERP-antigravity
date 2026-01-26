import mysql from 'mysql2/promise';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function initDb() {
    const schemaPath = path.join(__dirname, '../../database/schema.sql');
    const schema = fs.readFileSync(schemaPath, 'utf8');

    const commands = schema
        .split(';')
        .map(cmd => cmd.trim())
        .filter(cmd => cmd.length > 0);

    console.log(`Starting database initialization (${commands.length} commands)...`);

    // Connect without specifying a database first
    const connection = await mysql.createConnection({
        host: process.env.DB_HOST,
        user: process.env.DB_USER,
        password: process.env.DB_PASSWORD,
        port: process.env.DB_PORT || 3306,
        multipleStatements: true
    });

    try {
        for (const command of commands) {
            console.log(`Executing: ${command.substring(0, 50)}...`);
            await connection.query(command);
        }
        console.log('✅ Database initialization complete!');
    } catch (error) {
        console.error('❌ Error during initialization:', error.message);
        process.exit(1);
    } finally {
        await connection.end();
        process.exit(0);
    }
}

initDb();
