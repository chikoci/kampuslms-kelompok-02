{{-- Halaman Detail Mata Kuliah --}}
<x-layout title="Detail: {{ $course->name }} - KampusLMS"
    :backUrl="route('courses.index')"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
        ['label' => $course->name]
    ]">

    {{-- Header Banner Mata Kuliah --}}
    <div class="card-modern" style="margin-bottom: 1.5rem;">
        @can('update', $course)
            {{-- Toolbar Aksi Dosen/Admin --}}
            <div class="course-show-toolbar" style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 0.5rem;">
                <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                    <a href="{{ route('courses.submissions', $course) }}" class="btn-open-class" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe; font-weight: 600;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        Pengumpulan
                    </a>
                    <a href="{{ route('courses.grades', $course) }}" class="btn-open-class" style="background: #ecfdf5; color: #047857; border-color: #a7f3d0; font-weight: 600;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                        Nilai
                    </a>
                    <a href="{{ route('courses.edit', $course) }}" class="btn-icon-action edit" style="width: 30px; height: 30px;" title="Edit Mata Kuliah">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </a>
                    @can('delete', $course)
                        <form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Hapus mata kuliah ini beserta seluruh materi dan tugasnya?');" style="margin: 0; display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-action delete" style="width: 30px; height: 30px;" title="Hapus Mata Kuliah">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        @endcan
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

            <div class="detail-meta-box detail-meta-box-clickable" onclick="togglePesertaView()" role="button" tabindex="0" title="Klik untuk melihat peserta kelas" style="cursor: pointer;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                    <div class="lbl" style="margin: 0;">Mahasiswa Terdaftar</div>
                    <span class="badge" id="badgeLihatPeserta" style="background: #eff6ff; color: #2563eb; font-size: 0.68rem; font-weight: 700; padding: 0.12rem 0.45rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.25rem;">
                        <span id="badgeLihatPesertaText">Lihat Peserta</span>
                        <svg id="badgeLihatPesertaIcon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.2s ease;"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>
                </div>
                <div class="val">{{ $course->students()->count() }} <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Orang</span></div>
                <div class="sub">Peserta Kelas Aktif</div>
            </div>
        </div>
    </div>

    {{-- FULL VIEW PESERTA MAHASISWA TERDAFTAR (IN-PAGE TABEL SESUAI REFERENSI) --}}
    <div id="pesertaFullSection" class="card-modern" style="display: none; margin-bottom: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04); animation: fadeIn 0.2s ease;">
        <div class="card-header-row" style="margin-bottom: 0.85rem; padding-bottom: 0.65rem; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <h3 class="card-header-title" style="margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;">
                    Daftar Mahasiswa Terdaftar
                </h3>
                <span style="font-size: 0.72rem; font-weight: 700; background: #eff6ff; color: #2563eb; padding: 0.15rem 0.5rem; border-radius: 9999px;">
                    {{ $course->students()->count() }} Mahasiswa
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="text" id="searchPesertaInput" onkeyup="filterPesertaList()" placeholder="Cari nama atau NIM..." style="padding: 0.35rem 0.65rem; font-size: 0.78rem; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; background: #f8fafc; max-width: 200px;">
                <button type="button" onclick="togglePesertaView()" class="btn btn-secondary btn-sm" style="font-size: 0.78rem; padding: 0.25rem 0.65rem; display: inline-flex; align-items: center; gap: 0.3rem;" title="Tutup Tampilan Peserta">
                    <span>Tutup</span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        @php
            $allCourseStudents = $course->students()->orderBy('name')->get();
        @endphp

        @if ($allCourseStudents->isEmpty())
            <div style="text-align: center; padding: 2rem 1rem; color: #64748b; font-size: 0.85rem;">
                Belum ada mahasiswa yang terdaftar di kelas ini.
            </div>
        @else
            {{-- Tabel Peserta Bersih Sesuai Referensi Gambar --}}
            <div style="overflow-x: auto; border: 1px solid #eef2f6; border-radius: 12px; background: #ffffff;">
                <table class="grade-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #eef2f6;">
                            <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; font-weight: 700; color: #475569; width: 180px;">NIM</th>
                            <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; font-weight: 700; color: #475569;">Nama Lengkap</th>
                            <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; font-weight: 700; color: #475569; width: 220px;">Terdaftar Sejak</th>
                        </tr>
                    </thead>
                    <tbody id="pesertaTableBody">
                        @foreach ($allCourseStudents as $student)
                            <tr class="peserta-table-row" data-name="{{ strtolower($student->name) }}" data-nim="{{ strtolower($student->nim_nip ?? '') }}" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                                <td style="padding: 0.85rem 1rem; font-weight: 700; color: #475569; font-size: 0.85rem; font-family: monospace;">
                                    {{ $student->nim_nip ?? '-' }}
                                </td>
                                <td style="padding: 0.85rem 1rem; font-weight: 700; color: #0f172a; font-size: 0.875rem;">
                                    {{ $student->name }}
                                </td>
                                <td style="padding: 0.85rem 1rem; color: #64748b; font-size: 0.85rem;">
                                    {{ $student->pivot->enrolled_at ? \Carbon\Carbon::parse($student->pivot->enrolled_at)->translatedFormat('d M Y, H:i') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- SECTION 1: MATERI PERKULIAHAN --}}
    <div class="card-modern" style="margin-bottom: 1rem;">
        <div class="card-header-row">
            <h3 class="card-header-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                    <path d="M6 6h10"/>
                    <path d="M6 10h10"/>
                </svg>
                Materi Perkuliahan
                <span style="font-size: 0.72rem; font-weight: 700; background: #eff6ff; color: #2563eb; padding: 0.15rem 0.5rem; border-radius: 9999px;">
                    {{ $course->materials->count() }} Berkas
                </span>
            </h3>

            @can('create', [\App\Models\Material::class, $course])
                <a href="{{ route('courses.materials.create', $course) }}" class="btn btn-primary btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                    + Materi
                </a>
            @endcan
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            @forelse ($course->materials as $material)
                <div class="course-card-compact" onclick="window.location='{{ route('materials.show', $material) }}'">
                    <div style="flex: 1; min-width: 0; padding-right: 0.5rem;">
                        <h4 class="course-compact-title">
                            {{ $material->title }}
                        </h4>
                        <div class="course-compact-meta">
                            <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 0.68rem; font-weight: 700; padding: 0.12rem 0.45rem; border-radius: 6px;">
                                {{ ucfirst($material->type) }}
                            </span>
                            @if($material->formatted_file_size)
                                <span style="font-size: 0.72rem; color: #64748b;">{{ $material->formatted_file_size }}</span>
                            @endif
                            <span style="font-size: 0.72rem; color: #64748b;">&bull; {{ $material->created_at?->format('d M Y') }}</span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0;">
                        @if($material->type === 'link' && $material->external_url)
                            <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="btn-icon-action" title="Buka Tautan Luar" onclick="event.stopPropagation()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                </svg>
                            </a>
                        @elseif($material->file_path)
                            <a href="{{ route('materials.download', $material) }}" class="btn-icon-action" title="Unduh Berkas" onclick="event.stopPropagation()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                            </a>
                        @endif

                        @can('update', $material)
                            <a href="{{ route('materials.edit', $material) }}" class="btn-icon-action edit" title="Edit Materi" onclick="event.stopPropagation()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                        @endcan
                        @can('delete', $material)
                            <form action="{{ route('materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus materi ini?');" style="margin: 0; display: inline;" onclick="event.stopPropagation()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon-action delete" title="Hapus Materi">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="empty-state-card" style="padding: 1.5rem 1rem;">
                    <p class="empty-state-text">Belum ada materi perkuliahan untuk mata kuliah ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- SECTION 2: TUGAS AKADEMIK --}}
    <div class="card-modern" style="margin-bottom: 1rem;">
        <div class="card-header-row">
            <h3 class="card-header-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Tugas Akademik
                <span style="font-size: 0.72rem; font-weight: 700; background: #eff6ff; color: #2563eb; padding: 0.15rem 0.5rem; border-radius: 9999px;">
                    {{ $course->assignments->count() }} Tugas
                </span>
            </h3>

            @can('create', [\App\Models\Assignment::class, $course])
                <a href="{{ route('courses.assignments.create', $course) }}" class="btn btn-primary btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                    + Tugas
                </a>
            @endcan
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            @forelse ($course->assignments as $assignment)
                <div class="course-card-compact" onclick="window.location='{{ route('assignments.show', $assignment) }}'">
                    <div style="flex: 1; min-width: 0; padding-right: 0.5rem;">
                        <h4 class="course-compact-title">
                            {{ $assignment->title }}
                        </h4>

                        <div class="course-compact-meta">
                            @if ($assignment->due_at?->isPast())
                                <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.68rem; font-weight: 700; border-radius: 6px; padding: 0.12rem 0.45rem;">
                                    Lewat Tenggat
                                </span>
                            @else
                                <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 0.68rem; font-weight: 700; border-radius: 6px; padding: 0.12rem 0.45rem;">
                                    Aktif
                                </span>
                            @endif
                            <span class="badge-status-dot {{ $assignment->status }}" style="font-size: 0.68rem; padding: 0.12rem 0.45rem;">
                                <span class="dot"></span>
                                {{ ucfirst($assignment->status) }}
                            </span>
                            <span style="font-size: 0.72rem; color: #64748b;">
                                Tenggat: {{ $assignment->due_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.45rem; flex-shrink: 0;">
                        @if (auth()->user()?->role === 'mahasiswa')
                            @php
                                $mySub = $assignment->submissions->firstWhere('user_id', auth()->id());
                            @endphp
                            @if ($mySub)
                                <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.72rem; font-weight: 700;">
                                    &#10003; Dikumpulkan
                                </span>
                            @else
                                <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.72rem; font-weight: 700;">
                                    Belum Kumpul
                                </span>
                            @endif
                        @else
                            <span class="badge" style="background: #eff6ff; color: #1d4ed8; font-size: 0.72rem; font-weight: 700;">
                                {{ $assignment->submissions()->count() }} Kumpul
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state-card" style="padding: 1.5rem 1rem;">
                    <p class="empty-state-text">Belum ada tugas perkuliahan untuk mata kuliah ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- SECTION 3: PESERTA MAHASISWA TERDAFTAR (HANYA DITAMPILKAN UNTUK DOSEN & ADMIN) --}}
    @if (auth()->user()->role !== 'mahasiswa')
        <div class="card-modern" id="peserta-mahasiswa">
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
                        {{ $course->students()->count() }} Orang
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
                            <button type="submit" class="filter-btn-primary" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;" title="Daftarkan Mahasiswa">
                                + Daftarkan
                            </button>
                        </form>
                    @endif
                @endcan
            </div>

            @php
                $studentsList = $enrolledStudents ?? $course->students;
            @endphp

            @if ($studentsList->isEmpty())
                <div class="empty-state-card" style="padding: 1.5rem 1rem;">
                    <p class="empty-state-text">Belum ada mahasiswa yang terdaftar di kelas ini.</p>
                </div>
            @else
                {{-- Mobile View: Kartu Mahasiswa Ringkas (<= 767px) --}}
                <div class="students-mobile-cards" style="display: none; flex-direction: column; gap: 0.4rem;">
                    @foreach ($studentsList as $student)
                        <div class="course-card-compact" style="cursor: default; padding: 0.55rem 0.75rem; margin-bottom: 0.35rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem; line-height: 1.2;">
                                    {{ $student->name }}
                                </div>
                                <div style="font-size: 0.7rem; color: #64748b; margin-top: 0.15rem;">
                                    NIM: <strong style="color: #334155; font-family: monospace;">{{ $student->nim_nip ?? '-' }}</strong> &bull; {{ $student->pivot->enrolled_at ? \Carbon\Carbon::parse($student->pivot->enrolled_at)->translatedFormat('d M Y') : '-' }}
                                </div>
                            </div>
                            @can('unenroll', $course)
                                <form action="{{ route('courses.unenroll', [$course, $student]) }}" method="POST" onsubmit="return confirm('Keluarkan mahasiswa {{ $student->name }} dari mata kuliah ini?');" style="margin: 0; display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon-action delete" title="Keluarkan {{ $student->name }}" style="color: #dc2626; border-color: #fecaca; width: 26px; height: 26px;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @endforeach
                </div>

                {{-- Desktop View: Tabel Peserta Mahasiswa (> 767px) --}}
                <div class="students-desktop-table" style="overflow-x: auto; border: 1px solid #eef2f6; border-radius: 16px;">
                    <table class="grade-table">
                        <thead>
                            <tr>
                                <th style="width: 140px;">NIM</th>
                                <th>Nama Lengkap</th>
                                <th>Terdaftar Sejak</th>
                                @can('unenroll', $course)
                                    <th style="text-align: right; width: 60px;">Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($studentsList as $student)
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
                                                <button type="submit" class="btn-icon-action delete" title="Keluarkan {{ $student->name }}" style="color: #dc2626; border-color: #fecaca;">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"/>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi Peserta Mahasiswa Terdaftar --}}
                @if (isset($enrolledStudents) && $enrolledStudents->hasPages())
                    <div style="padding: 0.85rem 1rem; border-top: 1px solid #eef2f6; background: #ffffff;">
                        {{ $enrolledStudents->fragment('peserta-mahasiswa')->links() }}
                    </div>
                @endif
            @endif
        </div>
    @endif

    <script>
        function togglePesertaView() {
            const section = document.getElementById('pesertaFullSection');
            const badgeText = document.getElementById('badgeLihatPesertaText');
            const badgeIcon = document.getElementById('badgeLihatPesertaIcon');
            if (!section) return;

            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
                if (badgeText) badgeText.textContent = 'Tutup Peserta';
                if (badgeIcon) badgeIcon.style.transform = 'rotate(180deg)';
                section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                section.style.display = 'none';
                if (badgeText) badgeText.textContent = 'Lihat Peserta';
                if (badgeIcon) badgeIcon.style.transform = 'rotate(0deg)';
            }
        }

        function filterPesertaList() {
            const query = (document.getElementById('searchPesertaInput')?.value || '').toLowerCase();
            const rows = document.querySelectorAll('.peserta-table-row');
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const nim = row.getAttribute('data-nim') || '';
                if (name.includes(query) || nim.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>

</x-layout>
