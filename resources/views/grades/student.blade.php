<x-layout title="Nilai Saya - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Nilai Saya']
]">

    {{-- A. PAGE HEADER DENGAN ILUSTRASI BERUANG NILAI A+ --}}
    <div class="page-banner-card">
        <div>
            <h1 class="page-banner-title">Nilai Saya</h1>
            <p class="page-banner-subtitle">Transkrip & Rekap Nilai Tugas dari seluruh mata kuliah yang Anda ikuti.</p>
        </div>
        <div class="kavi-banner-composition">
            <div class="kavi-banner-blob gold"></div>
            <div class="kavi-sparkle s1" style="top: 5px; left: 8px; font-size: 0.95rem; color: #f59e0b;">&#10022;</div>
            <div class="kavi-sparkle s2" style="top: 15px; right: 10px; font-size: 0.85rem; color: #3b82f6;">&#10022;</div>
            <img src="{{ asset('images/illustrations/kavi-nilai.png') }}" alt="Kavi Meraih Prestasi" class="kavi-banner-img">
            <div class="kavi-pedestal-shadow" style="width: 110px; height: 12px; bottom: -2px;"></div>
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="card-modern empty-state-card">
            <img src="{{ asset('images/illustrations/calendar-empty.png') }}" alt="Belum Terdaftar" class="empty-state-img">
            <h3 style="margin-bottom: 0.5rem; color: #1e293b; font-size: 1.15rem; font-weight: 700;">Belum Terdaftar di Mata Kuliah</h3>
            <p class="empty-state-text">Anda belum terdaftar pada mata kuliah apa pun saat ini.</p>
        </div>
    @else

        {{-- B. DAFTAR MATA KULIAH BERJEJER KE BAWAH (SEPERTI DI MENU MATA KULIAH) DENGAN DETAIL NILAI INTERAKTIF --}}
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
            @foreach ($courses as $c)
                @php
                    $totalAssignments = $c->assignments->count();
                    $submittedCount = 0;
                    $gradedCount = 0;
                    $totalAchievedScore = 0;
                    $totalMaxPossibleScore = 0;

                    foreach ($c->assignments as $a) {
                        $sub = $a->submissions->first();
                        if ($sub) {
                            $submittedCount++;
                            if ($sub->grade) {
                                $gradedCount++;
                                $totalAchievedScore += (float) $sub->grade->score;
                                $totalMaxPossibleScore += (float) $a->max_score;
                            }
                        }
                    }

                    $finalScore = null;
                    $gradeLetter = '-';
                    if ($gradedCount > 0 && $totalMaxPossibleScore > 0) {
                        $finalScore = ($totalAchievedScore / $totalMaxPossibleScore) * 100;
                        if ($finalScore >= 85) $gradeLetter = 'A';
                        elseif ($finalScore >= 80) $gradeLetter = 'A-';
                        elseif ($finalScore >= 75) $gradeLetter = 'B+';
                        elseif ($finalScore >= 70) $gradeLetter = 'B';
                        elseif ($finalScore >= 65) $gradeLetter = 'B-';
                        elseif ($finalScore >= 60) $gradeLetter = 'C+';
                        elseif ($finalScore >= 55) $gradeLetter = 'C';
                        elseif ($finalScore >= 40) $gradeLetter = 'D';
                        else $gradeLetter = 'E';
                    }

                    $attendanceRate = $totalAssignments > 0 ? round(($submittedCount / $totalAssignments) * 100, 1) : 100;
                    $equalWeight = $totalAssignments > 0 ? round(100 / $totalAssignments, 1) : 0;
                @endphp

                <div class="grade-accordion-card card-modern {{ $loop->first ? 'open' : '' }}" 
                     id="grade-card-{{ $c->id }}"
                     style="padding: 0; overflow: hidden; border: 1px solid {{ $loop->first ? '#93c5fd' : '#e2e8f0' }}; border-radius: 20px; box-shadow: {{ $loop->first ? '0 6px 20px rgba(37, 99, 235, 0.08)' : '0 2px 8px rgba(0, 0, 0, 0.02)' }};">
                    
                    {{-- 1. HEADER KARTU MATA KULIAH (Gaya Persis Menu Mata Kuliah) --}}
                    <div class="course-card-item grade-card-header" 
                         onclick="toggleCourseGrade('{{ $c->id }}')" 
                         style="cursor: pointer; margin-bottom: 0; border: none; border-radius: 20px; padding: 1.25rem 1.5rem; background: #ffffff;">
                        
                        <div class="course-info-col" style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; flex-wrap: wrap;">
                                <span class="badge-code-modern">{{ $c->code }}</span>
                                <span class="badge" style="background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    {{ $c->sks }} SKS
                                </span>
                                <span class="badge" style="background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                        <polyline points="17 6 23 6 23 12"/>
                                    </svg>
                                    {{ number_format($attendanceRate, 1) }}% Pengumpulan
                                </span>
                            </div>

                            <h3 class="course-title" style="margin: 0.2rem 0 0.35rem 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                                {{ $c->name }}
                            </h3>

                            <div class="course-meta-text" style="color: #64748b; font-size: 0.85rem;">
                                <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    Dosen: {{ $c->lecturer?->name ?? 'Belum Ditugaskan' }}
                                </span>
                            </div>
                        </div>

                        <div class="course-actions-col" style="display: flex; align-items: center; gap: 1.25rem; flex-shrink: 0;">
                            {{-- Ringkasan Nilai Akhir Mini --}}
                            <div style="text-align: right;">
                                <div style="font-size: 0.72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Nilai Akhir</div>
                                <div style="display: flex; align-items: center; gap: 0.35rem; justify-content: flex-end;">
                                    <span style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                                        {{ $finalScore !== null ? number_format($finalScore, 1) : '-' }}
                                    </span>
                                    <span class="badge" style="background: {{ $finalScore !== null ? '#dcfce7' : '#f1f5f9' }}; color: {{ $finalScore !== null ? '#15803d' : '#64748b' }}; font-size: 0.8rem; font-weight: 800; padding: 0.15rem 0.5rem; border-radius: 6px;">
                                        {{ $gradeLetter }}
                                    </span>
                                </div>
                            </div>

                            {{-- Tombol Toggle Buka/Tutup Nilai --}}
                            <button type="button" class="btn-open-class grade-toggle-btn" id="btn-toggle-{{ $c->id }}" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.95rem; font-size: 0.825rem; pointer-events: none;">
                                <span id="label-toggle-{{ $c->id }}">{{ $loop->first ? 'Tutup Nilai' : 'Buka Nilai' }}</span>
                                <svg id="icon-toggle-{{ $c->id }}" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.2s ease; transform: {{ $loop->first ? 'rotate(180deg)' : 'rotate(0deg)' }};">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- 2. DETAIL NILAI (LANGSUNG MUNCUL SAAT DIKLIK) --}}
                    <div id="grade-body-{{ $c->id }}" class="grade-accordion-content" style="{{ $loop->first ? 'display: block;' : 'display: none;' }}; border-top: 1px solid #f1f5f9; background: #ffffff;">
                        <div style="padding: 1.5rem 1.75rem;">
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                Detail Nilai
                            </h3>

                            @if ($c->assignments->isEmpty())
                                <p class="text-muted" style="font-size: 0.9rem; font-style: italic; padding: 1rem 0;">Belum ada tugas atau komponen evaluasi pada mata kuliah ini.</p>
                            @else
                                <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 16px;">
                                    <table class="grade-table">
                                        <thead>
                                            <tr>
                                                <th>Komponen Penilaian</th>
                                                <th style="text-align: center;">Nilai</th>
                                                <th style="text-align: center;">Bobot</th>
                                                <th style="text-align: center;">Skor</th>
                                                <th>Catatan / Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($c->assignments as $assignment)
                                                @php
                                                    $sub = $assignment->submissions->first();
                                                    $grade = $sub?->grade;
                                                    $scoreVal = $grade ? (float) $grade->score : 0;
                                                    $weightedVal = ($grade && $assignment->max_score > 0) ? round(($scoreVal / $assignment->max_score) * $equalWeight, 2) : 0;
                                                @endphp
                                                <tr>
                                                    <td style="font-weight: 600;">
                                                        <a href="{{ route('assignments.show', $assignment) }}" style="color: #0f172a; text-decoration: none;">
                                                            {{ $assignment->title }}
                                                        </a>
                                                        <div style="font-size: 0.75rem; color: #64748b; font-weight: normal; margin-top: 0.15rem;">
                                                            Tenggat: {{ $assignment->due_at->translatedFormat('d M Y, H:i') }}
                                                        </div>
                                                    </td>

                                                    <td style="text-align: center; font-weight: 700;">
                                                        @if ($grade)
                                                            <span style="color: #0f172a; font-size: 0.95rem;">{{ number_format($grade->score, 2) }}</span>
                                                        @elseif ($sub)
                                                            <span class="badge badge-warning" style="font-size: 0.7rem;">Menunggu Nilai</span>
                                                        @else
                                                            <span style="color: #94a3b8;">-</span>
                                                        @endif
                                                    </td>

                                                    <td style="text-align: center; color: #64748b; font-weight: 500;">
                                                        {{ $equalWeight }}%
                                                    </td>

                                                    <td style="text-align: center; font-weight: 700; color: #2563eb;">
                                                        {{ $grade ? number_format($weightedVal, 2) : '-' }}
                                                    </td>

                                                    <td>
                                                        @if ($grade && !empty($grade->feedback))
                                                            <span style="font-size: 0.825rem; color: #166534; font-style: italic;">"{{ $grade->feedback }}"</span>
                                                        @elseif ($grade)
                                                            <span class="badge-status-dot active" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">Selesai Dinilai</span>
                                                        @elseif ($sub)
                                                            <span class="badge" style="background: #eff6ff; color: #2563eb; font-size: 0.7rem;">Sudah Dikumpulkan</span>
                                                        @else
                                                            <span class="badge-status-dot draft" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">Belum Mengumpulkan</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        {{-- BOTTOM BAR REKAP NILAI AKHIR --}}
                        <div class="grade-final-bar">
                            <div style="display: flex; align-items: center; gap: 0.65rem; font-weight: 700; color: #0f172a; font-size: 1.05rem;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="16" height="20" x="4" y="2" rx="2"/>
                                    <line x1="8" y1="6" x2="16" y2="6"/>
                                    <line x1="16" y1="14" x2="16" y2="18"/>
                                    <path d="M16 10h.01"/>
                                    <path d="M12 10h.01"/>
                                    <path d="M8 10h.01"/>
                                    <path d="M12 14h.01"/>
                                    <path d="M8 14h.01"/>
                                    <path d="M12 18h.01"/>
                                    <path d="M8 18h.01"/>
                                </svg>
                                Nilai Akhir
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span style="font-size: 1.35rem; font-weight: 800; color: #0f172a;">
                                    {{ $finalScore !== null ? number_format($finalScore, 2) : '-' }} <span style="font-size: 0.95rem; color: #64748b; font-weight: 600;">/ 100</span>
                                </span>
                                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 1.1rem; font-weight: 800; padding: 0.25rem 0.85rem; border-radius: 10px;">
                                    {{ $gradeLetter }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

    @endif

    {{-- INTERAKSI ACCORDION / TOGGLE NILAI MATA KULIAH --}}
    <script>
        function toggleCourseGrade(courseId) {
            const targetBody = document.getElementById('grade-body-' + courseId);
            if (!targetBody) return;
            const isCurrentlyOpen = (targetBody.style.display === 'block');

            // Tutup semua panel lain agar fokus
            document.querySelectorAll('.grade-accordion-content').forEach(function(content) {
                content.style.display = 'none';
            });
            document.querySelectorAll('.grade-toggle-btn span').forEach(function(span) {
                span.textContent = 'Buka Nilai';
            });
            document.querySelectorAll('.grade-toggle-btn svg').forEach(function(svg) {
                svg.style.transform = 'rotate(0deg)';
            });
            document.querySelectorAll('.grade-accordion-card').forEach(function(card) {
                card.style.borderColor = '#e2e8f0';
                card.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.02)';
                card.classList.remove('open');
            });

            // Jika sebelumnya belum terbuka, buka panel target
            if (!isCurrentlyOpen) {
                targetBody.style.display = 'block';
                const label = document.getElementById('label-toggle-' + courseId);
                const icon = document.getElementById('icon-toggle-' + courseId);
                const card = document.getElementById('grade-card-' + courseId);
                if (label) label.textContent = 'Tutup Nilai';
                if (icon) icon.style.transform = 'rotate(180deg)';
                if (card) {
                    card.classList.add('open');
                    card.style.borderColor = '#93c5fd';
                    card.style.boxShadow = '0 6px 20px rgba(37, 99, 235, 0.08)';
                }
            }
        }
    </script>

</x-layout>
