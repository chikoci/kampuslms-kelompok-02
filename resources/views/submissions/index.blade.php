<x-layout title="Pengumpulan: {{ $assignment->title }} - KampusLMS"
    :backUrl="route('assignments.show', $assignment)"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
        ['label' => $assignment->course->name, 'url' => route('courses.show', $assignment->course)],
        ['label' => $assignment->title, 'url' => route('assignments.show', $assignment)],
        ['label' => auth()->user()->role === 'mahasiswa' ? 'Pengumpulan' : 'Penilaian']
    ]">

    {{-- Header Banner Pengumpulan Ringkas --}}
    <div class="card-modern" style="margin-bottom: 0.85rem; padding: 1rem 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.45rem; margin-bottom: 0.25rem;">
                    <span class="badge-code-modern" style="font-size: 0.72rem; padding: 0.1rem 0.45rem;">{{ $assignment->course->code ?? 'TUGAS' }}</span>
                    <span class="badge-status-dot {{ $assignment->status }}">
                        <span class="dot"></span>
                        {{ ucfirst($assignment->status) }}
                    </span>
                </div>
                <h1 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0 0 0.2rem 0;">
                    {{ $assignment->title }}
                </h1>
                <p style="color: #64748b; font-size: 0.8rem; margin: 0;">
                    Tenggat: <strong>{{ $assignment->due_at?->translatedFormat('d M Y, H:i') }}</strong> &bull; Nilai Maks: <strong>{{ $assignment->max_score }}</strong>
                </p>
            </div>

            @if(auth()->user()->role !== 'mahasiswa')
                @php
                    $totalEnrolled = $enrolledStudents->count();
                    $submittedCount = $submissions->count();
                    $pendingGrading = $submissions->filter(fn($s) => !$s->grade)->count();
                @endphp
                <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                    <span class="badge" style="background: #f1f5f9; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.65rem; border-radius: 8px;">
                        {{ $totalEnrolled }} Mahasiswa
                    </span>
                    <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.65rem; border-radius: 8px;">
                        {{ $submittedCount }} Kumpul
                    </span>
                    @if($pendingGrading > 0)
                        <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.65rem; border-radius: 8px;">
                            {{ $pendingGrading }} Belum Dinilai
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if(auth()->user()->role === 'mahasiswa')
        {{-- TAMPILAN KHUSUS MAHASISWA --}}
        
        {{-- Mobile Cards Mahasiswa --}}
        <div class="submission-mobile-cards" style="display: none; flex-direction: column; gap: 0.5rem;">
            @forelse ($submissions as $submission)
                <div class="course-card-compact" onclick="window.location='{{ route('submissions.show', $submission) }}'" style="cursor: pointer; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 0.95rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.2rem;">
                            <span style="font-weight: 700; color: #0f172a; font-size: 0.9rem;">
                                {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') ?? 'Dikumpulkan' }}
                            </span>
                            @if($submission->is_late)
                                <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.65rem; font-weight: 700; border-radius: 4px; padding: 0.08rem 0.35rem;">Terlambat</span>
                            @else
                                <span class="badge" style="background: #ecfdf5; color: #047857; font-size: 0.65rem; font-weight: 700; border-radius: 4px; padding: 0.08rem 0.35rem;">Tepat Waktu</span>
                            @endif
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b;">
                            @if($submission->grade)
                                <span style="color: #15803d; font-weight: 700;">Nilai: {{ number_format($submission->grade->score, 2) }} / {{ $assignment->max_score }}</span>
                            @else
                                <span style="color: #b45309; font-weight: 600;">Belum Dinilai</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="card-modern" style="text-align: center; padding: 2rem 1rem; color: #64748b; font-size: 0.85rem;">
                    Anda belum mengumpulkan tugas ini.
                </div>
            @endforelse
        </div>

        {{-- Desktop Table Mahasiswa --}}
        <div class="submission-desktop-table card-modern" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table class="grade-table">
                    <thead>
                        <tr>
                            <th>Waktu Kumpul</th>
                            <th>Berkas Tugas</th>
                            <th>Status Keterlambatan</th>
                            <th style="text-align: center;">Nilai</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($submissions as $submission)
                            <tr>
                                <td style="font-weight: 600; color: #0f172a;">
                                    {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                                </td>
                                <td>
                                    @if($submission->file_path)
                                        <a href="{{ route('submissions.download', $submission) }}" class="btn-open-class" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe; font-size: 0.75rem; padding: 0.25rem 0.65rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="7 10 12 15 17 10"/>
                                                <line x1="12" y1="15" x2="12" y2="3"/>
                                            </svg>
                                            <span class="file-name-truncate" title="{{ $submission->original_name }}">{{ $submission->original_name }}</span>
                                        </a>
                                    @else
                                        <span style="color: #94a3b8;">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($submission->is_late)
                                        <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.72rem; font-weight: 700; border-radius: 6px; padding: 0.15rem 0.5rem;">Terlambat</span>
                                    @else
                                        <span class="badge" style="background: #ecfdf5; color: #047857; font-size: 0.72rem; font-weight: 700; border-radius: 6px; padding: 0.15rem 0.5rem;">Tepat Waktu</span>
                                    @endif
                                </td>
                                <td style="text-align: center; font-weight: 800; color: #2563eb;">
                                    {{ $submission->grade ? number_format($submission->grade->score, 2) . ' / ' . $assignment->max_score : 'Belum Dinilai' }}
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('submissions.show', $submission) }}" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.78rem;">
                                        Detail &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2.5rem 1rem; color: #64748b; font-size: 0.85rem;">
                                    Anda belum mengumpulkan tugas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($submissions->hasPages())
                <div style="padding: 0.75rem 1.25rem; border-top: 1px solid #e2e8f0; background: #ffffff;">
                    {{ $submissions->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- TAMPILAN KHUSUS DOSEN / ADMIN --}}

        {{-- Mobile Cards Dosen (<= 767px): Sangat Ringkas Mirip Gambar 6 --}}
        <div class="submission-mobile-cards" style="display: none; flex-direction: column; gap: 0.5rem;">
            @forelse ($enrolledStudents as $student)
                @php
                    $sub = $submissions->get($student->id);
                    $hasGrade = $sub && $sub->grade !== null;
                @endphp
                <div class="course-card-compact"
                     @if($sub) onclick="window.location='{{ route('submissions.show', $sub) }}'" style="cursor: pointer; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 0.95rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;"
                     @else style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 0.95rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; opacity: 0.75;"
                     @endif>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.15rem;">
                            <h4 style="margin: 0; font-size: 0.925rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $student->name }}
                            </h4>
                            @if($sub && $sub->is_late)
                                <span class="badge" style="background: #fef2f2; color: #dc2626; font-size: 0.65rem; padding: 0.08rem 0.35rem; border-radius: 4px; font-weight: 700; flex-shrink: 0;">Terlambat</span>
                            @endif
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b; display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
                            <span>{{ $student->nim_nip ?? 'No NIM' }}</span>
                            <span>&bull;</span>
                            @if($sub)
                                @if($hasGrade)
                                    <span style="color: #15803d; font-weight: 700;">Nilai: {{ $sub->grade->score }}</span>
                                @else
                                    <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.68rem; font-weight: 700; padding: 0.08rem 0.35rem; border-radius: 4px;">Belum Dinilai</span>
                                @endif
                            @else
                                <span style="color: #94a3b8; font-weight: 500;">Belum Mengumpulkan</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="card-modern" style="text-align: center; padding: 2rem 1rem; color: #64748b; font-size: 0.85rem;">
                    Belum ada mahasiswa yang terdaftar di kelas ini.
                </div>
            @endforelse
        </div>

        {{-- Desktop Table Dosen (> 767px) --}}
        <div class="submission-desktop-table card-modern" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table class="grade-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                            <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase;">NIM</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase;">Nama Mahasiswa</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase;">Waktu Kumpul</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase;">Berkas</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase; text-align: center;">Penilaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrolledStudents as $student)
                            @php
                                $sub = $submissions->get($student->id);
                            @endphp
                            <tr style="{{ $sub ? 'background: #ffffff;' : 'background: #fbfcfe;' }}; border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.75rem 1rem; font-weight: 700; color: #64748b; font-size: 0.8rem;">
                                    {{ $student->nim_nip ?? '-' }}
                                </td>
                                <td style="padding: 0.75rem 1rem;">
                                    <span style="font-weight: 600; color: #0f172a; font-size: 0.875rem;">
                                        {{ $student->name }}
                                    </span>
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: center;">
                                    @if($sub)
                                        <span class="badge" style="background: #ecfdf5; color: #047857; font-size: 0.72rem; font-weight: 700; border-radius: 6px; padding: 0.15rem 0.5rem;">Kumpul</span>
                                    @else
                                        <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.72rem; font-weight: 600; border-radius: 6px; padding: 0.15rem 0.5rem;">Belum</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 1rem; color: #64748b; font-size: 0.8rem;">
                                    {{ $sub?->submitted_at ? $sub->submitted_at->translatedFormat('d M Y, H:i') : '-' }}
                                    @if($sub && $sub->is_late)
                                        <span style="color: #dc2626; font-size: 0.7rem; font-weight: 700;">(Terlambat)</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 1rem;">
                                    @if($sub && $sub->file_path)
                                        <a href="{{ route('submissions.download', $sub) }}" class="btn-open-class" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; background: #eff6ff; color: #2563eb; border-color: #bfdbfe; display: inline-flex; align-items: center; gap: 0.3rem;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="7 10 12 15 17 10"/>
                                                <line x1="12" y1="15" x2="12" y2="3"/>
                                            </svg>
                                            <span>Unduh</span>
                                        </a>
                                    @else
                                        <span style="color: #cbd5e1;">-</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: center;">
                                    @if($sub)
                                        @if($sub->grade)
                                            <a href="{{ route('submissions.show', $sub) }}" style="text-decoration: none;" title="Edit Nilai">
                                                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 0.825rem; font-weight: 800; padding: 0.2rem 0.55rem; border-radius: 6px;">
                                                    {{ number_format($sub->grade->score, 2) }}
                                                </span>
                                            </a>
                                        @else
                                            <a href="{{ route('submissions.show', $sub) }}" class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.6rem; text-decoration: none;">
                                                Beri Nilai &rarr;
                                            </a>
                                        @endif
                                    @else
                                        <span style="color: #cbd5e1;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2.5rem 1rem; color: #64748b; font-size: 0.85rem;">
                                    Belum ada mahasiswa yang terdaftar di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</x-layout>
