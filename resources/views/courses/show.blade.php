{{-- Halaman Detail Mata Kuliah --}}
<x-layout title="Detail: {{ $course->name }} - KampusLMS">

    {{-- Navigasi Kembali & Aksi Dosen/Admin --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <a href="{{ route('courses.index') }}" class="btn-open-class" style="border-radius: 9999px;">
            &larr; Kembali ke Daftar Mata Kuliah
        </a>

        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            @can('update', $course)
                <a href="{{ route('courses.grades', $course) }}" class="btn-open-class" style="background: #ecfdf5; color: #047857; border-color: #a7f3d0;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Rekap Nilai Kelas
                </a>
                <a href="{{ route('courses.edit', $course) }}" class="btn-open-class" style="color: #d97706; border-color: #fde68a;">
                    Edit Mata Kuliah
                </a>
            @endcan
            @can('delete', $course)
                <form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Hapus mata kuliah ini beserta seluruh materi dan tugasnya?');" style="margin: 0; display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-open-class" style="color: #dc2626; border-color: #fecaca;">
                        Hapus
                    </button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Header Banner Mata Kuliah --}}
    <div class="card-modern" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem;">
                    <span class="badge-code-modern" style="font-size: 0.8rem; padding: 0.25rem 0.65rem;">{{ $course->code }}</span>
                    <span class="badge-status-dot {{ $course->status }}">
                        <span class="dot"></span>
                        {{ ucfirst($course->status) }}
                    </span>
                </div>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin: 0 0 0.5rem 0; letter-spacing: -0.01em;">
                    {{ $course->name }}
                </h1>
                <p style="color: #64748b; font-size: 0.95rem; margin: 0; max-width: 800px; line-height: 1.6;">
                    {{ $course->description ?: 'Tidak ada deskripsi yang dicantumkan untuk mata kuliah ini.' }}
                </p>
            </div>
        </div>

        {{-- 3 Metrik Utama Kelas --}}
        <div class="detail-meta-grid" style="margin-top: 1.5rem; margin-bottom: 0;">
            <div class="detail-meta-box">
                <div class="lbl">Dosen Pengampu</div>
                <div class="val">{{ $course->lecturer?->name ?? 'Belum Ditugaskan' }}</div>
                <div class="sub">NIP: {{ $course->lecturer?->nim_nip ?? '-' }}</div>
            </div>

            <div class="detail-meta-box">
                <div class="lbl">Beban SKS</div>
                <div class="val">{{ $course->sks }} <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">SKS</span></div>
                <div class="sub">Bobot Akademik Semester</div>
            </div>

            <div class="detail-meta-box">
                <div class="lbl">Mahasiswa Terdaftar</div>
                <div class="val">{{ $course->students()->count() }} <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Orang</span></div>
                <div class="sub">Peserta Kelas Aktif</div>
            </div>
        </div>
    </div>

    {{-- SECTION 1: MATERI PERKULIAHAN --}}
    <div class="card-modern" style="margin-bottom: 1.5rem;">
        <div class="card-header-row">
            <h3 class="card-header-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                    <path d="M6 6h10"/>
                    <path d="M6 10h10"/>
                </svg>
                Materi Perkuliahan
                <span style="font-size: 0.75rem; font-weight: 600; background: #eff6ff; color: #2563eb; padding: 0.2rem 0.55rem; border-radius: 9999px;">
                    {{ $course->materials->count() }} Berkas
                </span>
            </h3>

            @can('create', [\App\Models\Material::class, $course])
                <a href="{{ route('courses.materials.create', $course) }}" class="btn-open-class" style="background: #2563eb; color: #ffffff; border-color: #2563eb;">
                    + Tambah Materi
                </a>
            @endcan
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @forelse ($course->materials as $material)
                <div class="course-card-item" style="padding: 1rem 1.25rem;">
                    <div class="course-info-col">
                        <h4 class="course-title" style="font-size: 1rem;">
                            <a href="{{ route('materials.show', $material) }}" style="color: inherit; text-decoration: none;">
                                {{ $material->title }}
                            </a>
                        </h4>
                        <div class="course-meta-text">
                            <span>{{ $material->description ?: 'Tanpa keterangan tambahan.' }}</span>
                            <span>&bull;</span>
                            <span>{{ $material->created_at?->format('d M Y') }}</span>
                        </div>
                    </div>

                    <div class="course-actions-col">
                        @if($material->type === 'link' && $material->external_url)
                            <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="btn-open-class" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                </svg>
                                Buka Tautan &rarr;
                            </a>
                        @elseif($material->file_path)
                            <a href="{{ route('materials.download', $material) }}" class="btn-open-class" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Unduh ({{ $material->formatted_file_size }})
                            </a>
                        @else
                            <span class="badge" style="background: #f1f5f9; color: #64748b;">Catatan Teks</span>
                        @endif

                        @can('update', $material)
                            <a href="{{ route('materials.edit', $material) }}" class="btn-open-class" style="color: #d97706; border-color: #fde68a;">Edit</a>
                        @endcan
                        @can('delete', $material)
                            <form action="{{ route('materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus materi ini?');" style="margin: 0; display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-open-class" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="empty-state-card" style="padding: 2rem 1rem;">
                    <p class="empty-state-text">Belum ada materi perkuliahan untuk mata kuliah ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- SECTION 2: TUGAS AKADEMIK --}}
    <div class="card-modern" style="margin-bottom: 1.5rem;">
        <div class="card-header-row">
            <h3 class="card-header-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Tugas Akademik
                <span style="font-size: 0.75rem; font-weight: 600; background: #eff6ff; color: #2563eb; padding: 0.2rem 0.55rem; border-radius: 9999px;">
                    {{ $course->assignments->count() }} Tugas
                </span>
            </h3>

            @can('create', [\App\Models\Assignment::class, $course])
                <a href="{{ route('courses.assignments.create', $course) }}" class="btn-open-class" style="background: #2563eb; color: #ffffff; border-color: #2563eb;">
                    + Tambah Tugas
                </a>
            @endcan
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @forelse ($course->assignments as $assignment)
                <div class="course-card-item" style="padding: 1.15rem 1.25rem;">
                    <div class="course-info-col">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                            @if ($assignment->due_at?->isPast())
                                <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.7rem; font-weight: 700; border-radius: 6px; padding: 0.15rem 0.5rem;">
                                    Lewat Tenggat
                                </span>
                            @else
                                <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 0.7rem; font-weight: 700; border-radius: 6px; padding: 0.15rem 0.5rem;">
                                    Aktif
                                </span>
                            @endif
                            <span class="badge-status-dot {{ $assignment->status }}" style="font-size: 0.7rem; padding: 0.15rem 0.5rem;">
                                {{ ucfirst($assignment->status) }}
                            </span>
                        </div>

                        <h4 class="course-title" style="font-size: 1rem;">
                            <a href="{{ route('assignments.show', $assignment) }}" style="color: inherit; text-decoration: none;">
                                {{ $assignment->title }}
                            </a>
                        </h4>

                        <div class="course-meta-text">
                            <span>Batas Waktu: {{ $assignment->due_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                            <span>&bull;</span>
                            <span>Maks Nilai: {{ $assignment->max_score }}</span>
                        </div>
                    </div>

                    <div class="course-actions-col">
                        @if (auth()->user()?->role === 'mahasiswa')
                            @php
                                $mySub = $assignment->submissions->firstWhere('user_id', auth()->id());
                            @endphp
                            @if ($mySub)
                                <a href="{{ route('submissions.show', $mySub) }}" class="btn-open-class" style="background: #ecfdf5; color: #047857; border-color: #a7f3d0;">
                                    &#10003; Sudah Dikumpulkan
                                </a>
                            @else
                                <a href="{{ route('assignments.show', $assignment) }}" class="btn-open-class" style="background: #fffbeb; color: #b45309; border-color: #fde68a;">
                                    Kumpulkan Tugas &rarr;
                                </a>
                            @endif
                        @else
                            <a href="{{ route('assignments.submissions.index', $assignment) }}" class="btn-open-class">
                                {{ $assignment->submissions()->count() }} Pengumpulan &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state-card" style="padding: 2rem 1rem;">
                    <p class="empty-state-text">Belum ada tugas perkuliahan untuk mata kuliah ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- SECTION 3: PESERTA MAHASISWA TERDAFTAR --}}
    <div class="card-modern">
        <div class="card-header-row" style="flex-wrap: wrap; gap: 0.75rem;">
            <h3 class="card-header-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Peserta Mahasiswa Terdaftar
                <span style="font-size: 0.75rem; font-weight: 600; background: #eff6ff; color: #2563eb; padding: 0.2rem 0.55rem; border-radius: 9999px;">
                    {{ $course->students->count() }} Orang
                </span>
            </h3>

            @can('enroll', $course)
                @if(isset($availableStudents) && $availableStudents->isNotEmpty())
                    <form action="{{ route('courses.enroll', $course) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin: 0;">
                        @csrf
                        <select name="student_id" required class="filter-select-modern" style="padding: 0.45rem 0.85rem; font-size: 0.8rem; max-width: 260px;">
                            <option value="">-- Pilih Mahasiswa --</option>
                            @foreach($availableStudents as $mhs)
                                <option value="{{ $mhs->id }}">{{ $mhs->name }} ({{ $mhs->nim_nip ?? 'NIM -' }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="filter-btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem;">
                            + Daftarkan
                        </button>
                    </form>
                @endif
            @endcan
        </div>

        @if ($course->students->isEmpty())
            <div class="empty-state-card" style="padding: 2rem 1rem;">
                <p class="empty-state-text">Belum ada mahasiswa yang terdaftar di kelas ini.</p>
            </div>
        @else
            <div style="overflow-x: auto; border: 1px solid #eef2f6; border-radius: 16px;">
                <table class="grade-table">
                    <thead>
                        <tr>
                            <th style="width: 140px;">NIM</th>
                            <th>Nama Lengkap</th>
                            <th>Terdaftar Sejak</th>
                            @can('unenroll', $course)
                                <th style="text-align: right;">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($course->students as $student)
                            <tr>
                                <td style="font-weight: 700; color: #64748b; font-size: 0.85rem;">
                                    {{ $student->nim_nip ?? '-' }}
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    {{ $student->name }}
                                </td>
                                <td style="color: #64748b; font-size: 0.85rem;">
                                    {{ $student->pivot->enrolled_at ? \Carbon\Carbon::parse($student->pivot->enrolled_at)->translatedFormat('d M Y, H:i') : '-' }}
                                </td>
                                @can('unenroll', $course)
                                    <td style="text-align: right;">
                                        <form action="{{ route('courses.unenroll', [$course, $student]) }}" method="POST" onsubmit="return confirm('Keluarkan mahasiswa {{ $student->name }} dari mata kuliah ini?');" style="margin: 0; display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-open-class" style="color: #dc2626; border-color: #fecaca; padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                                                Keluarkan
                                            </button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-layout>
