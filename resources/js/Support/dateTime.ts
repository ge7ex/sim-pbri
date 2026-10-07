/** Explicit 24-hour booking/audit display; retain the browser's local timezone. */
export function formatBookingDateTime(value: string, withSeconds = false): string {
    const date = new Date(value);
    if (!Number.isFinite(date.getTime())) return '—';
    return new Intl.DateTimeFormat('th-TH', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
        ...(withSeconds ? { second: '2-digit' as const } : {}),
    }).format(date);
}
