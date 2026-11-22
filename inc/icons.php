<?php
declare(strict_types=1);

function icon(string $name, int $size = 20): string
{
    $p = [
        'cart' => '<path d="M6 7h14l-1.6 9H8L6 7Zm0 0-.7-3H3" /><circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
        'play' => '<path d="M8 5.5v13l11-6.5-11-6.5Z" fill="currentColor" stroke="none"/>',
        'pause' => '<rect x="6.5" y="5" width="4" height="14" rx="1" fill="currentColor" stroke="none"/><rect x="13.5" y="5" width="4" height="14" rx="1" fill="currentColor" stroke="none"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'ext' => '<path d="M7 17 17 7M9 7h8v8"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'pin' => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'ticket' => '<path d="M4 8a2 2 0 0 0 0 4v0a2 2 0 0 1 0 4v2h16v-2a2 2 0 0 1 0-4v0a2 2 0 0 0 0-4V6H4v2Z"/><path d="M14 6v12" stroke-dasharray="2 2"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'truck' => '<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17" cy="17.5" r="1.6"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'refresh' => '<path d="M20 11a8 8 0 0 0-14-4L4 9m0-4v4h4M4 13a8 8 0 0 0 14 4l2-2m0 4v-4h-4"/>',
        'instagram' => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="16.8" cy="7.2" r=".6" fill="currentColor"/>',
        'facebook' => '<path d="M14 8h2.5V4.5H14a3.5 3.5 0 0 0-3.5 3.5v2H8V13h2.5v7.5H14V13h2.3l.5-3H14V8.5c0-.3.2-.5.5-.5Z"/>',
        'youtube' => '<rect x="3" y="6" width="18" height="12" rx="3.5"/><path d="M10.5 9.5v5l4.2-2.5-4.2-2.5Z" fill="currentColor" stroke="none"/>',
        'spotify' => '<circle cx="12" cy="12" r="9"/><path d="M7.5 9.6c3.2-.9 6.6-.6 9.2.9M8 12.6c2.7-.7 5.4-.4 7.6.9M8.6 15.3c2.2-.5 4.3-.3 6 .7"/>',
        'tiktok' => '<path d="M14 4v10.2a3.3 3.3 0 1 1-3.3-3.3M14 4c.3 2.3 1.8 3.7 4 3.9"/>',
        'whatsapp' => '<path d="M4.5 19.5 5.7 15A7.5 7.5 0 1 1 9 18.3l-4.5 1.2Z"/><path d="M9.5 9c.2 2.6 2.7 5 5.3 5.2l1-1.1-1.9-1-.8.7c-.8-.3-1.7-1.2-2-2l.7-.8-1-1.9L9.5 9Z"/>',
        'mail' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m4 8 8 5.5L20 8"/>',
        'user' => '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c.6-3.6 3.5-5.5 7-5.5s6.4 1.9 7 5.5"/>',
    ];
    $inner = $p[$name] ?? '';
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}
