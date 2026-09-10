{{-- Halaman Detail Mata Kuliah --}}
<x-layout title="Detail: {{ $course['name'] }}">

    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('courses.index') }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">&larr; Kembali</a>
    </div>

    <div class="page-header">
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <h1>{{ $course['name'] }}</h1>
            <span class="badge">{{ $course['code'] }}</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">

        <div class="card">
            <p style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Dosen Pengampu</p>
            <p style="font-weight: 600; color: #0f172a;">{{ $course['lecturer'] }}</p>
        </div>

        <div class="card">
            <p style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Beban SKS</p>
            <p style="font-weight: 600; color: #0f172a;">{{ $course['sks'] }} SKS</p>
        </div>

    </div>

    <div class="card">
        <p style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Deskripsi</p>
        <p style="color: #334155; line-height: 1.7;">{{ $course['description'] }}</p>
    </div>

</x-layout>
