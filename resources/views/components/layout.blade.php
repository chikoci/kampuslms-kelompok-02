<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'KampusLMS' }}</title>

    {{-- Memuat font Inter dari Google Fonts untuk tipografi yang bersih --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Memuat stylesheet aplikasi konsisten dan rapi (dengan cache busting) --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ file_exists(public_path('css/style.css')) ? filemtime(public_path('css/style.css')) : time() }}">
</head>
<body>

    {{-- Navbar global --}}
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="navbar-brand">
                <span>🎓</span> KampusLMS
            </a>
            <ul class="navbar-nav">
                @auth
                    <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a></li>
                    <li><a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*') ? 'active' : '' }}">Mata Kuliah</a></li>
                    
                    @can('viewAny', \App\Models\User::class)
                        <li><a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Pengguna</a></li>
                    @endcan

                    @if (auth()->user()->role === 'mahasiswa')
                        <li><a href="{{ route('grades.student') }}" class="{{ request()->routeIs('grades.*') ? 'active' : '' }}">Nilai Saya</a></li>
                    @endif

                    <li><a href="{{ route('tentang') }}" class="{{ request()->routeIs('tentang') ? 'active' : '' }}">Tentang</a></li>

                    {{-- Lonceng Notifikasi Asinkron --}}
                    <li style="display: flex; align-items: center; margin-left: 0.5rem;">
                        <a href="{{ route('notifications.index') }}" id="notificationBellBtn" title="Pusat Notifikasi" style="position: relative; display: inline-flex; align-items: center; justify-content: center; padding: 0.35rem 0.5rem; text-decoration: none; color: #f8fafc; font-size: 1.15rem; border-radius: 6px; transition: background 0.15s ease;">
                            🔔
                            <span id="notificationBadge" style="{{ auth()->user()->unreadNotifications->count() > 0 ? 'display: inline-flex;' : 'display: none;' }} position: absolute; top: -3px; right: -3px; background: #ef4444; color: #ffffff; font-size: 0.65rem; font-weight: 700; border-radius: 9999px; min-width: 17px; height: 17px; align-items: center; justify-content: center; padding: 0 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.2);">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        </a>
                    </li>

                    {{-- Informasi Profil & Logout --}}
                    <li style="display: flex; align-items: center; gap: 0.75rem; margin-left: 0.5rem; padding-left: 0.75rem; border-left: 1px solid rgba(255,255,255,0.15);">
                        <div style="display: flex; flex-direction: column; align-items: flex-end; line-height: 1.2;">
                            <span style="font-size: 0.85rem; color: #f8fafc; font-weight: 600;">{{ auth()->user()->name }}</span>
                            <span class="badge {{ auth()->user()->role === 'admin' ? 'badge-danger' : (auth()->user()->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">
                                {{ ucfirst(auth()->user()->role) }}
                            </span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; background: rgba(255,255,255,0.1); color: #f8fafc; border: 1px solid rgba(255,255,255,0.2);">
                                Keluar
                            </button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('tentang') }}" class="{{ request()->routeIs('tentang') ? 'active' : '' }}">Tentang</a></li>
                    <li>
                        <a href="{{ route('login') }}" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.85rem; color: #ffffff;">
                            Masuk
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </nav>

    {{-- Slot konten halaman --}}
    <main class="main-content">
        {{-- Flash message sukses --}}
        @if (session('success'))
            <div class="alert alert-success">
                <span>&#10003;</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        {{-- Flash message gagal / error --}}
        @if (session('error'))
            <div class="alert alert-danger">
                <span>&#9888;</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        {{-- Flash message validasi error --}}
        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger">
                <div>
                    <p class="alert-title">Terjadi kesalahan pengisian formulir:</p>
                    <ul class="alert-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="footer">
        KampusLMS &copy; 2025 - Kelompok 02. Dibuat dengan Laravel 12.
    </footer>

    @auth
    {{-- Polling Otomatis Status Notifikasi (30 Detik) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const badge = document.getElementById('notificationBadge');
            if (!badge) return;

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
        });
    </script>
    @endauth

</body>
</html>
