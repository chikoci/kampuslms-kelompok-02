{{-- Halaman Kustom Error 404 --}}
<x-layout title="404 - Halaman Tidak Ditemukan">

    <div style="text-align: center; padding: 4rem 1rem;">
        <p style="font-size: 4.5rem; font-weight: 800; color: #94a3b8; line-height: 1; margin-bottom: 0.75rem;">404</p>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">Halaman Tidak Ditemukan</h1>
        <p style="color: #64748b; font-size: 0.9rem; max-width: 460px; margin: 0 auto 1.75rem;">
            Maaf, halaman atau data mata kuliah yang Anda cari tidak ditemukan atau alamat URL yang dituju salah.
        </p>
        <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('home') }}" class="btn btn-primary">
                &larr; Kembali ke Dashboard
            </a>
            <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                Daftar Mata Kuliah &rarr;
            </a>
        </div>
    </div>

</x-layout>
