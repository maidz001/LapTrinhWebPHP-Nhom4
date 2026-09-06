<?php
declare(strict_types=1);

/**
 * app/Views/bookings/_status_style.php
 * Style inline cho status-pill, dùng chung bởi my_requests/pending/detail/history.
 * Nhãn trạng thái/loại dùng Booking::statusLabel() / Booking::typeLabel().
 */
if (!function_exists('mvc_booking_status_style')) {
    function mvc_booking_status_style(string $status): string
    {
        return match ($status) {
            'pending' => 'background:var(--color-warning-bg);color:#92400e;',
            'approved' => 'background:var(--color-success-bg);color:#166534;',
            'rejected' => 'background:var(--color-error-bg);color:#991b1b;',
            'cancelled' => 'background:#f1f5f9;color:var(--color-text-muted);',
            default => '',
        };
    }
}
