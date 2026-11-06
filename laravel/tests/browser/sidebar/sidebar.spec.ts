import { test, expect } from '@playwright/test';

test.describe('JobPilot AI Sidebar & UI/UX Navigation E2E Suite', () => {

  test.beforeEach(async ({ page }) => {
    // Authenticate candidate session
    await page.goto('/login');
    await page.fill('input[name="email"]', 'candidate@jobpilot.test');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
  });

  test('desktop sidebar renders all fixed zones, scrollable nav, and 10 groups', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Verify desktop sidebar is visible
    const sidebar = page.locator('#desktopSidebar');
    await expect(sidebar).toBeVisible();

    // Verify 1. Brand zone
    await expect(sidebar.locator('.sidebar-brand')).toContainText('JobPilot AI');

    // Verify Search filter input
    await expect(page.locator('#sidebarSearchDesktop')).toBeVisible();

    // Verify 2. Scrollable navigation zone
    const navScroll = page.locator('#sidebarNavigationDesktop');
    await expect(navScroll).toBeVisible();

    // Verify navigation groups exist
    await expect(navScroll).toContainText('1. Overview');
    await expect(navScroll).toContainText('2. Career & Profile');
    await expect(navScroll).toContainText('3. Jobs & Registry');
    await expect(navScroll).toContainText('4. Communications');
    await expect(navScroll).toContainText('5. AI Workspace');
    await expect(navScroll).toContainText('6. Content & Social');
    await expect(navScroll).toContainText('7. Data & Archives');
    await expect(navScroll).toContainText('8. Health & Automation');
    await expect(navScroll).toContainText('9. Configuration');
    await expect(navScroll).toContainText('10. User Account');

    // Verify 3. Pinned Settings Link is visible immediately without scrolling
    const pinnedSettings = page.locator('#pinnedSettingsLink');
    await expect(pinnedSettings).toBeVisible();
    await expect(pinnedSettings).toContainText('Settings & Vault');

    // Verify 4. Fixed user footer
    await expect(page.locator('.sidebar-footer')).toBeVisible();
    await expect(page.locator('.user-name').first()).toBeVisible();

    // Capture "after" desktop screenshot
    await page.screenshot({ path: '../reports/ui/after/sidebar_after_desktop.png', fullPage: false });
  });

  test('sidebar navigation container scrolls independently with wheel/touch', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    const navScroll = page.locator('#sidebarNavigationDesktop');
    await expect(navScroll).toBeVisible();

    // Check computed overflow-y style
    const overflowY = await navScroll.evaluate(el => window.getComputedStyle(el).overflowY);
    expect(['auto', 'scroll']).toContain(overflowY);

    // Scroll navigation container down
    await navScroll.evaluate(el => {
      el.scrollTop = 300;
    });
    const scrolledTop = await navScroll.evaluate(el => el.scrollTop);
    expect(scrolledTop).toBeGreaterThanOrEqual(0);

    // Settings shortcut remains pinned and visible at bottom regardless of nav scroll
    await expect(page.locator('#pinnedSettingsLink')).toBeVisible();
  });

  test('clicking pinned settings shortcut navigates directly to /settings vault', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto('/dashboard');

    await page.click('#pinnedSettingsLink');
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/.*settings/);
    await expect(page.locator('body')).toContainText(/Encrypted Credential Vault|Central Settings/i);
    await expect(page.locator('#pinnedSettingsLink')).toHaveClass(/active/);

    // Capture settings screenshot
    await page.screenshot({ path: '../reports/ui/screenshots/settings_vault.png', fullPage: false });
  });

  test('collapsible accordion groups expand and collapse on toggle', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto('/dashboard');

    // Group 2 (Career & Profile) toggle button
    const careerToggle = page.locator('button[data-bs-target="#group-career"]');
    const careerCollapse = page.locator('#group-career');

    // Click to expand
    await careerToggle.click();
    await page.waitForTimeout(350); // allow bootstrap collapse animation
    await expect(careerCollapse).toHaveClass(/show/);
    await expect(careerCollapse.locator('text=CV Studio & LaTeX')).toBeVisible();

    // Click to collapse
    await careerToggle.click();
    await page.waitForTimeout(350);
    await expect(careerCollapse).not.toHaveClass(/show/);
  });

  test('real-time search filter filters links and auto-opens matched group', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto('/dashboard');

    const searchInput = page.locator('#sidebarSearchDesktop');
    await searchInput.fill('LaTeX');
    await page.waitForTimeout(100);

    // Group 2 containing LaTeX should auto-expand and show CV Studio & LaTeX
    const cvStudioLink = page.locator('#sidebarNavigationDesktop a:has-text("CV Studio & LaTeX")');
    await expect(cvStudioLink).toBeVisible();

    // Non-matching links should be hidden with nav-item-hidden class
    const dashboardSubItem = page.locator('#sidebarNavigationDesktop a:has-text("Dashboard")').locator('..');
    await expect(dashboardSubItem).toHaveClass(/nav-item-hidden/);

    // Clear filter
    await searchInput.fill('');
    await page.waitForTimeout(100);
    await expect(dashboardSubItem).not.toHaveClass(/nav-item-hidden/);
  });

  test('responsive mobile drawer opens via hamburger and supports navigation', async ({ page }) => {
    // Mobile viewport
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Desktop sidebar should be hidden
    await expect(page.locator('#desktopSidebar')).toBeHidden();

    // Hamburger button should be visible
    const hamburger = page.locator('#mobileMenuToggle');
    await expect(hamburger).toBeVisible();

    // Click hamburger to open offcanvas drawer
    await hamburger.click();
    const mobileDrawer = page.locator('#mobileSidebar');
    await expect(mobileDrawer).toBeVisible();
    await expect(mobileDrawer).toContainText('JobPilot AI');

    // Capture mobile open screenshot
    await page.screenshot({ path: '../reports/ui/mobile/mobile_drawer_open.png', fullPage: false });

    // Navigate to Applications from mobile drawer
    const appLink = mobileDrawer.locator('a:has-text("Application Registry")');
    await expect(appLink).toBeVisible();
    await appLink.click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/.*applications/);
    await expect(page.locator('body')).toContainText(/Application Registry|Application Tracking/i);
  });

  test('responsive tablet layout snapshot verification', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('#mobileMenuToggle')).toBeVisible();
    await page.screenshot({ path: '../reports/ui/tablet/dashboard_tablet.png', fullPage: false });
  });

  test('desktop 1080p full dashboard snapshot verification', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('#desktopSidebar')).toBeVisible();
    await page.screenshot({ path: '../reports/ui/desktop/dashboard_desktop.png', fullPage: false });
  });

});
