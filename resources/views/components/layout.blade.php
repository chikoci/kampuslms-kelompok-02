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

    {{-- Memuat aset CSS dan JS melalui Vite (standar Laravel 12) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Memuat stylesheet aplikasi konsisten dan rapi --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    {{-- Navbar global --}}
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="{{ route('home') }}" class="navbar-brand">KampusLMS</a>
            <ul class="navbar-nav">
                <li><a href="{{ route('home') }}" class="{{ request()->is('/') ? 'active' : '' }}">Dashboard</a></li>
                <li><a href="{{ route('courses.index') }}" class="{{ request()->is('courses*') ? 'active' : '' }}">Mata Kuliah</a></li>
                <li><a href="{{ route('users.index') }}" class="{{ request()->is('users*') ? 'active' : '' }}">Pengguna</a></li>
                <li><a href="{{ route('tentang') }}" class="{{ request()->is('tentang') ? 'active' : '' }}">Tentang</a></li>
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

</body>
</html>
