import mysql from 'mysql2/promise';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function diagnose() {
    const schemaPath = path.join(__dirname, '../../database/schema.sql');
    const schema = fs.readFileSync(schemaPath, 'utf8');

    // More robust splitting (naive but usually sufficient for this structure)
    // We split by ';' but ensure we don't pick up empty lines
    const commands = schema
        .split(';')
        .map(cmd => cmd.trim())
        .filter(cmd => cmd.length > 0);

    console.log(`Diagnostic: Found ${commands.length} commands.`);

    const connection = await mysql.createConnection({
        host: process.env.DB_HOST,
        user: process.env.DB_USER,
        password: process.env.DB_PASSWORD,
        port: process.env.DB_PORT || 3306,
        multipleStatements: true
    });

    try {
        for (const [index, command] of commands.entries()) {
            // Skip empty or comment-only commands
            if (command.startsWith('--') && !command.includes('\n')) continue;

            console.log(`[${index}] Executing: ${command.substring(0, 40).replace(/\n/g, ' ')}...`);
            try {
                await connection.query(command);
            } catch (err) {
                console.error(`\n❌ ERROR at Command [${index}]:`);
                console.error(`Query: ${command}`);
                console.error(`Error: ${err.message}`);
                process.exit(1);
            }
        }
        console.log('✅ Success: All commands executed.');
    } finally {
        await connection.end();
    }
}

diagnose();
