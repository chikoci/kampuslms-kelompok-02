<x-layout title="Rekap Nilai Kelas - {{ $course->name }} - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
    ['label' => $course->name, 'url' => route('courses.show', $course)],
    ['label' => 'Rekap Nilai Kelas']
]">

    <div class="card-modern" style="margin-bottom: 2rem;">
        <div style="margin-bottom: 1.25rem;">
            <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;">
                &larr; Kembali ke {{ $course->name }}
            </a>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="badge badge-primary" style="font-size: 0.8rem; margin-bottom: 0.5rem;">{{ $course->code }}</span>
                <h1 class="page-title" style="margin: 0.25rem 0 0.5rem 0;">Rekapitulasi Nilai Akademik Kelas</h1>
                <p class="text-muted" style="margin: 0;">
                    Mata Kuliah: <strong>{{ $course->name }}</strong> | Dosen Pengampu: <strong>{{ $course->lecturer->name }}</strong>
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <div class="card" style="padding: 0.75rem 1.25rem; text-align: center; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; min-width: 110px;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">TOTAL MAHASISWA</div>
                    <div style="font-size: 1.4rem; font-weight: 700; color: #0f172a;">{{ $students->count() }}</div>
                </div>
                <div class="card" style="padding: 0.75rem 1.25rem; text-align: center; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; min-width: 110px;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">TOTAL TUGAS</div>
                    <div style="font-size: 1.4rem; font-weight: 700; color: #0284c7;">{{ $assignments->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($students->isEmpty())
        <div class="card" style="text-align: center; padding: 3rem 1.5rem; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <p class="text-muted" style="margin: 0;">Belum ada mahasiswa yang terdaftar di mata kuliah ini.</p>
        </div>
    @elseif ($assignments->isEmpty())
        <div class="card" style="text-align: center; padding: 3rem 1.5rem; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <p class="text-muted" style="margin: 0;">Belum ada tugas yang dibuat pada mata kuliah ini.</p>
        </div>
    @else
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <table class="table" style="width: 100%; border-collapse: collapse; min-width: 750px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                        <th style="padding: 0.85rem 1rem; font-size: 0.8rem; font-weight: 700; color: #475569; width: 45px;">No</th>
                        <th style="padding: 0.85rem 1rem; font-size: 0.8rem; font-weight: 700; color: #475569; min-width: 180px;">Nama Mahasiswa</th>
                        <th style="padding: 0.85rem 1rem; font-size: 0.8rem; font-weight: 700; color: #475569; width: 110px;">NIM</th>
                        @foreach ($assignments as $assignment)
                            <th style="padding: 0.85rem 1rem; font-size: 0.8rem; font-weight: 700; color: #0f172a; text-align: center; min-width: 120px;">
                                <div style="font-weight: 700;">{{ $assignment->title }}</div>
                                <div style="font-size: 0.7rem; color: #64748b; font-weight: normal;">Maks: {{ $assignment->max_score }}</div>
                            </th>
                        @endforeach
                        <th style="padding: 0.85rem 1rem; font-size: 0.8rem; font-weight: 700; color: #047857; text-align: center; width: 90px; background: #f0fdf4;">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $index => $student)
                        @php
                            $totalScore = 0;
                            $gradedCount = 0;
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                            <td style="padding: 0.85rem 1rem; font-size: 0.85rem; color: #64748b; font-weight: 600;">{{ $index + 1 }}</td>
                            <td style="padding: 0.85rem 1rem; font-size: 0.9rem; font-weight: 600; color: #1e293b;">
                                {{ $student->name }}
                            </td>
                            <td style="padding: 0.85rem 1rem; font-size: 0.85rem; color: #64748b; font-family: monospace;">
                                {{ $student->nim_nip ?? '-' }}
                            </td>

                            @foreach ($assignments as $assignment)
                                @php
                                    $subKey = $student->id . '_' . $assignment->id;
                                    $sub = $submissions->get($subKey)?->first();
                                    $grade = $sub?->grade;
                                @endphp
                                <td style="padding: 0.85rem 1rem; font-size: 0.85rem; text-align: center;">
                                    @if ($grade)
                                        @php
                                            $totalScore += $grade->score;
                                            $gradedCount++;
                                            $isHigh = ($grade->score >= 80);
                                            $isMed = ($grade->score >= 60 && $grade->score < 80);
                                        @endphp
                                        <a href="{{ route('submissions.show', $sub) }}" style="text-decoration: none;">
                                            <span class="badge" style="background: {{ $isHigh ? '#dcfce7' : ($isMed ? '#fef9c3' : '#fee2e2') }}; color: {{ $isHigh ? '#166534' : ($isMed ? '#854d0e' : '#991b1b') }}; font-weight: 700; padding: 0.25rem 0.55rem;">
                                                {{ number_format($grade->score, 1) }}
                                            </span>
                                        </a>
                                    @elseif ($sub)
                                        <a href="{{ route('submissions.show', $sub) }}" style="text-decoration: none;">
                                            <span class="badge badge-warning" style="font-size: 0.7rem; padding: 0.2rem 0.45rem;">
                                                Belum Dinilai
                                            </span>
                                        </a>
                                    @else
                                        <span style="color: #cbd5e1; font-size: 0.85rem;">-</span>
                                    @endif
                                </td>
                            @endforeach

                            <td style="padding: 0.85rem 1rem; font-size: 0.9rem; text-align: center; font-weight: 700; color: #047857; background: #f0fdf4;">
                                @if ($gradedCount > 0)
                                    {{ number_format($totalScore / $gradedCount, 1) }}
                                @else
                                    <span style="color: #94a3b8; font-weight: normal;">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layout>
