@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'KampusLMS' }}</title>

    {{-- Memuat font Inter dari Google Fonts untuk tipografi yang bersih --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Memuat stylesheet aplikasi konsisten dan rapi (dengan cache busting) --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ file_exists(public_path('css/style.css')) ? filemtime(public_path('css/style.css')) : time() }}">
</head>
<body class="lms-modern">

@auth
    <div class="app-shell">
        {{-- SIDEBAR KIRI --}}
        <aside class="app-sidebar">
            <div class="sidebar-top">
                {{-- 1. Logo / Nama Kampus LMS --}}
                <a href="{{ route('dashboard') }}" class="sidebar-brand" title="Kampus LMS">
                    <div class="sidebar-brand-icon">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                        </svg>
                    </div>
                    <span class="sidebar-brand-title">Kampus<br>LMS</span>
                </a>

                {{-- 2. Menu Navigasi --}}
                <ul class="sidebar-menu">
                    {{-- Dashboard --}}
                    <li>
                        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    {{-- Mata Kuliah --}}
                    <li>
                        <a href="{{ route('courses.index') }}" class="sidebar-link {{ (request()->routeIs('courses.*') || request()->routeIs('materials.*') || request()->routeIs('assignments.*') || request()->routeIs('submissions.*')) && !request()->routeIs('courses.grades') ? 'active' : '' }}" title="Mata Kuliah">
                            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                <path d="M6 6h10"/>
                                <path d="M6 10h10"/>
                            </svg>
                            <span>Matakuliah</span>
                        </a>
                    </li>

                    {{-- Nilai Saya (Khusus Mahasiswa) --}}
                    @if (auth()->user()->role === 'mahasiswa')
                        <li>
                            <a href="{{ route('grades.student') }}" class="sidebar-link {{ request()->routeIs('grades.*') ? 'active' : '' }}" title="Nilai Saya">
                                <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="20" x2="18" y2="10"/>
                                    <line x1="12" y1="20" x2="12" y2="4"/>
                                    <line x1="6" y1="20" x2="6" y2="14"/>
                                </svg>
                                <span>Nilai Saya</span>
                            </a>
                        </li>
                    @endif

                    {{-- Menu Pengguna (Khusus Admin) --}}
                    @can('viewAny', \App\Models\User::class)
                        <li>
                            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}" title="Pengguna">
                                <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                                <span>Pengguna</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>

            {{-- 3. Settings / Tentang Button di Bawah Sidebar --}}
            <div class="sidebar-bottom">
                <a href="{{ route('tentang') }}" class="sidebar-gear-btn" title="Pengaturan & Tentang">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </a>
            </div>
        </aside>

        {{-- AREA UTAMA --}}
        <div class="app-main">
            {{-- HEADER MINIMAL ATAS --}}
            <header class="app-header">
                {{-- Lonceng Notifikasi --}}
                <a href="{{ route('notifications.index') }}" class="header-btn-notification" id="notificationBellBtn" title="Pusat Pemberitahuan">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                    <span id="notificationBadge" class="header-notif-badge" style="{{ auth()->user()->unreadNotifications->count() > 0 ? 'display: inline-flex;' : 'display: none;' }}">
                        {{ auth()->user()->unreadNotifications->count() }}
                    </span>
                </a>

                {{-- Profil Mahasiswa Dinamis & Dropdown --}}
                <div class="header-user-profile" id="userProfileBtn" title="Menu Pengguna">
                    @php
                        $userNames = explode(' ', auth()->user()->name);
                        $initials = strtoupper(substr($userNames[0] ?? 'U', 0, 1) . (isset($userNames[1]) ? substr($userNames[1], 0, 1) : ''));
                    @endphp
                    <div class="header-avatar">{{ $initials }}</div>
                    <span class="header-user-name">{{ auth()->user()->name }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>

                    {{-- Dropdown Profile --}}
                    <div class="header-dropdown-menu" id="userProfileDropdown">
                        <div class="dropdown-user-info">
                            <div class="name">{{ auth()->user()->name }}</div>
                            <div class="role">
                                <span class="badge {{ auth()->user()->role === 'admin' ? 'badge-danger' : (auth()->user()->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.7rem; padding: 0.15rem 0.5rem; text-transform: capitalize;">
                                    {{ auth()->user()->role }}
                                </span>
                                @if (auth()->user()->nim_nip)
                                    <span style="font-size: 0.75rem; color: #64748b; margin-left: 0.35rem;">{{ auth()->user()->nim_nip }}</span>
                                @endif
                            </div>
                            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">{{ auth()->user()->email }}</div>
                        </div>

                        <a href="{{ route('notifications.index') }}" class="dropdown-item-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                            </svg>
                            Pemberitahuan
                        </a>

                        <a href="{{ route('tentang') }}" class="dropdown-item-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            Tentang Aplikasi
                        </a>

                        <div style="border-top: 1px solid #f1f5f9; margin-top: 0.35rem; padding-top: 0.35rem;">
                            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" class="dropdown-logout-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                        <polyline points="16 17 21 12 16 7"/>
                                        <line x1="21" y1="12" x2="9" y2="12"/>
                                    </svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="alert alert-success" style="border-radius: 16px; margin-bottom: 1.5rem; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.85rem 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-weight: 700; font-size: 1.1rem;">&#10003;</span>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger" style="border-radius: 16px; margin-bottom: 1.5rem; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.85rem 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-weight: 700; font-size: 1.1rem;">&#9888;</span>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger" style="border-radius: 16px; margin-bottom: 1.5rem; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 1rem 1.25rem;">
                    <p style="font-weight: 700; margin-bottom: 0.35rem;">Terjadi kesalahan pengisian formulir:</p>
                    <ul style="padding-left: 1.25rem; margin: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Slot Konten Halaman --}}
            <main>
                {{ $slot }}
            </main>
        </div>
    </div>
@else
    {{-- Tampilan Tamu (Login, Register, Forgot Password, dll) --}}
    <div class="guest-layout-wrapper">
        <div style="width: 100%; max-width: 1020px; margin: 0 auto; display: flex; flex-direction: column; align-items: center;">
            @if (session('success'))
                <div class="alert alert-success" style="border-radius: 16px; margin-bottom: 1.5rem; width: 100%; max-width: 980px;">
                    <span>&#10003;</span>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger" style="border-radius: 16px; margin-bottom: 1.5rem; width: 100%; max-width: 980px;">
                    <span>&#9888;</span>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
@endauth

@auth
{{-- Script Polling Notifikasi & Dropdown Menu --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Polling notifikasi berkala 30 detik
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            setInterval(function () {
                fetch('{{ route('notifications.unreadCount') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data && typeof data.unread_count !== 'undefined') {
                        if (data.unread_count > 0) {
                            badge.textContent = data.unread_count;
                            badge.style.display = 'inline-flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(() => {});
            }, 30000);
        }

        // Toggle dropdown profile
        const profileBtn = document.getElementById('userProfileBtn');
        const dropdown = document.getElementById('userProfileDropdown');
        if (profileBtn && dropdown) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!profileBtn.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }
    });
</script>
@endauth

</body>
</html>
