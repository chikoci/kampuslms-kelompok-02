<x-layout title="Nilai Saya - KampusLMS">

    {{-- A. PAGE HEADER DENGAN ILUSTRASI BERUANG NILAI A+ --}}
    <div class="page-banner-card">
        <div>
            <h1 class="page-banner-title">Nilai Saya</h1>
            <p class="page-banner-subtitle">Lihat dan pantau nilai dari seluruh mata kuliah yang Anda ikuti.</p>
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

        {{-- B. COURSE SELECTOR (KARTU MATA KULIAH DI BAGIAN ATAS) --}}
        <div class="course-selector-grid">
            @foreach ($courses as $c)
                <div class="course-selector-card {{ $loop->first ? 'active' : '' }}" 
                     data-target="course-grade-{{ $c->id }}"
                     onclick="switchGradeCourse('course-grade-{{ $c->id }}', this)">
                    <div>
                        <span class="badge-code-modern" style="margin-bottom: 0.4rem; font-size: 0.7rem;">{{ $c->code }}</span>
                        <h4 style="margin: 0.25rem 0 0 0; font-size: 0.95rem; font-weight: 700; color: #0f172a; line-height: 1.3;">
                            {{ $c->name }}
                        </h4>
                    </div>
                    <div class="course-selector-arrow">&gt;</div>
                </div>
            @endforeach
        </div>

        {{-- C. DETAIL NILAI CONTAINER PER MATA KULIAH --}}
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

            <div id="course-grade-{{ $c->id }}" 
                 class="course-grade-panel card-modern" 
                 style="padding: 0; overflow: hidden; {{ $loop->first ? 'display: block;' : 'display: none;' }}">
                
                {{-- Panel Top Header --}}
                <div style="padding: 1.5rem 1.75rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem; flex-wrap: wrap;">
                            <span class="badge-code-modern">{{ $c->code }}</span>
                            <span style="font-size: 0.8rem; color: #64748b;">Dosen: {{ $c->lecturer?->name ?? '-' }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                            <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0;">{{ $c->name }}</h2>
                            <span class="badge" style="background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; font-size: 0.8rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                {{ $c->sks }} SKS
                            </span>
                            <span class="badge" style="background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.8rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                    <polyline points="17 6 23 6 23 12"/>
                                </svg>
                                {{ number_format($attendanceRate, 1) }}% Pengumpulan Tugas
                            </span>
                        </div>
                    </div>

                    <span class="badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 0.8rem; font-weight: 600; padding: 0.35rem 0.85rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        Semester Aktif
                    </span>
                </div>

                <div style="padding: 1.5rem 1.75rem;">
                    {{-- DETAIL KOMPONEN PENILAIAN --}}
                    <div>
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
        @endforeach

    @endif

    {{-- INTERAKSI SWITCH COURSE DI NILAI SAYA --}}
    <script>
        function switchGradeCourse(targetId, clickedCard) {
            // Hapus status active dari semua selector card
            document.querySelectorAll('.course-selector-card').forEach(function(card) {
                card.classList.remove('active');
            });
            // Aktifkan card yang diklik
            clickedCard.classList.add('active');

            // Sembunyikan seluruh panel detail nilai
            document.querySelectorAll('.course-grade-panel').forEach(function(panel) {
                panel.style.display = 'none';
            });

            // Tampilkan panel yang dipilih
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.style.display = 'block';
            }
        }
    </script>

</x-layout>
