<?php
/**
 * includes/icons.php
 * ---------------------------------------------------------------------
 * Icon SVG inline dạng "line icon" (không phụ thuộc thư viện ngoài,
 * không cần kết nối internet để tải font/icon set).
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

function app_icon(string $name): string
{
    $paths = [
        'home'     => '<path d="M4 11.5 12 4l8 7.5" /><path d="M6 10v9a1 1 0 0 0 1 1h3v-6h4v6h3a1 1 0 0 0 1-1v-9" />',
        'grid'     => '<rect x="4" y="4" width="7" height="7" rx="1.4" /><rect x="13" y="4" width="7" height="7" rx="1.4" /><rect x="4" y="13" width="7" height="7" rx="1.4" /><rect x="13" y="13" width="7" height="7" rx="1.4" />',
        'monitor'  => '<rect x="3" y="4" width="18" height="12" rx="1.6" /><path d="M8 20h8" /><path d="M12 16v4" />',
        'list'     => '<path d="M8 6h12" /><path d="M8 12h12" /><path d="M8 18h12" /><circle cx="4" cy="6" r="1.1" /><circle cx="4" cy="12" r="1.1" /><circle cx="4" cy="18" r="1.1" />',
        'clock'    => '<circle cx="12" cy="12" r="8.5" /><path d="M12 7.5V12l3 2" />',
        'plus'     => '<circle cx="12" cy="12" r="8.5" /><path d="M12 8v8" /><path d="M8 12h8" />',
        'swap'     => '<path d="M4 8h13" /><path d="M13 4l4 4-4 4" /><path d="M20 16H7" /><path d="M11 12l-4 4 4 4" />',
        'bar-chart'=> '<path d="M5 20V10" /><path d="M12 20V4" /><path d="M19 20v-7" /><path d="M3 20h18" />',
        'users'    => '<circle cx="9" cy="8" r="3.2" /><path d="M3.5 19c.7-3 3-4.8 5.5-4.8s4.8 1.8 5.5 4.8" /><circle cx="17" cy="8.5" r="2.4" /><path d="M15.7 14.4c2 .4 3.6 1.9 4.2 4.6" />',
        'settings' => '<circle cx="12" cy="12" r="3" /><path d="M19.4 13.5a7.7 7.7 0 0 0 0-3l2-1.5-2-3.4-2.3.9a7.6 7.6 0 0 0-2.6-1.5L14 2h-4l-.5 2.4a7.6 7.6 0 0 0-2.6 1.5l-2.3-.9-2 3.4 2 1.5a7.7 7.7 0 0 0 0 3l-2 1.6 2 3.4 2.3-.9c.8.7 1.7 1.2 2.6 1.5L10 22h4l.5-2.5c.9-.3 1.8-.8 2.6-1.5l2.3.9 2-3.4-2-1.6Z" />',
        'logout'   => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3" /><path d="M15 16l4-4-4-4" /><path d="M19 12H9" />',
        'search'   => '<circle cx="11" cy="11" r="6.5" /><path d="M20 20l-4.3-4.3" />',
        'bell'     => '<path d="M6 10a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z" /><path d="M10 20a2 2 0 0 0 4 0" />',
        'wrench'   => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2 2.5-2.5Z" />',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="1.6" /><path d="M8 3v4" /><path d="M16 3v4" /><path d="M4 10h16" />',
        'menu'     => '<path d="M4 7h16" /><path d="M4 12h16" /><path d="M4 17h16" />',
        'chevron-left' => '<path d="M14 6l-6 6 6 6" />',
    ];

    $inner = $paths[$name] ?? $paths['grid'];

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
        . 'stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">'
        . $inner . '</svg>';
}
