<x-layout title="Daftar Pengumpulan Tugas">
    <div class="header-actions-row">
        <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Tugas
        </a>
    </div>

    <div class="page-header">
        <h1 class="page-title-lg">Pengumpulan Tugas: {{ $assignment->title }}</h1>
        <p style="color: #6b7280; font-size: 0.875rem;">
            Mata Kuliah: <strong>{{ $assignment->course->name ?? '-' }}</strong> | 
            Batas Waktu: <strong>{{ $assignment->due_at?->format('d M Y, H:i') }}</strong>
        </p>
    </div>

    @if(auth()->user()->role === 'mahasiswa')
        {{-- Tampilan khusus mahasiswa: hanya pengumpulan miliknya --}}
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu Kumpul</th>
                        <th>Berkas Tugas</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td class="text-muted">{{ $submission->submitted_at?->format('d M Y, H:i') ?? '-' }}</td>
                            <td>
                                @if($submission->file_path)
                                    <a href="{{ route('submissions.download', $submission) }}" class="badge badge-file" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        &#128190; {{ $submission->original_name }}
                                        <span style="font-size: 0.75rem; opacity: 0.85;">({{ $submission->formatted_file_size }})</span>
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($submission->is_late)
                                    <span class="badge badge-overdue">Terlambat</span>
                                @else
                                    <span class="badge badge-upcoming">Tepat Waktu</span>
                                @endif
                            </td>
                            <td>
                                <strong style="color: #2563eb;">{{ $submission->grade ? $submission->grade->score . ' / ' . $assignment->max_score : 'Belum Dinilai' }}</strong>
                            </td>
                            <td>
                                <a href="{{ route('submissions.show', $submission) }}" class="btn btn-sm" style="background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 4px 10px; border-radius: 6px;">
                                    Detail &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-cell-sm">Anda belum mengumpulkan tugas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($submissions->hasPages())
            <div class="pagination-wrapper" style="margin-top: 1.5rem;">
                {{ $submissions->links() }}
            </div>
        @endif
    @else
        {{-- Tampilan khusus Dosen dan Admin: Rekap Mahasiswa Sudah / Belum Mengumpulkan --}}
        @php
            $totalEnrolled = $enrolledStudents->count();
            $submittedCount = $submissions->count();
        @endphp

        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb;">
                <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Total Terdaftar</span>
                <p style="font-size: 1.25rem; font-weight: 700; color: #111827; margin-top: 0.25rem;">{{ $totalEnrolled }} Mahasiswa</p>
            </div>
            <div style="flex: 1; min-width: 180px; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb;">
                <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Sudah Mengumpulkan</span>
                <p style="font-size: 1.25rem; font-weight: 700; color: #16a34a; margin-top: 0.25rem;">{{ $submittedCount }} Mahasiswa</p>
            </div>
            <div style="flex: 1; min-width: 180px; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb;">
                <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Belum Mengumpulkan</span>
                <p style="font-size: 1.25rem; font-weight: 700; color: #dc2626; margin-top: 0.25rem;">{{ max(0, $totalEnrolled - $submittedCount) }} Mahasiswa</p>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Status Pengumpulan</th>
                        <th>Waktu Kumpul</th>
                        <th>Berkas Tugas</th>
                        <th>Nilai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrolledStudents as $student)
                        @php
                            $sub = $submissions->get($student->id);
                        @endphp
                        <tr>
                            <td class="text-muted" style="font-size: 0.85rem;">{{ $student->nim_nip ?? '-' }}</td>
                            <td class="font-semibold text-body">{{ $student->name }}</td>
                            <td>
                                @if($sub)
                                    @if($sub->is_late)
                                        <span class="badge badge-overdue">Terlambat</span>
                                    @else
                                        <span class="badge badge-upcoming">Tepat Waktu</span>
                                    @endif
                                @else
                                    <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">Belum Mengumpulkan</span>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem;">
                                {{ $sub?->submitted_at?->format('d M Y, H:i') ?? '-' }}
                            </td>
                            <td>
                                @if($sub && $sub->file_path)
                                    <a href="{{ route('submissions.download', $sub) }}" class="badge badge-file" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Unduh berkas submission">
                                        &#128190; {{ $sub->original_name }}
                                        <span style="font-size: 0.75rem; opacity: 0.85;">({{ $sub->formatted_file_size }})</span>
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($sub)
                                    @if($sub->grade)
                                        <strong style="color: #16a34a;">{{ $sub->grade->score }} / {{ $assignment->max_score }}</strong>
                                    @else
                                        <span class="text-muted" style="font-size: 0.85rem;">Belum Dinilai</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($sub)
                                    <a href="{{ route('submissions.show', $sub) }}" class="btn btn-sm" style="background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 4px 10px; border-radius: 6px;">
                                        Periksa &rarr;
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size: 0.85rem;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-cell-sm">Belum ada mahasiswa yang terdaftar pada mata kuliah ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</x-layout>
