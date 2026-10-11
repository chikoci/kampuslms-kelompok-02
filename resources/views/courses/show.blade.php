{{-- Halaman Detail Mata Kuliah --}}
<x-layout title="Detail: {{ $course->name }}">

    <div class="header-actions-row">
        <a href="{{ route('courses.index') }}" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Daftar
        </a>
        <div class="header-actions-group">
            @can('update', $course)
                <a href="{{ route('courses.edit', $course) }}" class="btn btn-edit btn-sm">
                    Edit Mata Kuliah
                </a>
            @endcan
            @can('delete', $course)
                <form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Hapus mata kuliah ini beserta seluruh materi dan tugasnya?');" class="inline-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        Hapus
                    </button>
                </form>
            @endcan
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
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <h2 class="section-title">Materi Perkuliahan</h2>
                <span class="section-counter">Total: {{ $course->materials->count() }} Berkas</span>
            </div>
            @can('create', [\App\Models\Material::class, $course])
                <a href="{{ route('courses.materials.create', $course) }}" class="btn btn-primary btn-sm">
                    + Tambah Materi
                </a>
            @endcan
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-title-wide">Judul Materi</th>
                        <th>Keterangan</th>
                        <th class="col-file-badge">Berkas</th>
                        <th class="col-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($course->materials as $material)
                        <tr>
                            <td class="font-semibold text-body">{{ $material->title }}</td>
                            <td class="text-muted">{{ $material->description ?? '-' }}</td>
                            <td>
                                <a href="{{ route('materials.download', $material) }}" class="badge badge-file" style="text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="Klik untuk mengunduh berkas">
                                    &#128190; {{ $material->original_name ?? 'Unduh Berkas' }}
                                </a>
                            </td>
                            <td class="action-buttons-cell">
                                <div class="action-buttons-group">
                                    @can('update', $material)
                                        <a href="{{ route('materials.edit', $material) }}" class="btn btn-edit btn-sm">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('delete', $material)
                                        <form action="{{ route('materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus materi ini?');" class="inline-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-cell-sm">
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
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <h2 class="section-title">Tugas Akademik</h2>
                <span class="section-counter">Total: {{ $course->assignments->count() }} Tugas</span>
            </div>
            @can('create', [\App\Models\Assignment::class, $course])
                <a href="{{ route('courses.assignments.create', $course) }}" class="btn btn-primary btn-sm">
                    + Tambah Tugas
                </a>
            @endcan
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-title-wide">Judul Tugas</th>
                        <th class="col-due-date">Batas Waktu (Due At)</th>
                        <th class="col-status">Status</th>
                        <th class="col-submissions">
                            @if(auth()->user()?->role === 'mahasiswa')
                                Status Tugas Anda
                            @else
                                Pengumpulan
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($course->assignments as $assignment)
                        <tr>
                            <td class="font-semibold text-body">
                                <a href="{{ route('assignments.show', $assignment) }}" style="color: #2563eb; text-decoration: none; font-weight: 600;">
                                    {{ $assignment->title }} &rarr;
                                </a>
                            </td>
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
                                @if (auth()->user()?->role === 'mahasiswa')
                                    @php
                                        $mySub = $assignment->submissions->firstWhere('user_id', auth()->id());
                                    @endphp
                                    @if ($mySub)
                                        <a href="{{ route('submissions.show', $mySub) }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Klik untuk melihat detail pengumpulan Anda">
                                            <span class="badge badge-active" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                                &#10003; Sudah Dikumpulkan
                                            </span>
                                        </a>
                                    @else
                                        <a href="{{ route('assignments.show', $assignment) }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Klik untuk mengumpulkan tugas">
                                            <span class="badge badge-overdue" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                                Belum Mengumpulkan &rarr;
                                            </span>
                                        </a>
                                    @endif
                                @else
                                    <a href="{{ route('assignments.submissions.index', $assignment) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">
                                        {{ $assignment->submissions()->count() }} Pengumpulan &rarr;
                                    </a>
                                @endif
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
