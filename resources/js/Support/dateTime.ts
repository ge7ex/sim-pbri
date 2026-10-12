const BOOKING_TIME_ZONE = 'Asia/Bangkok';

/** Interpret naive form values as Bangkok wall time; keep serialized instants absolute. */
export function parseBookingDateTime(value: string): Date {
    if (!value) return new Date(Number.NaN);
    if (/(?:Z|[+-]\d{2}:?\d{2})$/i.test(value)) return new Date(value);
    return new Date(`${value.length === 16 ? `${value}:00` : value}+07:00`);
}

export function bookingDateTimeToIso(value: string): string {
    return parseBookingDateTime(value).toISOString();
}

/** Format a persisted instant for the application's fixed Bangkok timezone. */
export function formatBookingDateTime(value: string, withSeconds = false): string {
    const date = new Date(value);
    if (!Number.isFinite(date.getTime())) return '—';
    return new Intl.DateTimeFormat('th-TH', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
        timeZone: BOOKING_TIME_ZONE,
        ...(withSeconds ? { second: '2-digit' as const } : {}),
    }).format(date);
}

/** Populate datetime-local fields in Bangkok time regardless of device timezone. */
export function bookingDateTimeInput(value: string | null | undefined): string {
    if (!value) return '';
    if (!/(?:Z|[+-]\d{2}:?\d{2})$/i.test(value)) return value.slice(0, 16);
    const date = new Date(value);
    if (!Number.isFinite(date.getTime())) return '';
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: BOOKING_TIME_ZONE,
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(date).reduce<Record<string, string>>((result, part) => {
        result[part.type] = part.value;
        return result;
    }, {});
    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
}
