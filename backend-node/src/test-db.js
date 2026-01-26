import pool from './config/db.config.js';

async function testConnection() {
    try {
        const [rows] = await pool.query('SELECT 1 + 1 AS result');
        console.log('Successfully connected to the database. Result:', rows[0].result);
        process.exit(0);
    } catch (error) {
        console.error('Error connecting to the database:', error.message);
        process.exit(1);
    }
}

testConnection();
