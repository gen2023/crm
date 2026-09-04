@props(['name'])

@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'users' => '<circle cx="9" cy="8" r="3.25"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M15.5 14.2c2.6.4 4.5 2.6 4.5 5.3"/>',
        'roles' => '<path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3z"/><path d="M9.5 12l1.8 1.8L15 10"/>',
        'chevron-left' => '<path d="M14.5 5l-6.5 7 6.5 7"/>',
        'chevron-right' => '<path d="M9.5 5l6.5 7-6.5 7"/>',
        'logout' => '<path d="M9 4H5a1 1 0 00-1 1v14a1 1 0 001 1h4"/><path d="M14 15l4-4-4-4"/><path d="M18 11H9"/>',
        'eye' => '<path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.75"/>',
        'pencil' => '<path d="M4 16.5V20h3.5L18.4 9.1a1.5 1.5 0 000-2.1l-1.4-1.4a1.5 1.5 0 00-2.1 0L4 16.5z"/>',
        'trash' => '<path d="M4 7h16"/><path d="M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2"/><path d="M6 7l1 13a1 1 0 001 1h8a1 1 0 001-1l1-13"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'check' => '<path d="M5 13l4 4L19 7"/>',
        'chevron-down' => '<path d="M5 8.5l7 7 7-7"/>',
        'customers' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10.5" r="2"/><path d="M6 16.5c0-1.8 1.4-3 3-3s3 1.2 3 3"/><path d="M14 9.5h4"/><path d="M14 13h4"/>',
        'products' => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'orders' => '<path d="M6 3h9l3 4v13a1 1 0 01-1 1H7a1 1 0 01-1-1V3z"/><path d="M9 8h6"/><path d="M9 12h6"/><path d="M9 16h3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 13a1.7 1.7 0 00.34 1.87l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.7 1.7 0 00-1.87-.34 1.7 1.7 0 00-1.04 1.56V19a2 2 0 11-4 0v-.09a1.7 1.7 0 00-1.04-1.56 1.7 1.7 0 00-1.87.34l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.7 1.7 0 00.34-1.87 1.7 1.7 0 00-1.56-1.04H4a2 2 0 110-4h.09a1.7 1.7 0 001.56-1.04 1.7 1.7 0 00-.34-1.87l-.06-.06a2 2 0 112.83-2.83l.06.06a1.7 1.7 0 001.87.34H10a1.7 1.7 0 001.04-1.56V4a2 2 0 114 0v.09a1.7 1.7 0 001.04 1.56 1.7 1.7 0 001.87-.34l.06-.06a2 2 0 112.83 2.83l-.06.06a1.7 1.7 0 00-.34 1.87V10a1.7 1.7 0 001.56 1.04H20a2 2 0 110 4h-.09a1.7 1.7 0 00-1.56 1.04z"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    {!! $paths[$name] ?? '' !!}
</svg>
