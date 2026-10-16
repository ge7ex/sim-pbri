async (page) => {
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    const origin = 'http://127.0.0.1:8766';
    check(new URL(page.url()).origin === origin, 'Isolated demo on 8766 required');
    const payload = async () => JSON.parse(await page.locator('script[data-page]').textContent());
    const results = [];
    const nativeDialogs = [];
    const onDialog = async dialog => { nativeDialogs.push(dialog.type()); await dialog.dismiss(); };
    page.on('dialog', onDialog);
    const assertNoOverflow = async () => check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Horizontal viewport overflow');
    try {
        await page.goto(origin + '/app/calendar');
        let current = await payload();
        check(current.props.auth.user.college.name === 'Fictional Senior Demo College', 'Synthetic demo College required');
        const permissions = current.props.auth.permissions;
        const expected = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Bangkok' }).format(new Date()).slice(0, 7);
        check(current.props.filters.date_from === expected + '-01', 'Calendar default is not current Bangkok month');
        check(await page.getByRole('button', { name: 'ปฏิทิน', exact: true }).getAttribute('aria-pressed') === 'true', 'Calendar not default view');
        await page.getByRole('button', { name: 'เดือนถัดไป', exact: true }).click();
        await page.waitForURL(/date_from=/);
        await page.getByRole('button', { name: 'เดือนก่อนหน้า', exact: true }).click();
        await page.waitForURL(new RegExp(expected + '-01'));
        await page.getByRole('button', { name: 'รายการ', exact: true }).click();
        await page.getByLabel('จากวันที่', { exact: true }).fill('2026-12-01');
        await page.getByLabel('ถึงวันที่', { exact: true }).fill('2027-01-31');
        await page.getByRole('button', { name: 'แสดงช่วงเวลา', exact: true }).click();
        await page.waitForURL(/2027-01-31/);
        check(await page.getByRole('button', { name: 'รายการ', exact: true }).getAttribute('aria-pressed') === 'true', 'Filter lost selected list view');
        await page.getByRole('button', { name: 'ปฏิทิน', exact: true }).click();
        check(await page.locator('.calendar-day').count() === 31, 'December grid wrong');
        await page.getByRole('button', { name: 'เดือนถัดไป', exact: true }).click();
        await page.waitForURL(/date_from=2027-01-01/);
        check(await page.locator('.calendar-day').count() === 31, 'January grid wrong');
        await page.setViewportSize({ width: 390, height: 844 });
        await assertNoOverflow();
        await page.screenshot({ path: process.env.SIM_UI_SCREENSHOT_DIR + '/calendar-mobile-' + current.props.auth.user.role + '.png', fullPage: true });
        await page.setViewportSize({ width: 1366, height: 900 });
        // Synthetic intercepted multi-day and midnight boundaries: never write to demo data.
        await page.goto(origin + '/app/calendar?date_from=2026-12-01&date_to=2026-12-31');
        current = await payload();
        const calendarPattern = '**/app/calendar?*';
        await page.route(calendarPattern, route => route.fulfill({ status: 200, headers: { 'X-Inertia': 'true', 'Content-Type': 'application/json' }, body: JSON.stringify({ ...current, props: { ...current.props, events: [
            { starts_at: '2026-12-01T16:00:00Z', ends_at: '2026-12-02T17:00:00Z', title: 'Synthetic multi-day', status: 'approved' },
            { starts_at: '2026-12-01T02:00:00Z', ends_at: '2026-12-01T03:00:00Z', title: 'ถูกจองแล้ว' },
        ] } }) }));
        await page.getByRole('button', { name: 'แสดงช่วงเวลา', exact: true }).click();
        await page.getByRole('button', { name: '2026-12-01 · 2 รายการ', exact: true }).waitFor();
        await page.getByRole('button', { name: '2026-12-02 · 1 รายการ', exact: true }).click();
        check(await page.locator('.day-agenda').getByText('Synthetic multi-day', { exact: true }).count() === 1, 'Multi-day agenda missing');
        check(await page.getByRole('button', { name: '2026-12-03 · 0 รายการ', exact: true }).count() === 1, 'Midnight end incorrectly occupies next day');
        await page.getByRole('button', { name: '2026-12-01 · 2 รายการ', exact: true }).click();
        check(await page.locator('.day-agenda').getByText('ข้อมูลปกปิด', { exact: true }).count() === 1, 'Private event badge missing');
        await page.unroute(calendarPattern);
        results.push('calendar: current Bangkok month, list/calendar toggle, range, year navigation, multi-day/midnight, privacy, mobile');

        if (permissions.includes('booking.approve')) {
            await page.goto(origin + '/app/review');
            current = await payload();
            let requests = 0;
            const pattern = '**/app/bookings/*/approve';
            await page.route(pattern, route => { requests++; return route.fulfill({ status: 200, headers: { 'X-Inertia': 'true', 'Content-Type': 'application/json' }, body: JSON.stringify({ ...current, props: { ...current.props, errors: { status: 'Synthetic review validation' } } }) }); });
            await page.locator('button.approve').click();
            await page.getByRole('dialog').waitFor();
            check(requests === 0, 'Approval sent before confirmation');
            check(await page.getByRole('dialog').getByRole('button', { name: 'ย้อนกลับ', exact: true }).evaluate(el => el === document.activeElement), 'Modal initial focus not on safe return');
            await page.keyboard.press('Shift+Tab');
            check(await page.evaluate(() => !!document.activeElement.closest('dialog')), 'Keyboard focus escaped modal');
            await page.keyboard.press('Tab');
            check(await page.getByRole('dialog').getByRole('button', { name: 'ย้อนกลับ', exact: true }).evaluate(el => el === document.activeElement), 'Forward Tab did not wrap within modal');
            await page.keyboard.press('Escape');
            await page.getByRole('dialog').waitFor({ state: 'hidden' });
            check(requests === 0, 'Escape submitted approval');
            check(await page.locator('button.approve').evaluate(el => el === document.activeElement), 'Focus not restored after modal cancel');
            await page.locator('button.approve').click();
            await page.getByRole('dialog').getByRole('button', { name: 'ยืนยันอนุมัติ', exact: true }).click();
            await page.getByRole('alert').filter({ hasText: 'Synthetic review validation' }).waitFor();
            await page.getByRole('dialog').waitFor({ state: 'hidden' });
            check(requests === 1, 'Approval request count wrong');
            await page.unroute(pattern);
            await page.goto(origin + '/app/bookings');
            const booking = (await payload()).props.bookings.data[0];
            check(booking, 'Own College booking fixture required for recall UI');
            const detailUrl = origin + '/app/bookings/' + booking.id;
            await page.goto(detailUrl);
            current = await payload();
            // Existing demo has no approved record. Inject only the UI status, never change stored data.
            current.props.booking.status = 'approved';
            await page.goto(origin + '/app/bookings');
            await page.route(detailUrl, route => route.fulfill({ status: 200, headers: { 'X-Inertia': 'true', 'Content-Type': 'application/json' }, body: JSON.stringify(current) }));
            await page.locator(`a[href="/app/bookings/${booking.id}"]`).first().click();
            await page.getByRole('button', { name: 'เรียกกลับเพื่อตรวจสอบใหม่', exact: true }).waitFor();
            await page.unroute(detailUrl);
            let recallBody = null;
            const recallPattern = '**/app/bookings/*/recall';
            await page.route(recallPattern, route => { recallBody = route.request().postDataJSON(); return route.fulfill({ status: 200, headers: { 'X-Inertia': 'true', 'Content-Type': 'application/json' }, body: JSON.stringify({ ...current, props: { ...current.props, errors: { status: 'Synthetic recall validation' } } }) }); });
            await page.getByLabel('เหตุผลที่เรียกกลับ', { exact: true }).fill('Synthetic owner UI check');
            await page.getByRole('button', { name: 'เรียกกลับเพื่อตรวจสอบใหม่', exact: true }).click();
            await page.getByRole('dialog', { name: 'ยืนยันเรียกกลับเพื่อตรวจสอบใหม่' }).waitFor();
            check(recallBody === null, 'Recall sent before confirmation');
            check(await page.getByRole('dialog').textContent().then(text => text.includes('รอตรวจสอบ') && text.includes('Synthetic owner UI check')), 'Recall modal lacks status/reason');
            await page.getByRole('dialog').getByRole('button', { name: 'ย้อนกลับ', exact: true }).click();
            check(recallBody === null, 'Recall cancelled but request sent');
            await page.getByRole('button', { name: 'เรียกกลับเพื่อตรวจสอบใหม่', exact: true }).click();
            await page.getByRole('dialog').getByRole('button', { name: 'ยืนยันเปลี่ยนเป็นรอตรวจสอบ', exact: true }).click();
            await page.getByRole('alert').filter({ hasText: 'Synthetic recall validation' }).waitFor();
            check(recallBody?.confirmed === true && recallBody.reason === 'Synthetic owner UI check', 'Recall confirmation payload missing');
            await page.unroute(recallPattern);
            results.push('review/recall: modal, safe initial focus, keyboard containment, Escape, focus restore, cancel sends no request, explicit confirmation payload');
        }

        if (!permissions.includes('booking.approve')) {
            await page.goto(origin + '/app/bookings');
            const history = await payload();
            const own = history.props.bookings.data.find(item => item.requested_by?.id === history.props.auth.user.id && ['pending', 'approved'].includes(item.status));
            check(own, 'Own cancellable synthetic booking required');
            await page.goto(origin + '/app/bookings/' + own.id);
            current = await payload();
            let cancellation = 0;
            const pattern = '**/app/bookings/*/cancel';
            await page.route(pattern, route => { cancellation++; return route.fulfill({ status: 200, headers: { 'X-Inertia': 'true', 'Content-Type': 'application/json' }, body: JSON.stringify({ ...current, props: { ...current.props, errors: { status: 'Synthetic cancel validation' } } }) }); });
            await page.setViewportSize({ width: 390, height: 844 });
            await page.getByRole('button', { name: 'ยกเลิกคำขอ', exact: true }).click();
            await page.getByRole('dialog').waitFor();
            check(cancellation === 0, 'Cancel sent before confirmation');
            const bounds = await page.getByRole('dialog').boundingBox();
            check(Math.abs(bounds.x + bounds.width / 2 - 195) < 2 && Math.abs(bounds.y + bounds.height / 2 - 422) < 2, 'Modal not centered in mobile viewport');
            await assertNoOverflow();
            await page.screenshot({ path: process.env.SIM_UI_SCREENSHOT_DIR + '/confirm-mobile.png', fullPage: false });
            await page.keyboard.press('Escape');
            check(cancellation === 0, 'Escape sent cancellation');
            await page.getByRole('button', { name: 'ยกเลิกคำขอ', exact: true }).click();
            await page.getByRole('dialog').getByRole('button', { name: 'ยืนยันยกเลิกคำขอ', exact: true }).click();
            await page.getByRole('alert').filter({ hasText: 'Synthetic cancel validation' }).waitFor();
            check(cancellation === 1, 'Cancel did not require one confirmation');
            await page.unroute(pattern);
            await page.setViewportSize({ width: 1366, height: 900 });
            results.push('Lecturer cancellation: mobile modal, Escape no write, explicit confirm, intercepted validation');
        }

        if (permissions.includes('simulator.view')) {
            await page.goto(origin + '/app/simulators');
            current = await payload();
            check(await page.locator('form.stack').count() === 0, 'Registry opens editing forms automatically');
            check(await page.getByLabel('เลือกทรัพย์สินในหน้านี้', { exact: true }).count() === 1, 'Asset dropdown absent');
            const options = await page.getByLabel('เลือกทรัพย์สินในหน้านี้', { exact: true }).locator('option').all();
            if (options.length > 1) {
                const value = await options[1].getAttribute('value');
                await page.getByLabel('เลือกทรัพย์สินในหน้านี้', { exact: true }).selectOption(value);
                const selected = current.props.assets.data.find(item => String(item.id) === value);
                check(await page.locator('.asset-heading h3').textContent() === selected.asset_name, 'Selection did not update read-only details');
            }
            if (permissions.includes('simulator.update')) {
                await page.getByRole('button', { name: 'แก้ไขทรัพย์สิน / สถานะ', exact: true }).click();
                check(await page.locator('#asset-form-title').evaluate(el => el.closest('section') === document.activeElement), 'Asset editor not focused');
                check(await page.getByLabel('เลือกทรัพย์สินในหน้านี้', { exact: true }).isDisabled(), 'Selection allowed during edit');
                let saved = null;
                const pattern = '**/app/simulators/assets/*';
                await page.route(pattern, route => { saved = route.request().postDataJSON(); return route.fulfill({ status: 200, headers: { 'X-Inertia': 'true', 'Content-Type': 'application/json' }, body: JSON.stringify({ ...current, props: { ...current.props, errors: { asset_name: 'Synthetic asset validation' } } }) }); });
                await page.getByLabel('สถานที่จัดเก็บ', { exact: true }).fill('Synthetic unsaved location');
                await page.getByRole('button', { name: 'ตรวจสอบและยืนยันการแก้ไข', exact: true }).click();
                await page.getByRole('dialog').waitFor();
                check(saved === null, 'Asset saved before confirmation');
                await page.getByRole('dialog').getByRole('button', { name: 'ย้อนกลับ', exact: true }).click();
                check(await page.getByLabel('สถานที่จัดเก็บ', { exact: true }).inputValue() === 'Synthetic unsaved location', 'Modal cancel lost edited fields');
                await page.getByRole('button', { name: 'ตรวจสอบและยืนยันการแก้ไข', exact: true }).click();
                await page.getByRole('dialog').getByRole('button', { name: 'ยืนยันบันทึกการแก้ไข', exact: true }).click();
                await page.getByRole('alert').filter({ hasText: 'Synthetic asset validation' }).waitFor();
                check(saved?.location === 'Synthetic unsaved location', 'Confirmed asset payload wrong');
                check(await page.getByLabel('สถานที่จัดเก็บ', { exact: true }).inputValue() === 'Synthetic unsaved location', 'Validation lost fields');
                await page.unroute(pattern);
                await page.getByRole('button', { name: 'ยกเลิกการแก้ไข', exact: true }).click();
                await page.getByRole('button', { name: 'แก้ไขประเภท', exact: true }).click();
                await page.getByRole('button', { name: 'ตรวจสอบและยืนยันการแก้ไข', exact: true }).click();
                await page.getByRole('dialog').waitFor();
                await page.keyboard.press('Escape');
                await page.getByRole('button', { name: 'ยกเลิกการแก้ไข', exact: true }).click();
                await page.getByRole('button', { name: 'เพิ่มทรัพย์สิน', exact: true }).click();
                check(await page.getByLabel('ชื่อทรัพย์สิน', { exact: true }).inputValue() === '', 'Create reused edit data');
                await page.getByRole('button', { name: 'ยกเลิกการแก้ไข', exact: true }).click();
                results.push('registry Admin: dropdown/details, explicit edit/create, one editor, save modal, cancel preserves fields, validation preserves fields');
            } else {
                check(await page.getByRole('button', { name: 'แก้ไขทรัพย์สิน / สถานะ', exact: true }).count() === 0, 'Staff edit control leaked');
                check(await page.getByText('ราคาซื้อ', { exact: true }).count() === 0, 'Staff finance label leaked');
                results.push('registry Staff: read-only selector and no financial/edit controls');
            }
            for (const width of [390, 768, 1366]) {
                await page.setViewportSize({ width, height: 900 });
                await assertNoOverflow();
            }
            await page.screenshot({ path: process.env.SIM_UI_SCREENSHOT_DIR + '/registry-' + current.props.auth.user.role + '.png', fullPage: true });
        }
        check(nativeDialogs.length === 0, 'Native browser confirmation appeared');
        return results;
    } finally { page.off('dialog', onDialog); }
}
