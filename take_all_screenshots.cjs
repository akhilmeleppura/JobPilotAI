const { chromium } = require('./laravel/node_modules/playwright');
const path = require('path');
const fs = require('fs');

async function run() {
    console.log('--- Launching Chrome for Full Browser Walkthrough ---');
    const screenshotsDir = path.join(__dirname, 'reports', 'local', 'screenshots');
    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir, { recursive: true });
    }

    const browser = await chromium.launch({
        channel: 'chrome',
        headless: true
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // 1. Login Page
    console.log('1. Capturing Login Page...');
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '01-login.png'), fullPage: true });

    // Login Action
    console.log('   Logging in with candidate credentials...');
    await page.fill('input[name="email"]', 'candidate@jobpilot.test');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForURL('http://127.0.0.1:8000/');
    await page.waitForLoadState('networkidle');

    // 2. Dashboard Cockpit
    console.log('2. Capturing Dashboard Cockpit...');
    await page.screenshot({ path: path.join(screenshotsDir, '02-dashboard-cockpit.png'), fullPage: true });

    // 3. Role Profiles
    console.log('3. Capturing Role Profiles...');
    await page.goto('http://127.0.0.1:8000/role-profiles', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '03-role-profiles.png'), fullPage: true });

    // 4. CV Assignment Matrix
    console.log('4. Capturing CV Assignment Matrix...');
    await page.goto('http://127.0.0.1:8000/cv-studio/assignment', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '04-cv-assignment.png'), fullPage: true });

    // 5. LaTeX Editor & ATS Analyzer
    console.log('5. Capturing LaTeX Editor & ATS Analyzer...');
    await page.goto('http://127.0.0.1:8000/cv-studio/editor/1', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '05-cv-latex-editor.png'), fullPage: true });

    // 6. Job Discovery
    console.log('6. Capturing Job Discovery...');
    await page.goto('http://127.0.0.1:8000/jobs', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '06-jobs-discovery.png'), fullPage: true });

    // 7. Natural Language AI Job Search
    console.log('7. Capturing AI Job Search...');
    await page.goto('http://127.0.0.1:8000/jobs/ai-search', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '07-jobs-ai-search.png'), fullPage: true });

    // 8. Applications Registry
    console.log('8. Capturing Applications Registry...');
    await page.goto('http://127.0.0.1:8000/applications', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '08-applications-kanban.png'), fullPage: true });

    // 9. Spreadsheet Import
    console.log('9. Capturing Spreadsheet Import...');
    await page.goto('http://127.0.0.1:8000/import', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '09-spreadsheet-import.png'), fullPage: true });

    // 10. Email Studio
    console.log('10. Capturing Email Studio...');
    await page.goto('http://127.0.0.1:8000/email-studio', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '10-email-studio.png'), fullPage: true });

    // 11. Email Templates
    console.log('11. Capturing Email Templates...');
    await page.goto('http://127.0.0.1:8000/email-studio/templates', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '11-email-templates.png'), fullPage: true });

    // 12. AI Career Copilot Chat
    console.log('12. Capturing AI Career Copilot Chat...');
    await page.goto('http://127.0.0.1:8000/ai-chat', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '12-ai-chat-copilot.png'), fullPage: true });

    // 13. LinkedIn Optimizer
    console.log('13. Capturing LinkedIn Optimizer...');
    await page.goto('http://127.0.0.1:8000/linkedin-optimizer', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '13-linkedin-optimizer.png'), fullPage: true });

    // 14. Content Studio
    console.log('14. Capturing Content Studio...');
    await page.goto('http://127.0.0.1:8000/content-studio', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '14-content-studio.png'), fullPage: true });

    // 15. LinkedIn Publishing
    console.log('15. Capturing LinkedIn Publishing...');
    await page.goto('http://127.0.0.1:8000/linkedin/publishing', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '15-linkedin-publishing.png'), fullPage: true });

    // 16. Archives & Drive
    console.log('16. Capturing Archives & Drive...');
    await page.goto('http://127.0.0.1:8000/archives', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '16-archives-drive.png'), fullPage: true });

    // 17. Settings & Credential Vault
    console.log('17. Capturing Settings & Vault...');
    await page.goto('http://127.0.0.1:8000/settings', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '17-settings-vault.png'), fullPage: true });

    // 18. System Health Scorecard
    console.log('18. Capturing System Health...');
    await page.goto('http://127.0.0.1:8000/system-health', { waitUntil: 'networkidle' });
    await page.screenshot({ path: path.join(screenshotsDir, '18-system-health.png'), fullPage: true });

    await browser.close();
    console.log('\n--- SUCCESS: All 18 Full-Page Browser Screenshots Captured! ---');
}

run().catch(err => {
    console.error('Error during browser walkthrough:', err);
    process.exit(1);
});

