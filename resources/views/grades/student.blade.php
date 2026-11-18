<x-layout title="Nilai Saya - KampusLMS"
    :backUrl="route('dashboard')"
    :breadcrumbs="[
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
            <img src="{{ asset('images/illustrations/kavi-nilai.png') }}" alt="Kavi Meraih Prestasi" class="kavi-banner-img">
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="card-modern empty-state-card">
            <img src="{{ asset('images/illustrations/calendar-empty.png') }}" alt="Belum Terdaftar" class="empty-state-img">
            <h3 style="margin-bottom: 0.5rem; color: #1e293b; font-size: 1.15rem; font-weight: 700;">Belum Terdaftar di Mata Kuliah</h3>
            <p class="empty-state-text">Anda belum terdaftar pada mata kuliah apa pun saat ini.</p>
        </div>
    @else

        {{-- B. DAFTAR MATA KULIAH BERJEJER KE BAWAH (RINGKAS & TIDAK BERJARAK BERLEBIHAN) --}}
        <div class="grade-cards-list" style="display: flex; flex-direction: column; gap: 0.35rem; margin-top: 0.5rem;">
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
                @endphp

                <div class="grade-accordion-card clean-course-card" 
                     id="grade-card-{{ $c->id }}"
                     style="padding: 0; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 10px; margin: 0; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); transition: all 0.15s ease;">
                    
                    {{-- 1. HEADER KARTU: HANYA MENAMPILKAN NAMA MATA KULIAH (RINGKAS & RAPAT) --}}
                    <div class="grade-card-header clean-course-header" 
                         onclick="toggleCourseGrade('{{ $c->id }}')" 
                         style="cursor: pointer; padding: 0.65rem 1rem; background: #ffffff; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; user-select: none;">
                        
                        {{-- HANYA NAMA MATA KULIAH --}}
                        <h3 class="clean-course-title" style="margin: 0; font-size: 0.925rem; font-weight: 700; color: #0f172a; line-height: 1.25; flex: 1; min-width: 0;">
                            {{ $c->name }}
                        </h3>

                        {{-- Chevron Icon Indicator --}}
                        <div class="clean-course-chevron" id="chevron-box-{{ $c->id }}" style="width: 24px; height: 24px; border-radius: 6px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0; transition: transform 0.25s ease, background 0.2s ease, color 0.2s ease;">
                            <svg id="icon-toggle-{{ $c->id }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.25s ease;">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </div>
                    </div>

                    {{-- 2. DETAIL NILAI TERSEMBUNYI (FOKUS HANYA NAMA TUGAS & NILAI) --}}
                    <div id="grade-body-{{ $c->id }}" class="grade-accordion-content clean-course-body" style="display: none; border-top: 1px solid #f1f5f9; background: #fafbfc; padding: 0.6rem 0.85rem;">
                        
                        {{-- Ringkasan Nilai Akhir Kumulatif Tepat di Atas (Tidak Perlu Scroll ke Bawah) --}}
                        @if ($finalScore !== null)
                            <div style="margin-bottom: 0.5rem; padding: 0.45rem 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; font-size: 0.825rem; color: #334155;">
                                <span style="font-weight: 600; color: #475569;">Nilai Akhir:</span>
                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                    <strong style="color: #0f172a; font-size: 0.95rem;">{{ number_format($finalScore, 1) }}</strong>
                                    <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 800; padding: 0.1rem 0.4rem; border-radius: 5px;">{{ $gradeLetter }}</span>
                                </div>
                            </div>
                        @endif

                        @if ($c->assignments->isEmpty())
                            <p class="text-muted" style="font-size: 0.825rem; font-style: italic; padding: 0.75rem 0; margin: 0; text-align: center;">
                                Belum ada tugas pada mata kuliah ini.
                            </p>
                        @else
                            {{-- Daftar Tugas & Nilai (Nama Tugas di Kiri, Nilai di Kanan) --}}
                            <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                @foreach ($c->assignments as $assignment)
                                    @php
                                        $sub = $assignment->submissions->first();
                                        $grade = $sub?->grade;
                                    @endphp
                                    <div class="grade-assignment-item" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.55rem 0.75rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                        {{-- Nama Tugas & Catatan Feedback --}}
                                        <div style="flex: 1; min-width: 0;">
                                            <a href="{{ route('assignments.show', $assignment) }}" style="font-weight: 600; color: #0f172a; text-decoration: none; font-size: 0.875rem; line-height: 1.3; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ $assignment->title }}
                                            </a>
                                            @if ($grade && !empty($grade->feedback))
                                                <div style="font-size: 0.72rem; color: #166534; font-style: italic; margin-top: 0.15rem;">
                                                    {{ $grade->feedback }}
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Nilai yang Diperoleh di Sisi Kanan --}}
                                        <div style="flex-shrink: 0; display: flex; align-items: center;">
                                            @if ($grade)
                                                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 0.85rem; font-weight: 800; padding: 0.18rem 0.55rem; border-radius: 6px;">
                                                    {{ number_format($grade->score, 1) }}
                                                </span>
                                            @elseif ($sub)
                                                <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px;">
                                                    Menunggu
                                                </span>
                                            @else
                                                <span class="badge" style="background: #f1f5f9; color: #94a3b8; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px;">
                                                    -
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
