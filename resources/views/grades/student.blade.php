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

        {{-- B. DAFTAR MATA KULIAH BERJEJER KE BAWAH DENGAN CARD NILAI INTERAKTIF & RINGKAS --}}
        <div class="grade-cards-list" style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.75rem;">
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

                <div class="grade-accordion-card card-modern clean-course-card" 
                     id="grade-card-{{ $c->id }}"
                     style="padding: 0; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03); transition: all 0.2s ease;">
                    
                    {{-- 1. HEADER KARTU: HANYA MENAMPILKAN NAMA MATA KULIAH (RINGKAS & HEMAT TEMPAT) --}}
                    <div class="grade-card-header clean-course-header" 
                         onclick="toggleCourseGrade('{{ $c->id }}')" 
                         style="cursor: pointer; padding: 0.75rem 1rem; background: #ffffff; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; user-select: none;">
                        
                        {{-- HANYA NAMA MATA KULIAH --}}
                        <h3 class="clean-course-title" style="margin: 0; font-size: 0.925rem; font-weight: 700; color: #0f172a; line-height: 1.25; flex: 1; min-width: 0;">
                            {{ $c->name }}
                        </h3>

                        {{-- Chevron Icon Indicator --}}
                        <div class="clean-course-chevron" id="chevron-box-{{ $c->id }}" style="width: 26px; height: 26px; border-radius: 7px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0; transition: transform 0.25s ease, background 0.2s ease, color 0.2s ease;">
                            <svg id="icon-toggle-{{ $c->id }}" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.25s ease;">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </div>
                    </div>

                    {{-- 2. DETAIL NILAI TERSEMBUNYI (MUNCUL SAAT DIKLIK) --}}
                    <div id="grade-body-{{ $c->id }}" class="grade-accordion-content clean-course-body" style="display: none; border-top: 1px solid #f1f5f9; background: #fafbfc; padding: 0.75rem 1rem;">
                        
                        {{-- Ringkasan Informasi Mata Kuliah & Nilai Akhir (Muncul setelah di-klik) --}}
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.65rem 0.85rem; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.25rem; flex-wrap: wrap;">
                                    <span class="badge-code-modern" style="font-size: 0.7rem; padding: 0.12rem 0.45rem;">{{ $c->code }}</span>
                                    <span class="badge" style="background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; font-size: 0.65rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 6px;">
                                        {{ $c->sks }} SKS
                                    </span>
                                    <span class="badge" style="background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.65rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 6px;">
                                        {{ number_format($attendanceRate, 0) }}% Kumpul
                                    </span>
                                </div>
                                <div style="color: #64748b; font-size: 0.78rem; display: flex; align-items: center; gap: 0.3rem;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    <span>Dosen: {{ $c->lecturer?->name ?? 'Belum Ditugaskan' }}</span>
                                </div>
                            </div>

                            <div style="text-align: right; display: flex; align-items: center; gap: 0.45rem;">
                                <div>
                                    <div style="font-size: 0.62rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Nilai Akhir</div>
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a; line-height: 1.1;">
                                        {{ $finalScore !== null ? number_format($finalScore, 1) : '-' }}
                                    </div>
                                </div>
                                <span class="badge" style="background: {{ $finalScore !== null ? '#dcfce7' : '#f1f5f9' }}; color: {{ $finalScore !== null ? '#15803d' : '#64748b' }}; font-size: 0.8rem; font-weight: 800; padding: 0.25rem 0.5rem; border-radius: 7px;">
                                    {{ $gradeLetter }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- A. Mobile List View (Khusus Layar Smartphone <= 767px) --}}
                        <div class="grade-mobile-view" style="padding: 0.75rem 0.85rem;">
                            <div style="font-size: 0.8rem; font-weight: 700; color: #475569; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                Rincian Evaluasi & Tugas ({{ $c->assignments->count() }})
                            </div>

                            @if ($c->assignments->isEmpty())
                                <p class="text-muted" style="font-size: 0.825rem; font-style: italic; padding: 0.75rem 0; margin: 0; text-align: center;">
                                    Belum ada tugas atau komponen evaluasi pada mata kuliah ini.
                                </p>
                            @else
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    @foreach ($c->assignments as $assignment)
                                        @php
                                            $sub = $assignment->submissions->first();
                                            $grade = $sub?->grade;
                                            $scoreVal = $grade ? (float) $grade->score : 0;
                                            $weightedVal = ($grade && $assignment->max_score > 0) ? round(($scoreVal / $assignment->max_score) * $equalWeight, 2) : 0;
                                        @endphp
                                        <div class="grade-assignment-item" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.65rem 0.75rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.35rem;">
                                                <div style="min-width: 0; flex: 1;">
                                                    <a href="{{ route('assignments.show', $assignment) }}" style="font-weight: 700; color: #0f172a; text-decoration: none; font-size: 0.875rem; line-height: 1.25; display: block;">
                                                        {{ $assignment->title }}
                                                    </a>
                                                    <div style="font-size: 0.7rem; color: #64748b; margin-top: 0.15rem;">
                                                        Tenggat: {{ $assignment->due_at->translatedFormat('d M Y, H:i') }}
                                                    </div>
                                                </div>
                                                <div>
                                                    @if ($grade)
                                                        <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 0.78rem; font-weight: 800; padding: 0.15rem 0.45rem; border-radius: 6px;">
                                                            {{ number_format($grade->score, 1) }}
                                                        </span>
                                                    @elseif ($sub)
                                                        <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px;">
                                                            Menunggu Nilai
                                                        </span>
                                                    @else
                                                        <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px;">
                                                            Belum Kumpul
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b; padding-top: 0.35rem; border-top: 1px dashed #f1f5f9;">
                                                <span>Bobot: <strong>{{ $equalWeight }}%</strong> &bull; Maks: {{ $assignment->max_score }}</span>
                                                <span style="font-weight: 700; color: #2563eb;">Skor: {{ $grade ? number_format($weightedVal, 2) : '-' }}</span>
                                            </div>

                                            @if ($grade && !empty($grade->feedback))
                                                <div style="margin-top: 0.35rem; padding: 0.35rem 0.5rem; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0; font-size: 0.72rem; color: #166534; font-style: italic;">
                                                    Feedback: "{{ $grade->feedback }}"
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- B. Desktop Table View (Layar Desktop / Tablet Lebar > 767px) --}}
                        <div class="grade-desktop-view" style="padding: 1.25rem 1.5rem;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                Detail Komponen Penilaian
                            </h3>

                            @if ($c->assignments->isEmpty())
                                <p class="text-muted" style="font-size: 0.875rem; font-style: italic; padding: 0.75rem 0; margin: 0;">
                                    Belum ada tugas atau komponen evaluasi pada mata kuliah ini.
                                </p>
                            @else
                                <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 14px; background: #ffffff;">
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

                        {{-- BOTTOM BAR REKAP NILAI AKHIR RINGKAS --}}
                        <div class="grade-final-bar" style="padding: 0.75rem 1rem; background: #ffffff; border-top: 1px solid #eef2f6; display: flex; align-items: center; justify-content: space-between; border-radius: 0 0 16px 16px;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: #0f172a; font-size: 0.875rem;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="16" height="20" x="4" y="2" rx="2"/>
                                    <line x1="8" y1="6" x2="16" y2="6"/>
                                    <line x1="16" y1="14" x2="16" y2="18"/>
                                    <path d="M16 10h.01"/>
                                    <path d="M12 10h.01"/>
                                    <path d="M8 10h.01"/>
                                </svg>
                                <span>Nilai Akhir Kumulatif</span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                <span style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                                    {{ $finalScore !== null ? number_format($finalScore, 2) : '-' }} <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">/ 100</span>
                                </span>
                                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 0.95rem; font-weight: 800; padding: 0.15rem 0.65rem; border-radius: 8px;">
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

            // Tutup semua panel lain agar rapi
            document.querySelectorAll('.grade-accordion-content').forEach(function(content) {
                content.style.display = 'none';
            });
            document.querySelectorAll('.grade-accordion-card').forEach(function(card) {
                card.style.borderColor = '#e2e8f0';
                card.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.03)';
                card.classList.remove('open');
            });
            document.querySelectorAll('[id^="icon-toggle-"]').forEach(function(svg) {
                svg.style.transform = 'rotate(0deg)';
            });
            document.querySelectorAll('[id^="chevron-box-"]').forEach(function(box) {
                box.style.background = '#f8fafc';
                box.style.borderColor = '#e2e8f0';
                box.style.color = '#64748b';
            });

            // Jika sebelumnya belum terbuka, buka panel target
            if (!isCurrentlyOpen) {
                targetBody.style.display = 'block';
                const icon = document.getElementById('icon-toggle-' + courseId);
                const chevronBox = document.getElementById('chevron-box-' + courseId);
                const card = document.getElementById('grade-card-' + courseId);
                
                if (icon) icon.style.transform = 'rotate(180deg)';
                if (chevronBox) {
                    chevronBox.style.background = '#eff6ff';
                    chevronBox.style.borderColor = '#bfdbfe';
                    chevronBox.style.color = '#2563eb';
                }
                if (card) {
                    card.classList.add('open');
                    card.style.borderColor = '#bfdbfe';
                    card.style.boxShadow = '0 6px 18px rgba(37, 99, 235, 0.07)';
                }
            }
        }
    </script>

</x-layout>
