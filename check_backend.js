import http from 'http';

const req = http.get('http://localhost:3000/health', (res) => {
    console.log(`STATUS: ${res.statusCode}`);
    res.on('data', (chunk) => {
        console.log(`BODY: ${chunk}`);
    });
});

req.on('error', (e) => {
    console.error(`PROBLEM: ${e.message}`);
    process.exit(1);
});
