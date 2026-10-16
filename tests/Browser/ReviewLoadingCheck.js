async (page) => {
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    check(new URL(page.url()).origin === 'http://127.0.0.1:8766', 'Use only the isolated demo on port 8766');
    await page.goto('http://127.0.0.1:8766/app/review');
    check(await page.getByText('Fictional Senior Demo College', { exact: true }).count() === 1, 'Synthetic demo College required');
    check(await page.locator('button.approve').count() === 1, 'Fresh pending demo booking required');
    const current = JSON.parse(await page.locator('script[data-page]').textContent());
    const results = [];
    const accept = (dialog) => dialog.accept();
    page.on('dialog', accept);
    try {
        await page.locator('.review-actions textarea').fill('เหตุผลสังเคราะห์สำหรับตรวจสถานะกำลังส่ง');
        for (const action of ['approve', 'reject']) {
            let notify, release, requests = 0;
            const ready = new Promise((resolve) => { notify = resolve; });
            const held = new Promise((resolve) => { release = resolve; });
            const pattern = `**/app/bookings/*/${action}`;
            await page.route(pattern, async (route) => {
                requests++;
                notify();
                await held;
                await route.fulfill({
                    status: 200,
                    headers: { 'X-Inertia': 'true', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...current, props: { ...current.props, errors: { status: 'Synthetic validation error' } } }),
                });
            });
            try {
                const button = action === 'approve' ? page.locator('button.approve') : page.getByRole('button', { name: 'ไม่อนุมัติ', exact: true });
                await button.focus();
                await page.keyboard.press('Enter');
                await page.getByRole('dialog').getByRole('button', { name: action === 'approve' ? 'ยืนยันอนุมัติ' : 'ยืนยันไม่อนุมัติ', exact: true }).click();
                let timeout;
                try {
                    await Promise.race([ready, new Promise((_, reject) => { timeout = setTimeout(() => reject(new Error('Review request not observed')), 10000); })]);
                } finally {
                    clearTimeout(timeout);
                }
                check(await page.locator('.review-actions').getAttribute('aria-busy') === 'true', 'Busy state missing');
                check(await page.locator('button.approve').isDisabled(), 'Approve must be disabled while pending');
                check(await page.getByRole('button', { name: 'ไม่อนุมัติ', exact: true }).isDisabled(), 'Reject must be disabled while pending');
                const notice = page.getByRole('status').filter({ hasText: /กำลัง/ });
                check(await notice.count() === 1, 'Loading announcement missing');
                check(await notice.evaluate((element) => element.closest('[aria-busy=true]') === null), 'Loading announcement must stay outside the busy region');
                await page.locator('button.approve').dispatchEvent('click');
                await page.getByRole('button', { name: 'ไม่อนุมัติ', exact: true }).dispatchEvent('click');
                check(requests === 1, 'Repeated action sent another request');
                release();
                await page.getByRole('alert').filter({ hasText: 'Synthetic validation error' }).waitFor();
                await page.waitForFunction(() => document.querySelector('.review-actions')?.getAttribute('aria-busy') === 'false');
                check(!await page.locator('button.approve').isDisabled(), 'Action not restored after validation');
                check(await page.evaluate(() => document.activeElement.getAttribute('role') === 'alert'), 'Validation feedback did not receive focus');
                results.push({ action, loading: 'pass', duplicate_guard: 'pass', error_recovery: 'pass' });
            } finally {
                release();
                await page.unroute(pattern);
            }
        }
        await page.setViewportSize({ width: 390, height: 900 });
        for (const failure of ['network', 'http']) {
            const pattern = '**/app/bookings/*/approve';
            await page.route(pattern, (route) => failure === 'network'
                ? route.abort()
                : route.fulfill({ status: 503, contentType: 'text/plain', body: 'Synthetic service failure' }));
            try {
                await page.locator('button.approve').click();
                await page.getByRole('dialog').getByRole('button', { name: 'ยืนยันอนุมัติ', exact: true }).click();
                const message = failure === 'network' ? 'เชื่อมต่อระบบไม่ได้' : 'ระบบตอบกลับไม่สำเร็จ';
                await page.getByRole('alert').filter({ hasText: message }).waitFor();
                await page.waitForFunction(() => document.querySelector('.review-actions')?.getAttribute('aria-busy') === 'false');
                check(!await page.locator('button.approve').isDisabled(), 'Action not restored after request failure');
                check(await page.evaluate(() => document.activeElement.getAttribute('role') === 'alert'), 'Request failure feedback did not receive focus');
                check(!await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), 'Error message overflows mobile viewport');
                results.push({ failure, safe_alert: 'pass', action_recovery: 'pass', mobile_overflow: 'none' });
            } finally {
                await page.unroute(pattern);
            }
        }
    } finally {
        page.off('dialog', accept);
    }
    return results;
}
