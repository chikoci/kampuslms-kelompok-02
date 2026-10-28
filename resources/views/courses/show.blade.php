{{-- Halaman Detail Mata Kuliah --}}
<x-layout title="Detail: {{ $course->name }}">

    <div class="header-actions-row">
        <a href="{{ route('courses.index') }}" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Daftar
        </a>
        <div class="header-actions-group">
            @can('update', $course)
                <a href="{{ route('courses.grades', $course) }}" class="btn btn-sm" style="background-color: #059669; color: #ffffff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                    &#128202; Rekap Nilai Kelas
                </a>
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
                                @if($material->type === 'link' && $material->external_url)
                                    <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Buka tautan materi eksternal">
                                        &#128279; Tautan Eksternal &rarr;
                                    </a>
                                @elseif($material->file_path)
                                    <a href="{{ route('materials.download', $material) }}" class="badge badge-file" style="text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="Klik untuk mengunduh berkas">
                                        &#128190; {{ $material->original_name ?? 'Unduh Berkas' }}
                                        <span style="font-size: 0.75rem; opacity: 0.85;">({{ $material->formatted_file_size }})</span>
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">Catatan Teks</span>
                                @endif
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

    {{-- Tabel Mahasiswa Terdaftar (Peserta Kelas) & Form Pendaftaran --}}
    <div class="section-block" style="margin-top: 2rem;">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <h2 class="section-title">Peserta Mahasiswa Terdaftar</h2>
                <span class="section-counter">Total: {{ $course->students->count() }} Mahasiswa</span>
            </div>
            @can('enroll', $course)
                @if(isset($availableStudents) && $availableStudents->isNotEmpty())
                    <form action="{{ route('courses.enroll', $course) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        @csrf
                        <select name="student_id" required class="form-control" style="padding: 0.375rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; max-width: 280px;">
                            <option value="">-- Pilih Mahasiswa Baru --</option>
                            @foreach($availableStudents as $mhs)
                                <option value="{{ $mhs->id }}">{{ $mhs->name }} ({{ $mhs->nim_nip ?? 'NIM -' }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">
                            + Daftarkan ke Kelas
                        </button>
                    </form>
                @endif
            @endcan
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 150px;">NIM</th>
                        <th>Nama Lengkap</th>
                        <th style="width: 200px;">Terdaftar Sejak</th>
                        @can('unenroll', $course)
                            <th class="col-actions" style="width: 120px;">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($course->students as $student)
                        <tr>
                            <td class="text-muted" style="font-size: 0.85rem;">{{ $student->nim_nip ?? '-' }}</td>
                            <td class="font-semibold text-body">{{ $student->name }}</td>
                            <td class="text-muted" style="font-size: 0.85rem;">
                                {{ $student->pivot->enrolled_at ? \Carbon\Carbon::parse($student->pivot->enrolled_at)->format('d M Y, H:i') : '-' }}
                            </td>
                            @can('unenroll', $course)
                                <td class="action-buttons-cell">
                                    <form action="{{ route('courses.unenroll', [$course, $student]) }}" method="POST" onsubmit="return confirm('Keluarkan mahasiswa {{ $student->name }} dari mata kuliah ini?');" class="inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 2px 8px;">
                                            Keluarkan
                                        </button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->can('unenroll', $course) ? 4 : 3 }}" class="empty-cell-sm">
                                Belum ada mahasiswa yang terdaftar di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-layout>
