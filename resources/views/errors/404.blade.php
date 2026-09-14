{{-- Halaman Kustom Error 404 --}}
<x-layout title="404 - Halaman Tidak Ditemukan">

    <div class="error-container">
        <p class="error-code">404</p>
        <h1 class="error-title">Halaman Tidak Ditemukan</h1>
        <p class="error-message">
            Maaf, halaman atau data mata kuliah yang Anda cari tidak ditemukan atau alamat URL yang dituju salah.
        </p>
        <div class="error-actions">
            <a href="{{ route('home') }}" class="btn btn-primary">
                &larr; Kembali ke Dashboard
            </a>
            <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                Daftar Mata Kuliah &rarr;
            </a>
        </div>
    </div>

</x-layout>
