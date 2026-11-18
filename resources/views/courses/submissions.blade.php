{{-- Halaman Daftar Semua Pengumpulan Tugas pada Mata Kuliah (Khusus Dosen / Admin) --}}
<x-layout title="Pengumpulan: {{ $course->name }} - KampusLMS"
    :backUrl="route('dashboard')"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
        ['label' => $course->name, 'url' => route('courses.show', $course)],
        ['label' => 'Pengumpulan']
    ]">

    {{-- 1. Header Ringkas Mata Kuliah & Metrik --}}
    <div class="card-modern" style="margin-bottom: 0.85rem; padding: 1rem 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.25rem;">
                    <span class="badge-code-modern" style="font-size: 0.72rem; padding: 0.1rem 0.45rem;">{{ $course->code }}</span>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">{{ $course->sks }} SKS</span>
                </div>
                <h1 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0;">
                    Pengumpulan Tugas: {{ $course->name }}
                </h1>
            </div>

            {{-- Metrik Ringkas --}}
            <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                <span class="badge" style="background: #f1f5f9; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.65rem; border-radius: 8px;">
                    {{ $totalSubmissions }} Total
                </span>
                <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.65rem; border-radius: 8px;">
                    {{ $ungradedCount }} Belum Dinilai
                </span>
                <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.65rem; border-radius: 8px;">
                    {{ $gradedCount }} Dinilai
                </span>
            </div>
        </div>
    </div>

    {{-- 2. Filter Bar Ramping --}}
    <div class="card-modern" style="margin-bottom: 0.85rem; padding: 0.75rem 1rem;">
        <form action="{{ route('courses.submissions', $course) }}" method="GET" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <select name="assignment_id" class="form-control" style="font-size: 0.825rem; padding: 0.4rem 0.65rem; border-radius: 8px; border: 1px solid #cbd5e1; flex: 1; min-width: 140px;">
                <option value="">Semua Tugas ({{ $assignments->count() }})</option>
                @foreach ($assignments as $asg)
                    <option value="{{ $asg->id }}" {{ request('assignment_id') == $asg->id ? 'selected' : '' }}>
                        {{ $asg->title }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="form-control" style="font-size: 0.825rem; padding: 0.4rem 0.65rem; border-radius: 8px; border: 1px solid #cbd5e1; min-width: 120px;">
                <option value="">Semua Status</option>
                <option value="ungraded" {{ request('status') === 'ungraded' ? 'selected' : '' }}>Belum Dinilai</option>
                <option value="graded" {{ request('status') === 'graded' ? 'selected' : '' }}>Sudah Dinilai</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.4rem 0.85rem; font-size: 0.825rem; font-weight: 600;">
                Filter
            </button>
            @if (request()->hasAny(['assignment_id', 'status']))
                <a href="{{ route('courses.submissions', $course) }}" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.65rem; font-size: 0.825rem;" title="Reset filter">
                    ✕
                </a>
            @endif
        </form>
    </div>

    {{-- 3A. Mobile View (<= 767px): Kartu Ringkas 1 Baris Mirip Gambar 6 --}}
    <div class="submission-mobile-cards" style="display: none; flex-direction: column; gap: 0.5rem;">
        @forelse ($submissions as $submission)
            @php
                $hasGrade = $submission->grade !== null;
                $score = $submission->grade?->score;
            @endphp
            <div class="course-card-compact" onclick="window.location='{{ route('submissions.show', $submission) }}'" style="cursor: pointer; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 0.95rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.15rem;">
                        <h4 style="margin: 0; font-size: 0.925rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $submission->student?->name ?? 'Mahasiswa' }}
                        </h4>
                        @if ($submission->is_late)
                            <span class="badge" style="background: #fef2f2; color: #dc2626; font-size: 0.65rem; padding: 0.08rem 0.35rem; border-radius: 4px; font-weight: 700; flex-shrink: 0;">Terlambat</span>
                        @endif
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
                        <span style="font-weight: 500; color: #334155;">{{ $submission->assignment?->title ?? '-' }}</span>
                        <span>&bull;</span>
                        @if ($hasGrade)
                            <span style="color: #15803d; font-weight: 700;">Nilai: {{ $score }}</span>
                        @else
                            <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.68rem; font-weight: 700; padding: 0.08rem 0.35rem; border-radius: 4px;">Belum Dinilai</span>
                        @endif
                    </div>
                </div>
                <div class="course-chevron" style="width: 28px; height: 28px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </div>
            </div>
        @empty
            <div class="card-modern" style="text-align: center; padding: 2rem 1rem; color: #64748b; font-size: 0.85rem;">
                Tidak ada data pengumpulan tugas.
            </div>
        @endforelse
    </div>

    {{-- 3B. Desktop View (> 767px): Tabel Rapi dan Bersih --}}
    <div class="submission-desktop-table card-modern" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="grade-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                        <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase;">Mahasiswa</th>
                        <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase;">Tugas</th>
                        <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase;">Dikumpulkan</th>
                        <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase;">Status</th>
                        <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase; text-align: center;">Nilai</th>
                        <th style="padding: 0.75rem 1rem; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        @php
                            $hasGrade = $submission->grade !== null;
                            $score = $submission->grade?->score;
                            $maxScore = $submission->assignment?->max_score ?? 100;
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                            {{-- Mahasiswa --}}
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.875rem;">
                                    {{ $submission->student?->name ?? 'Mahasiswa' }}
                                </div>
                                <div style="font-size: 0.72rem; color: #64748b;">
                                    {{ $submission->student?->nim_nip ?? $submission->student?->email }}
                                </div>
                            </td>

                            {{-- Tugas --}}
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 600; color: #1e293b; font-size: 0.85rem;">
                                    {{ $submission->assignment?->title ?? '-' }}
                                </div>
                            </td>

                            {{-- Tanggal Kumpul --}}
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-size: 0.8rem; color: #334155;">
                                    {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                                </div>
                                @if ($submission->is_late)
                                    <span class="badge" style="background: #fef2f2; color: #dc2626; font-size: 0.65rem; padding: 0.05rem 0.35rem; border-radius: 4px; font-weight: 600;">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="badge" style="background: #ecfdf5; color: #059669; font-size: 0.65rem; padding: 0.05rem 0.35rem; border-radius: 4px; font-weight: 600;">
                                        Tepat Waktu
                                    </span>
                                @endif
                            </td>

                            {{-- Status Penilaian --}}
                            <td style="padding: 0.75rem 1rem;">
                                @if ($hasGrade)
                                    <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.72rem; padding: 0.15rem 0.5rem; border-radius: 6px; font-weight: 700;">
                                        Dinilai
                                    </span>
                                @else
                                    <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.72rem; padding: 0.15rem 0.5rem; border-radius: 6px; font-weight: 700;">
                                        Belum Dinilai
                                    </span>
                                @endif
                            </td>

                            {{-- Nilai --}}
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                @if ($hasGrade)
                                    <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">{{ $score }}</span>
                                    <span style="font-size: 0.72rem; color: #64748b;">/{{ $maxScore }}</span>
                                @else
                                    <span style="font-size: 0.85rem; color: #94a3b8;">&mdash;</span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td style="padding: 0.75rem 1rem; text-align: right;">
                                <a href="{{ route('submissions.show', $submission) }}"
                                   class="btn {{ $hasGrade ? 'btn-secondary' : 'btn-primary' }} btn-sm"
                                   style="font-size: 0.78rem; padding: 0.3rem 0.65rem; text-decoration: none;"
                                   title="{{ $hasGrade ? 'Lihat / Edit Nilai' : 'Beri Nilai' }}">
                                    {{ $hasGrade ? 'Lihat' : 'Beri Nilai' }} &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2.5rem 1rem; color: #64748b; font-size: 0.875rem;">
                                Tidak ada pengumpulan yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 4. Paginasi --}}
    @if ($submissions->hasPages())
        <div style="margin-top: 0.75rem;">
            {{ $submissions->links() }}
        </div>
    @endif

</x-layout>
