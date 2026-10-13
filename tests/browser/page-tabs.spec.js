// resources/js/page-tabs.js: the three tabs, the section switcher, the hash, the live links'
// "More (n)" disclosure and the bounded read-outs (design 02, Changes 1 and 3).
import { expect, test } from '@playwright/test';
import { row } from './helpers.js';

const tab = (page, name) => page.locator(`#tab-${name}`);
const section = (page, name) => page.locator(`#hood-tab-${name}`);

async function expectTab(page, name, sectionName) {
    await expect(tab(page, name)).toHaveAttribute('aria-selected', 'true');
    for (const other of ['demo', 'description', 'hood']) {
        await expect(page.locator(`#panel-${other}`)).toBeVisible({ visible: other === name });
    }
    if (sectionName) {
        await expect(section(page, sectionName)).toHaveAttribute('aria-selected', 'true');
        await expect(page.locator(`#hood-${sectionName}`)).toBeVisible();
    }
}

test('7. keyboard: the tabs, the section switcher and the More (n) disclosure', async ({ page }) => {
    await page.goto('/examples/save-response');

    // Tab reaches the tab list: only the selected tab is in the tab order
    for (let i = 0; i < 5 && !(await tab(page, 'demo').evaluate((el) => el === document.activeElement)); i++) {
        await page.keyboard.press('Tab');
    }
    await expect(tab(page, 'demo')).toBeFocused();

    await page.keyboard.press('ArrowRight');
    await expect(tab(page, 'description')).toBeFocused();
    await expectTab(page, 'description');
    await page.keyboard.press('End');
    await expect(tab(page, 'hood')).toBeFocused();
    await expectTab(page, 'hood');
    await page.keyboard.press('Home');
    await expectTab(page, 'demo');
    await page.keyboard.press('ArrowLeft');   // wraps around
    await expectTab(page, 'hood');
    await expect(tab(page, 'demo')).toHaveAttribute('tabindex', '-1');
    await expect(tab(page, 'hood')).toHaveAttribute('tabindex', '0');

    // The same inside the section switcher
    await page.locator('#hood-sections [aria-selected="true"]').focus();
    await page.keyboard.press('Home');
    await expectTab(page, 'hood', 'code');
    await page.keyboard.press('ArrowRight');
    await expect(section(page, 'requests')).toBeFocused();
    await expectTab(page, 'hood', 'requests');
    await page.keyboard.press('End');
    await expectTab(page, 'hood', 'stored');
    await page.keyboard.press('ArrowRight');
    await expectTab(page, 'hood', 'code');

    // III.4 at 1024: the links that don't fit are in More (n)
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.goto('/examples/variable-presets');
    const button = page.locator('#live-more-button');
    await expect(button).toBeVisible();
    await expect(button).toHaveText(/More \(\d\)/);
    await button.focus();
    await page.keyboard.press('Enter');
    await expect(button).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#live-more-list')).toBeVisible();
    await expect(page.locator('#live-more-list a:focus')).toHaveCount(1);
    await page.keyboard.press('Escape');
    await expect(button).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator('#live-more-list')).toBeHidden();
    await expect(button).toBeFocused();

    // The header stays two rows, and the visible labels are whole
    const header = await page.locator('#page-header').boundingBox();
    expect(header.height).toBeLessThan(100);
    for (const link of await page.locator('#live-links [data-live]:visible').all()) {
        expect(await link.evaluate((el) => el.scrollWidth <= el.clientWidth + 1 && el.getBoundingClientRect().right <= el.parentElement.getBoundingClientRect().right + 1)).toBe(true);
    }
});

test('8. the hash selects the tab and the section; choosing a tab replaces it', async ({ page }) => {
    for (const [hash, name, sectionName] of [['#description', 'description'], ['#code', 'hood', 'code'], ['#requests', 'hood', 'requests'], ['#stored', 'hood', 'stored']]) {
        await page.goto('about:blank');
        await page.goto(`/examples/save-response${hash}`);
        await expectTab(page, name, sectionName);
    }

    await page.goto('about:blank');
    await page.goto('/examples/save-response');
    await expectTab(page, 'demo');
    const entries = await page.evaluate(() => history.length);
    await tab(page, 'description').click();
    await expect(page).toHaveURL(/\/examples\/save-response#description$/);
    await section(page, 'stored').evaluate((el) => el.click());   // also opens Under the hood
    await expect(page).toHaveURL(/#stored$/);
    await tab(page, 'demo').click();
    await expect(page).toHaveURL(/\/examples\/save-response$/);
    expect(await page.evaluate(() => history.length)).toBe(entries);

    // A hashchange (a link, back and forward) switches the tab
    await page.evaluate(() => {
        location.hash = '#requests';
    });
    await expectTab(page, 'hood', 'requests');
    await page.goBack();
    await expectTab(page, 'demo');
    await page.goForward();
    await expectTab(page, 'hood', 'requests');

    // A hash-less load opens Demo, even with a section remembered
    await page.evaluate(() => localStorage.setItem('surveyjs-hood-section', 'stored'));
    await page.goto('about:blank');
    await page.goto('/examples/save-response');
    await expectTab(page, 'demo');
    await tab(page, 'hood').click();
    await expectTab(page, 'hood', 'stored');
});

for (const [width, height] of [[1440, 900], [390, 844]]) {
    test(`9. after 50 notes, the section switcher and the first request row stay on screen (${width}×${height})`, async ({ page }) => {
        await page.setViewportSize({ width, height });
        await page.route('**/api/responses', (route) => route.fulfill({ status: 400, contentType: 'application/json', body: '{"errors":[{"type":"unknown-choice","path":"incident"}]}' }));
        await page.goto('/examples/validate-response');

        const button = page.getByRole('button', { name: 'An unknown choice' });
        for (let i = 0; i < 50; i++) await button.click();
        await expect(page.locator('#notes > li')).toHaveCount(50);

        await tab(page, 'hood').click();
        await section(page, 'requests').click();
        await expect(row(page, 'POST', '/api/responses')).toBeVisible();
        for (const locator of [page.locator('#hood-sections'), page.locator('#requests > li:not([hidden])').first()]) {
            const box = await locator.boundingBox();
            expect(box.y).toBeGreaterThanOrEqual(0);
            expect(box.y + box.height).toBeLessThanOrEqual(height);
        }
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
    });
}
