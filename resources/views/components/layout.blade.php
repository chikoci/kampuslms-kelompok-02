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

    <style>
        /* Reset dan base */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            background-color: #1e293b;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar-inner {
            max-width: 960px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
        }
        .navbar-brand {
            color: #f8fafc;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            letter-spacing: -0.02em;
        }
        .navbar-nav {
            display: flex;
            gap: 0.25rem;
            list-style: none;
        }
        .navbar-nav a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: color 0.15s, background-color 0.15s;
        }
        .navbar-nav a:hover {
            color: #f8fafc;
            background-color: #334155;
        }
        .navbar-nav a.active {
            color: #f8fafc;
            background-color: #334155;
        }

        /* Konten utama */
        .main-content {
            max-width: 960px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Utilitas */
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .page-header p {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Card */
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem;
            transition: box-shadow 0.15s;
        }
        .card:hover {
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .card a {
            text-decoration: none;
            color: inherit;
        }

        /* Badge */
        .badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            background-color: #e2e8f0;
            color: #475569;
        }

        /* Tombol */
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.15s;
            cursor: pointer;
            border: none;
        }
        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background-color: #cbd5e1;
        }
        .btn-primary {
            background-color: #1e293b;
            color: #f8fafc;
        }
        .btn-primary:hover {
            background-color: #334155;
        }

        /* Footer */
        .footer {
            max-width: 960px;
            margin: 3rem auto 1.5rem;
            padding: 1rem 1.5rem;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

    {{-- Navbar global --}}
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="{{ route('home') }}" class="navbar-brand">KampusLMS</a>
            <ul class="navbar-nav">
                <li><a href="{{ route('home') }}" class="{{ request()->is('/') ? 'active' : '' }}">Dashboard</a></li>
                <li><a href="{{ route('courses.index') }}" class="{{ request()->is('courses*') ? 'active' : '' }}">Mata Kuliah</a></li>
                <li><a href="{{ route('tentang') }}" class="{{ request()->is('tentang') ? 'active' : '' }}">Tentang</a></li>
            </ul>
        </div>
    </nav>

    {{-- Slot konten halaman --}}
    <main class="main-content">
        {{ $slot }}
    </main>

    <footer class="footer">
        KampusLMS &copy; 2025 - Kelompok 02. Dibuat dengan Laravel 12.
    </footer>

</body>
</html>
