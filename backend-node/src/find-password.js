import mysql from 'mysql2/promise';

async function tryPasswords() {
    const commonPasswords = [
        '', 'root', 'admin', 'password', 'mysql',
        'loan221213', 'Loan221213', 'LOAN221213',
        'loan2212', 'Loan2212', 'loan2026', 'Loan2024',
        'artsalu', 'ArtsAlu', 'ArtsAlu2026', 'ArtsAlu2024',
        'mattdev', 'MattDev', 'Matt2026', 'Matt2024',
        'mattsecret', 'admin123', 'root123', '12345678',
        'loan', 'Loan', 'antigravity', 'Antigravity'
    ];
    for (const password of commonPasswords) {
        console.log(`Trying password: "${password}"`);
        try {
            const connection = await mysql.createConnection({
                host: 'localhost',
                user: 'root',
                password: password
            });
            console.log(`SUCCESS! The password is: "${password}"`);
            await connection.end();
            process.exit(0);
        } catch (error) {
            console.log(`Failed for "${password}": ${error.message}`);
        }
    }
    console.log('No common passwords worked.');
    process.exit(1);
}

tryPasswords();
