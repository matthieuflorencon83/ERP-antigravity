/**
 * screenshot_cli.js
 * Wrapper CLI pour prendre des screenshots via Puppeteer
 * Usage: node screenshot_cli.js <URL> <DEVICE>
 */

// Tenter de charger puppeteer depuis le dossier mcp-server adjacent
const puppeteerPath = '../mcp-server/node_modules/puppeteer';
let puppeteer;

try {
    puppeteer = require(puppeteerPath);
} catch (e) {
    try {
        puppeteer = require('puppeteer');
    } catch (e2) {
        console.error(JSON.stringify({ success: false, error: "Puppeteer not found. Install it directly or in ../mcp-server" }));
        process.exit(1);
    }
}

const url = process.argv[2];
const deviceType = process.argv[3] || 'desktop';

if (!url) {
    console.error(JSON.stringify({ success: false, error: "URL required" }));
    process.exit(1);
}

(async () => {
    let browser;
    try {
        browser = await puppeteer.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });

        const page = await browser.newPage();

        // Viewport settings
        if (deviceType === 'mobile') {
            await page.setViewport({ width: 375, height: 667, isMobile: true });
            await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1');
        } else {
            await page.setViewport({ width: 1920, height: 1080 });
        }

        // Navigate
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

        // Screenshot to Base64
        const screenshotBuffer = await page.screenshot({ encoding: 'base64', fullPage: false });

        console.log(JSON.stringify({
            success: true,
            image_base64: screenshotBuffer
        }));

    } catch (error) {
        console.error(JSON.stringify({ success: false, error: error.message }));
    } finally {
        if (browser) await browser.close();
    }
})();
