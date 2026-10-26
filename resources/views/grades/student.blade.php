<x-layout>
    <x-slot:title>Nilai Saya - KampusLMS</x-slot:title>

    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 class="page-title" style="margin-bottom: 0.25rem;">Transkrip & Rekap Nilai Tugas</h1>
        <p class="text-muted" style="margin: 0;">
            Daftar nilai seluruh tugas perkuliahan untuk mahasiswa: <strong>{{ $user->name }}</strong> ({{ $user->nim_nip ?? '-' }})
        </p>
    </div>

    @if ($courses->isEmpty())
        <div class="card" style="text-align: center; padding: 3.5rem 1.5rem; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.6;">📚</div>
            <h3 style="margin-bottom: 0.5rem; color: #1e293b;">Belum Terdaftar di Mata Kuliah</h3>
            <p class="text-muted" style="margin: 0;">Anda belum terdaftar pada mata kuliah apa pun saat ini.</p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            @foreach ($courses as $course)
                <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1rem;">
                        <div>
                            <span class="badge badge-primary" style="font-size: 0.75rem; margin-bottom: 0.35rem;">{{ $course->code }}</span>
                            <h3 style="margin: 0.2rem 0; font-size: 1.15rem; color: #0f172a;">
                                <a href="{{ route('courses.show', $course) }}" style="color: inherit; text-decoration: none;">
                                    {{ $course->name }}
                                </a>
                            </h3>
                            <p class="text-muted" style="margin: 0; font-size: 0.85rem;">
                                Dosen: <strong>{{ $course->lecturer->name }}</strong>
                            </p>
                        </div>
                    </div>

                    @if ($course->assignments->isEmpty())
                        <p class="text-muted" style="margin: 0; font-size: 0.9rem; font-style: italic;">Belum ada tugas pada mata kuliah ini.</p>
                    @else
                        <div style="overflow-x: auto;">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                                        <th style="padding: 0.65rem 0.85rem; font-size: 0.8rem; font-weight: 700; color: #475569;">Tugas</th>
                                        <th style="padding: 0.65rem 0.85rem; font-size: 0.8rem; font-weight: 700; color: #475569;">Tenggat</th>
                                        <th style="padding: 0.65rem 0.85rem; font-size: 0.8rem; font-weight: 700; color: #475569;">Status Pengumpulan</th>
                                        <th style="padding: 0.65rem 0.85rem; font-size: 0.8rem; font-weight: 700; color: #475569; text-align: center;">Nilai</th>
                                        <th style="padding: 0.65rem 0.85rem; font-size: 0.8rem; font-weight: 700; color: #475569;">Catatan / Feedback</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($course->assignments as $assignment)
                                        @php
                                            $submission = $assignment->submissions->first();
                                            $grade = $submission?->grade;
                                        @endphp
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 0.75rem 0.85rem; font-size: 0.9rem; font-weight: 600;">
                                                <a href="{{ route('assignments.show', $assignment) }}" style="color: #0284c7; text-decoration: none;">
                                                    {{ $assignment->title }}
                                                </a>
                                            </td>
                                            <td style="padding: 0.75rem 0.85rem; font-size: 0.85rem; color: #64748b;">
                                                {{ $assignment->due_at->translatedFormat('d M Y, H:i') }}
                                            </td>
                                            <td style="padding: 0.75rem 0.85rem; font-size: 0.85rem;">
                                                @if ($submission)
                                                    <span class="badge badge-success" style="font-size: 0.75rem;">Sudah Dikumpulkan</span>
                                                    @if ($submission->is_late)
                                                        <span class="badge badge-danger" style="font-size: 0.7rem; margin-left: 0.25rem;">Terlambat</span>
                                                    @endif
                                                @else
                                                    <span class="badge badge-secondary" style="font-size: 0.75rem;">Belum Mengumpulkan</span>
                                                @endif
                                            </td>
                                            <td style="padding: 0.75rem 0.85rem; text-align: center;">
                                                @if ($grade)
                                                    <span class="badge" style="background: #dcfce7; color: #166534; font-weight: 700; font-size: 0.95rem; padding: 0.25rem 0.6rem;">
                                                        {{ number_format($grade->score, 1) }}
                                                    </span>
                                                    <span style="font-size: 0.75rem; color: #64748b;">/ {{ $assignment->max_score }}</span>
                                                @elseif ($submission)
                                                    <span class="badge badge-warning" style="font-size: 0.7rem;">Menunggu Nilai</span>
                                                @else
                                                    <span style="color: #cbd5e1;">-</span>
                                                @endif
                                            </td>
                                            <td style="padding: 0.75rem 0.85rem; font-size: 0.85rem; color: #475569;">
                                                @if ($grade && !empty($grade->feedback))
                                                    <em>"{{ $grade->feedback }}"</em>
                                                @elseif ($grade)
                                                    <span class="text-muted" style="font-size: 0.8rem;">(Tidak ada catatan)</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-layout>
