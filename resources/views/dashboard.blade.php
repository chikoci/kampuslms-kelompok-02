{{-- Halaman Dashboard --}}
<x-layout title="Dashboard">

    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Selamat datang di Sistem Manajemen Pembelajaran Kampus</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">

        {{-- Kartu Mata Kuliah --}}
        <a href="{{ route('courses.index') }}" style="text-decoration: none;">
            <div class="card" style="border-left: 3px solid #3b82f6;">
                <p style="font-size: 0.75rem; font-weight: 600; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Mata Kuliah</p>
                <p style="font-size: 1.5rem; font-weight: 700; color: #0f172a;">3</p>
                <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">mata kuliah tersedia</p>
            </div>
        </a>

        {{-- Kartu Tugas (placeholder) --}}
        <div class="card" style="border-left: 3px solid #10b981; opacity: 0.6;">
            <p style="font-size: 0.75rem; font-weight: 600; color: #10b981; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Tugas</p>
            <p style="font-size: 1.5rem; font-weight: 700; color: #0f172a;">-</p>
            <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">segera hadir</p>
        </div>

        {{-- Kartu Nilai (placeholder) --}}
        <div class="card" style="border-left: 3px solid #f59e0b; opacity: 0.6;">
            <p style="font-size: 0.75rem; font-weight: 600; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Nilai</p>
            <p style="font-size: 1.5rem; font-weight: 700; color: #0f172a;">-</p>
            <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">segera hadir</p>
        </div>

    </div>

</x-layout>
