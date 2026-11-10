<x-layout title="Daftar Pengumpulan Tugas - KampusLMS">

    {{-- Navigasi Kembali --}}
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('assignments.show', $assignment) }}" class="btn-open-class" style="border-radius: 9999px;">
            &larr; Kembali ke Tugas
        </a>
    </div>

    {{-- Header Banner Pengumpulan --}}
    <div class="card-modern" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
            <span class="badge-code-modern">{{ $assignment->course->code ?? 'TUGAS' }}</span>
            <span class="badge-status-dot {{ $assignment->status }}">
                <span class="dot"></span>
                {{ ucfirst($assignment->status) }}
            </span>
        </div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin: 0 0 0.35rem 0;">
            Pengumpulan: {{ $assignment->title }}
        </h1>
        <p style="color: #64748b; font-size: 0.9rem; margin: 0;">
            Mata Kuliah: <strong>{{ $assignment->course->name ?? '-' }}</strong> &bull; Batas Waktu: <strong>{{ $assignment->due_at?->translatedFormat('d M Y, H:i') }}</strong>
        </p>
    </div>

    @if(auth()->user()->role === 'mahasiswa')
        {{-- Tampilan khusus mahasiswa --}}
        <div class="card-modern" style="padding: 0; overflow: hidden;">
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
                                        <a href="{{ route('submissions.download', $submission) }}" class="btn-open-class" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe; font-size: 0.75rem; padding: 0.25rem 0.65rem;">
                                            &#128190; {{ $submission->original_name }}
                                        </a>
                                    @else
                                        <span style="color: #94a3b8;">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($submission->is_late)
                                        <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.75rem; font-weight: 700; border-radius: 6px; padding: 0.2rem 0.6rem;">Terlambat</span>
                                    @else
                                        <span class="badge" style="background: #ecfdf5; color: #047857; font-size: 0.75rem; font-weight: 700; border-radius: 6px; padding: 0.2rem 0.6rem;">Tepat Waktu</span>
                                    @endif
                                </td>
                                <td style="text-align: center; font-weight: 800; color: #2563eb;">
                                    {{ $submission->grade ? number_format($submission->grade->score, 2) . ' / ' . $assignment->max_score : 'Belum Dinilai' }}
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('submissions.show', $submission) }}" class="btn-open-class" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">
                                        Detail &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state-card" style="padding: 2.5rem 1rem;">
                                    <p class="empty-state-text">Anda belum mengumpulkan tugas ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($submissions->hasPages())
                <div class="pagination-wrapper" style="padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0;">
                    {{ $submissions->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- Tampilan khusus Dosen dan Admin --}}
        @php
            $totalEnrolled = $enrolledStudents->count();
            $submittedCount = $submissions->count();
            $uncreditedSubmissions = $submissions->filter(fn($s) => !$s->grade);
            $canGradeAssignment = auth()->user()?->role === 'dosen' && $assignment->course->lecturer_id === auth()->id();
        @endphp

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="grade-metric-box blue">
                <div>
                    <div class="lbl">Total Terdaftar</div>
                    <div class="val">{{ $totalEnrolled }} <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Mhs</span></div>
                </div>
            </div>
            <div class="grade-metric-box green">
                <div>
                    <div class="lbl">Sudah Mengumpulkan</div>
                    <div class="val" style="color: #16a34a;">{{ $submittedCount }} <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Mhs</span></div>
                </div>
            </div>
            <div class="grade-metric-box orange">
                <div>
                    <div class="lbl">Belum Mengumpulkan</div>
                    <div class="val" style="color: #dc2626;">{{ max(0, $totalEnrolled - $submittedCount) }} <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Mhs</span></div>
                </div>
            </div>
        </div>

        @if($canGradeAssignment && $uncreditedSubmissions->isNotEmpty())
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 18px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h4 style="margin: 0; color: #1e40af; font-size: 1rem; font-weight: 700;">
                        &#9998; {{ $uncreditedSubmissions->count() }} Pengumpulan Menunggu Penilaian
                    </h4>
                    <p style="margin: 0.25rem 0 0 0; color: #3b82f6; font-size: 0.85rem;">
                        Klik tombol untuk langsung membuka form penilaian mahasiswa yang telah mengumpulkan tugas.
                    </p>
                </div>
                <a href="{{ route('submissions.show', $uncreditedSubmissions->first()) }}" class="filter-btn-primary" style="text-decoration: none;">
                    Mulai Beri Nilai &rarr;
                </a>
            </div>
        @endif

        <div class="card-modern" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table class="grade-table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th style="text-align: center;">Status</th>
                            <th>Waktu Kumpul</th>
                            <th>Berkas Tugas</th>
                            <th style="text-align: center;">Penilaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrolledStudents as $student)
                            @php
                                $sub = $submissions->get($student->id);
                            @endphp
                            <tr style="{{ $sub ? 'background: #ffffff;' : 'background: #f8fafc;' }}">
                                <td style="font-weight: 700; color: #64748b; font-size: 0.85rem;">{{ $student->nim_nip ?? '-' }}</td>
                                <td>
                                    @if($sub)
                                        <a href="{{ route('submissions.show', $sub) }}" style="color: #2563eb; font-weight: 700; text-decoration: none;">
                                            {{ $student->name }} &rarr;
                                        </a>
                                    @else
                                        <span style="color: #475569; font-weight: 500;">{{ $student->name }}</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($sub)
                                        <span class="badge" style="background: #ecfdf5; color: #047857; font-size: 0.75rem; font-weight: 700; border-radius: 6px; padding: 0.2rem 0.6rem;">Sudah Kumpul</span>
                                    @else
                                        <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.75rem; font-weight: 600; border-radius: 6px; padding: 0.2rem 0.6rem;">Belum Kumpul</span>
                                    @endif
                                </td>
                                <td style="color: #64748b; font-size: 0.85rem;">
                                    {{ $sub?->submitted_at ? $sub->submitted_at->translatedFormat('d M Y, H:i') : '-' }}
                                </td>
                                <td>
                                    @if($sub && $sub->file_path)
                                        <a href="{{ route('submissions.download', $sub) }}" class="btn-open-class" style="padding: 0.25rem 0.65rem; font-size: 0.75rem; background: #eff6ff; color: #2563eb; border-color: #bfdbfe;">
                                            &#128190; Unduh
                                        </a>
                                    @else
                                        <span style="color: #cbd5e1;">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($sub)
                                        @if($sub->grade)
                                            <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 0.85rem; font-weight: 800; padding: 0.25rem 0.6rem; border-radius: 8px;">
                                                {{ number_format($sub->grade->score, 2) }}
                                            </span>
                                        @else
                                            <a href="{{ route('submissions.show', $sub) }}" class="btn-open-class" style="background: #2563eb; color: #ffffff; border-color: #2563eb; padding: 0.25rem 0.65rem; font-size: 0.75rem;">
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
                                <td colspan="6" class="empty-state-card" style="padding: 2.5rem 1rem;">
                                    <p class="empty-state-text">Belum ada mahasiswa yang terdaftar di kelas ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</x-layout>
