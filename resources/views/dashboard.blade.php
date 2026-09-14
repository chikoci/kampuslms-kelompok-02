{{-- Halaman Dashboard --}}
<x-layout title="Dashboard">

    <div class="page-header">
        <h1>Dashboard Akademik</h1>
        <p>Selamat datang di Sistem Informasi Pembelajaran KampusLMS</p>
    </div>

    <div class="metric-grid">

        {{-- Kartu Mata Kuliah --}}
        <a href="{{ route('courses.index') }}" class="dashboard-card-link">
            <div class="card card-accent-blue">
                <p class="metric-label label-blue">Mata Kuliah</p>
                <p class="metric-value">{{ \App\Models\Course::count() }}</p>
                <p class="metric-sub">Mata kuliah aktif di database</p>
            </div>
        </a>

        {{-- Kartu Pengguna --}}
        <a href="{{ route('users.index') }}" class="dashboard-card-link">
            <div class="card card-accent-green">
                <p class="metric-label label-green">Pengguna</p>
                <p class="metric-value">{{ \App\Models\User::count() }}</p>
                <p class="metric-sub">Akun administrator, dosen & mahasiswa</p>
            </div>
        </a>

        {{-- Kartu Tugas --}}
        <div class="card card-accent-amber">
            <p class="metric-label label-amber">Tugas Akademik</p>
            <p class="metric-value">{{ \App\Models\Assignment::count() }}</p>
            <p class="metric-sub">Tugas aktif hasil seeder</p>
        </div>

    </div>

</x-layout>
