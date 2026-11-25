@php
    $unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp

{{-- Lonceng Notifikasi --}}
<a href="{{ route('notifications.index') }}" class="header-btn-notification" id="notificationBellBtn" title="Pusat Pemberitahuan">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
    </svg>
    <span id="notificationBadge" class="header-notif-badge" style="{{ $unreadCount > 0 ? 'display: inline-flex;' : 'display: none;' }}">
        {{ $unreadCount }}
    </span>
</a>
