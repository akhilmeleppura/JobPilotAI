import { test, expect } from '@playwright/test';

test.describe('Dynamic Free Email Allowance Dashboard E2E', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'candidate@jobpilot.test');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
  });

  test('dashboard displays dynamic email allowance summary and separate provider cards without fixed 200 cap', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // 1. Verify absence of hardcoded 200 daily ceiling
    const pageText = await page.locator('body').innerText();
    expect(pageText).not.toContain('Application ceiling: 200/day');
    expect(pageText).not.toContain('14 / 200 daily cap');
    expect(pageText).not.toContain('Remaining today: 186');

    // 2. Verify Summary Section (Section 10)
    const summary = page.locator('#emailAllowanceSummary');
    await expect(summary).toBeVisible();
    await expect(summary).toContainText('FREE EMAIL PROVIDER ALLOWANCE SUMMARY');
    await expect(summary).toContainText('Connected Providers');
    await expect(summary).toContainText('Free Providers Eligible');
    await expect(summary).toContainText('Recipients Sent Today');
    await expect(summary).toContainText('Recipients This Month');
    await expect(summary).toContainText('Pending Emails');
    await expect(summary).toContainText('Failed Emails');
    await expect(summary).toContainText('Providers Paused');
    await expect(summary).toContainText('Unknown Quota');

    // 3. Verify Free Email Provider Allowance Monitor Grid (Section 9)
    const monitor = page.locator('#providerAllowanceMonitor');
    await expect(monitor).toBeVisible();
    await expect(monitor).toContainText('FREE EMAIL PROVIDER ALLOWANCE MONITOR');

    // Provider Card: Gmail API
    await expect(monitor).toContainText('Gmail API');
    await expect(monitor).toContainText('LOCALLY_MEASURED');
    await expect(monitor).toContainText('Exact remaining quota unavailable');

    // Provider Card: Google Apps Script
    await expect(monitor).toContainText('Google Apps Script');
    await expect(monitor).toContainText('PROVIDER_REPORTED');
    await expect(monitor).toContainText('available');

    // Shared Google pool warning
    await expect(monitor).toContainText('Shares underlying Gmail recipient allowance');

    // Provider Card: Outlook Graph
    await expect(monitor).toContainText('Outlook Graph');

    // Provider Card: Policy Prohibited Platform (Mailjet)
    await expect(monitor).toContainText('Mailjet');
    await expect(monitor).toContainText('PROHIBITED');

    // 4. Capture full dashboard screenshot with dynamic email allowance monitor
    await page.screenshot({ path: '../reports/ui/after/dashboard_dynamic_email_allowance.png', fullPage: true });

    // 5. Capture close-up screenshot of provider allowance monitor
    await monitor.screenshot({ path: '../reports/ui/screenshots/email_provider_cards.png' });
  });

  test('email studio view displays dynamic metrics and uncapped accounts', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/email-studio');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('body')).toContainText(/RECIPIENTS SENT TODAY/i);
    await expect(page.locator('body')).toContainText(/RECIPIENTS SENT THIS MONTH/i);
    await expect(page.locator('body')).toContainText(/ELIGIBLE FREE PROVIDERS/i);
    await expect(page.locator('body')).not.toContainText('default 200/day');
  });

});

