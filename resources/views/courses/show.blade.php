{{-- Halaman Detail Mata Kuliah --}}
<x-layout title="Detail: {{ $course->name }}">

    <div class="header-actions-row">
        <a href="{{ route('courses.index') }}" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Daftar
        </a>
        <div class="header-actions-group">
            <a href="{{ route('courses.edit', $course) }}" class="btn btn-edit btn-sm">
                Edit Mata Kuliah
            </a>
            <form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Hapus mata kuliah ini beserta seluruh materi dan tugasnya?');" class="inline-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <div class="page-header">
        <div class="title-badge-group">
            <h1 class="page-title-lg">{{ $course->name }}</h1>
            <span class="badge badge-code">{{ $course->code }}</span>
            <span class="badge badge-{{ $course->status }}">{{ ucfirst($course->status) }}</span>
        </div>
    </div>

    {{-- Grid Kartu Metrik --}}
    <div class="metric-grid">
        <div class="metric-card">
            <p class="metric-label">Dosen Pengampu</p>
            <p class="metric-value font-medium">{{ $course->lecturer?->name ?? 'Belum Ditugaskan' }}</p>
            <p class="metric-sub">{{ $course->lecturer?->nim_nip ?? 'NIP Belum Ada' }}</p>
        </div>

        <div class="metric-card">
            <p class="metric-label">Beban SKS</p>
            <p class="metric-value">{{ $course->sks }} <span class="metric-unit">SKS</span></p>
            <p class="metric-sub">Bobot Akademik</p>
        </div>

        <div class="metric-card">
            <p class="metric-label">Mahasiswa Terdaftar</p>
            <p class="metric-value">{{ $course->students()->count() }} <span class="metric-unit">Orang</span></p>
            <p class="metric-sub">Peserta Aktif</p>
        </div>
    </div>

    {{-- Kartu Deskripsi --}}
    <div class="card">
        <p class="metric-label">Deskripsi Mata Kuliah</p>
        <p class="card-description-text">
            {{ $course->description ?? 'Tidak ada deskripsi yang dicantumkan untuk mata kuliah ini.' }}
        </p>
    </div>

    {{-- Tabel Materi Perkuliahan --}}
    <div class="section-block">
        <div class="section-header">
            <h2 class="section-title">Materi Perkuliahan</h2>
            <span class="section-counter">Total: {{ $course->materials->count() }} Berkas</span>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-title-wide">Judul Materi</th>
                        <th>Keterangan</th>
                        <th class="col-file-badge">Berkas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($course->materials as $material)
                        <tr>
                            <td class="font-semibold text-body">{{ $material->title }}</td>
                            <td class="text-muted">{{ $material->description ?? '-' }}</td>
                            <td>
                                <span class="badge badge-file">
                                    {{ $material->original_name ?? 'PDF' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-cell-sm">
                                Belum ada materi perkuliahan untuk mata kuliah ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabel Tugas Akademik --}}
    <div>
        <div class="section-header">
            <h2 class="section-title">Tugas Akademik</h2>
            <span class="section-counter">Total: {{ $course->assignments->count() }} Tugas</span>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-title-wide">Judul Tugas</th>
                        <th class="col-due-date">Batas Waktu (Due At)</th>
                        <th class="col-status">Status</th>
                        <th class="col-submissions">Pengumpulan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($course->assignments as $assignment)
                        <tr>
                            <td class="font-semibold text-body">{{ $assignment->title }}</td>
                            <td class="text-body">
                                {{ $assignment->due_at?->format('d M Y, H:i') ?? '-' }}
                                @if ($assignment->due_at?->isPast())
                                    <span class="badge badge-overdue">Lewat</span>
                                @else
                                    <span class="badge badge-upcoming">Aktif</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $assignment->status }}">
                                    {{ ucfirst($assignment->status) }}
                                </span>
                            </td>
                            <td class="text-right font-semibold text-body">
                                {{ $assignment->submissions()->count() }} Pengumpulan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-cell-sm">
                                Belum ada tugas untuk mata kuliah ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-layout>
