const assert = require('node:assert/strict');
const { mkdirSync, unlinkSync, writeFileSync } = require('node:fs');
const { join } = require('node:path');
const { chromium } = require('playwright-core');

async function main() {
	const fixtures = join(process.env.WP_ROOT, 'wp-content', 'mu-plugins');
	const fixture = join(fixtures, 'schedsense-test-admin-notice.php');
	mkdirSync(fixtures, { recursive: true });
	writeFileSync(fixture, `<?php
add_action( 'admin_notices', static function () {
	echo '<div id="schedsense-test-admin-notice" class="notice notice-warning is-dismissible"><p>Example theme notice</p></div>';
} );
`, { flag: 'wx' });

	let browser;
	try {
		browser = await chromium.launch({ channel: 'chrome' });
		const page = await browser.newPage({
			viewport: { width: 1280, height: 840 },
			reducedMotion: 'reduce'
		});
		const url = process.env.WP_URL;
		await page.goto(url + '/wp-login.php');
		await page.locator('#user_login').fill('auditadmin');
		await page.locator('#user_pass').fill('audit-only-password');
		await page.locator('#wp-submit').click();
		await page.goto(url + '/wp-admin/tools.php?page=schedsense_diagnostics');

		const tabs = page.locator('.schedsense-wrap .nav-tab-wrapper');
		for (const tab of ['Actions', 'Sources', 'Report', 'Overview']) {
			await tabs.getByRole('link', { name: tab, exact: true }).click();
			await page.waitForFunction(() => {
				const marker = document.querySelector('.schedsense-wrap > .wp-header-end');
				const notice = document.getElementById('schedsense-test-admin-notice');
				return marker && notice && notice.parentElement === marker.parentElement &&
					Boolean(marker.compareDocumentPosition(notice) & Node.DOCUMENT_POSITION_FOLLOWING) &&
					notice.querySelector('.notice-dismiss');
			});
		}

		const header = page.locator('.schedsense-header');
		const notice = page.locator('#schedsense-test-admin-notice');
		const headerBox = await header.boundingBox();
		const noticeBox = await notice.boundingBox();
		assert(noticeBox.y >= headerBox.y + headerBox.height);
		await page.screenshot({
			path: join(process.env.RUNNER_TEMP, 'schedsense-admin-ui.png'),
			clip: { ...headerBox, height: noticeBox.y + noticeBox.height - headerBox.y }
		});
		await notice.getByRole('button', { name: 'Dismiss this notice.' }).click();
		await notice.waitFor({ state: 'hidden' });

		const refresh = header.getByRole('button', { name: 'Refresh diagnostics' });
		for (const width of [1280, 320]) {
			await page.setViewportSize({ width, height: 840 });
			const check = await refresh.evaluate(button => {
				const icon = button.querySelector('.dashicons');
				const label = document.createRange();
				label.selectNode(button.lastChild);
				const center = element => {
					const { top, height } = element.getBoundingClientRect();
					return top + height / 2;
				};
				return {
					offset: Math.abs(center(icon) - center(label)),
					iconLineHeight: getComputedStyle(icon).lineHeight,
					overflow: button.scrollWidth > button.clientWidth
				};
			});
			assert.equal(check.iconLineHeight, '18px');
			assert(check.offset <= 2 && !check.overflow, JSON.stringify(check));
		}
		await page.keyboard.press('Tab');
		await refresh.focus();
		assert.equal(await refresh.evaluate(button => getComputedStyle(button).outlineColor), 'rgb(255, 255, 255)');
		await refresh.press('Enter');
		await page.getByText('Diagnostics refreshed.', { exact: true }).waitFor();
		console.log('Admin notices stayed below the header on all four tabs. Refresh aligned on desktop and mobile and worked with the keyboard.');
	} finally {
		await browser?.close();
		unlinkSync(fixture);
	}
}

main().catch(error => {
	console.error(error);
	process.exitCode = 1;
});
