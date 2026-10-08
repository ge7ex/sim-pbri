const labels: Record<string, string> = {
    pending: 'รอตรวจสอบ',
    approved: 'อนุมัติแล้ว',
    rejected: 'ไม่อนุมัติ',
    cancelled: 'ยกเลิก',
};

export function bookingStatusLabel(status: string | null): string {
    return status === null ? 'เริ่มต้น' : labels[status] ?? status;
}
