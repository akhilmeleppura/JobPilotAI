import { test, expect } from '@playwright/test';

test.describe('Automatic Free Email Provider Switching E2E', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'candidate@jobpilot.test');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
  });

  test('dashboard displays automatic email provider switching monitor, scorecard, and controls', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // 1. Verify absence of hardcoded 200 daily limit
    const pageText = await page.locator('body').innerText();
    expect(pageText).not.toContain('Application ceiling: 200/day');
    expect(pageText).not.toContain('14 / 200 daily cap');

    // 2. Verify Automatic Email Switching Monitor is visible
    const switchingMonitor = page.locator('#emailSwitchingMonitor');
    await expect(switchingMonitor).toBeVisible();
    await expect(switchingMonitor).toContainText('AUTOMATIC EMAIL PROVIDER SWITCHING MONITOR');

    // 3. Verify Dynamic Notification Banner
    await expect(switchingMonitor.locator('.alert')).toBeVisible();

    // 4. Verify 3 Hero Cards: Active Provider, Primary Provider, Selected Alternative
    await expect(switchingMonitor).toContainText('Current Active Dispatcher');
    await expect(switchingMonitor).toContainText('Configured Primary Provider');
    await expect(switchingMonitor).toContainText('Selected Alternative (Failover Target)');

    // 5. Verify Provider Health & Priority Scorecard Table
    const scorecardTable = switchingMonitor.locator('table').first();
    await expect(scorecardTable).toBeVisible();
    await expect(scorecardTable).toContainText('Provider & Connected Account');
    await expect(scorecardTable).toContainText('Eligibility Status');
    await expect(scorecardTable).toContainText('Priority');
    await expect(scorecardTable).toContainText('Policy Status');

    // 6. Verify Switching History Section
    await expect(switchingMonitor).toContainText(/Recent Provider Switching History/i);

    // 7. Verify Collapsible Switching Controls
    const controlsToggle = page.locator('button[data-bs-target="#switchingSettingsCollapse"]');
    await expect(controlsToggle).toBeVisible();
    await controlsToggle.click();
    await page.waitForTimeout(400);

    const controlsCollapse = page.locator('#switchingSettingsCollapse');
    await expect(controlsCollapse).toBeVisible();
    await expect(controlsCollapse).toContainText('Provider Selection Mode');
    await expect(controlsCollapse).toContainText('Technical Failover');
    await expect(controlsCollapse).toContainText('Switch on Normal Quota Exhaustion');

    // 8. Capture Full Screenshot of Switching Monitor
    await switchingMonitor.screenshot({ path: '../reports/ui/after/email_switching_monitor.png' });

    // 9. Capture Close-up Scorecard Screenshot
    await scorecardTable.screenshot({ path: '../reports/ui/screenshots/email_failover_scorecard.png' });
  });

  test('email studio displays failover priority guidance and uncapped accounts', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/email-studio');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('body')).toContainText(/AUTOMATIC FAILOVER/i);
    await expect(page.locator('body')).toContainText(/RECIPIENTS SENT TODAY/i);
    await expect(page.locator('body')).not.toContainText('200/day');
  });

});
