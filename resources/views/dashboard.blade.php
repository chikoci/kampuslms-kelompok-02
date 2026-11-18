<x-layout title="Dashboard Akademik - KampusLMS">

    @if ($role === 'admin')
        {{-- ======================================================== --}}
        {{-- DASHBOARD ADMINISTRATOR SISTEM                           --}}
        {{-- ======================================================== --}}

        {{-- 1. HERO WELCOME ADMINISTRATOR TERPADU --}}
        <div class="admin-hero-unified">
            <div class="admin-hero-unified-left">
                <span class="admin-unified-badge">Dashboard Admin</span>
                <h1 class="admin-unified-title">Hi, {{ auth()->user()->name }}!</h1>
                <p class="admin-unified-subtitle">Ringkasan sistem dan kontrol data civitas akademika.</p>

                {{-- Tiga Card Statistik Sejajar --}}
                <div class="admin-unified-stats-row">
                    {{-- Stat 1: Pengguna --}}
                    <a href="{{ route('users.index') }}" class="admin-mini-stat-card">
                        <div class="admin-mini-stat-icon blue">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div class="admin-mini-stat-info">
                            <span class="admin-mini-stat-label">Pengguna</span>
                            <span class="admin-mini-stat-value">{{ $metrics['users_count'] }}</span>
                        </div>
                    </a>

                    {{-- Stat 2: Mata Kuliah --}}
                    <a href="{{ route('courses.index') }}" class="admin-mini-stat-card">
                        <div class="admin-mini-stat-icon green">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                <path d="M6 6h10"/>
                            </svg>
                        </div>
                        <div class="admin-mini-stat-info">
                            <span class="admin-mini-stat-label">Mata Kuliah</span>
                            <span class="admin-mini-stat-value">{{ $metrics['courses_count'] }}</span>
                        </div>
                    </a>

                    {{-- Stat 3: Tugas --}}
                    <a href="{{ route('courses.index') }}" class="admin-mini-stat-card">
                        <div class="admin-mini-stat-icon yellow">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div class="admin-mini-stat-info">
                            <span class="admin-mini-stat-label">Tugas</span>
                            <span class="admin-mini-stat-value">{{ $metrics['assignments_count'] }}</span>
                        </div>
                    </a>
                </div>

                {{-- Dua Tombol Aksi Simpel Menggunakan Simbol (+) --}}
                <div class="admin-unified-actions-row">
                    <a href="{{ route('register') }}" class="admin-btn-dark">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>Pengguna</span>
                    </a>
                    <a href="{{ route('courses.create') }}" class="admin-btn-light">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>Mata Kuliah</span>
                    </a>
                </div>
            </div>

            {{-- Ilustrasi Maskot Kavi di Sisi Kanan (Hanya Ditampilkan di Layar Desktop) --}}
            <div class="admin-hero-unified-right">
                <div class="kavi-hero-composition" style="width: 140px; height: 140px;">
                    <img src="{{ asset('images/illustrations/kavi-dashboard.png') }}" alt="Kavi Administrator" class="kavi-hero-img" style="max-height: 130px;">
                </div>
            </div>
        </div>

        {{-- 3. BARIS BAWAH: PENGGUNA TERBARU & MATA KULIAH TERBARU (2 KOLOM SEIMBANG) --}}
        <div class="admin-dashboard-grid">
            {{-- Kartu 1: Pengguna Terbaru --}}
            <div class="card-modern">
                <div class="card-header-row">
                    <h3 class="card-header-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Pengguna Terbaru
                    </h3>
                    <a href="{{ route('users.index') }}" class="card-header-link">Lihat Semua &rarr;</a>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.35rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    @foreach ($recentUsers as $usr)
                        <div class="course-card-compact" onclick="window.location='{{ route('users.show', $usr) }}'" style="margin-bottom: 0; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                            <div style="flex: 1; min-width: 0; overflow: hidden;">
                                <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                                    <h4 class="course-compact-title" style="margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $usr->name }}</h4>
                                    <span class="badge {{ $usr->role === 'admin' ? 'badge-danger' : ($usr->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.65rem; padding: 0.1rem 0.4rem; text-transform: capitalize; flex-shrink: 0;">
                                        {{ $usr->role }}
                                    </span>
                                </div>
                                <div class="course-compact-meta" style="margin-top: 0.2rem; min-width: 0; overflow: hidden;">
                                    <span style="font-size: 0.72rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">{{ $usr->email }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Kartu 2: Mata Kuliah Terbaru --}}
            <div class="card-modern">
                <div class="card-header-row">
                    <h3 class="card-header-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                            <path d="M6 6h10"/>
                            <path d="M6 10h10"/>
                        </svg>
                        Mata Kuliah Terbaru
                    </h3>
                    <a href="{{ route('courses.index') }}" class="card-header-link">Lihat Semua &rarr;</a>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.35rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    @foreach ($recentCourses as $c)
                        <div class="course-card-compact" onclick="window.location='{{ route('courses.show', $c) }}'" style="margin-bottom: 0; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                            <div style="flex: 1; min-width: 0; overflow: hidden;">
                                <h4 class="course-compact-title" style="margin: 0 0 0.2rem 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $c->name }}</h4>
                                <div class="course-compact-meta" style="min-width: 0; overflow: hidden;">
                                    <span class="badge-code-modern" style="font-size: 0.7rem; padding: 0.12rem 0.45rem; flex-shrink: 0;">{{ $c->code }}</span>
                                    <span style="font-size: 0.72rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">&bull; Dosen: {{ $c->lecturer?->name ?? 'Belum Ditugaskan' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    @elseif ($role === 'dosen')
        {{-- ======================================================== --}}
        {{-- DASHBOARD DOSEN PENGAMPU                                 --}}
        {{-- ======================================================== --}}

        {{-- BARIS ATAS: HERO BANNER & NOTIFICATIONS DOSEN --}}
        <div class="dashboard-top-grid">
            
            {{-- A. HERO WELCOME SECTION --}}
            <div class="hero-welcome-card">
                <div class="hero-welcome-left">
                    <div class="hero-welcome-text">
                        <span class="badge badge-primary" style="margin-bottom: 0.5rem; display: inline-block; font-size: 0.75rem; font-weight: 700;">Dashboard Dosen</span>
                        <h1 class="hero-welcome-title">Hi, {{ auth()->user()->name }}!</h1>
                        <p class="hero-welcome-subtitle">Kelola mata kuliah, materi ajar, dan evaluasi tugas mahasiswa pengampu Anda.</p>
                    </div>

                    <div class="hero-metric-row">
                        {{-- Mata Kuliah Diampu --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-text">
                                <span class="label">Mata Kuliah Diampu</span>
                                <span class="value">{{ $metrics['courses_count'] }}</span>
                            </div>
                        </div>

                        {{-- Total Tugas Dikumpulkan --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-text">
                                <span class="label">Tugas Dikumpulkan</span>
                                <span class="value">{{ $metrics['total_submissions'] ?? ($metrics['total_assignments'] ?? 0) }}</span>
                            </div>
                        </div>

                        {{-- Menunggu Penilaian --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-text">
                                <span class="label">Menunggu Penilaian</span>
                                <span class="value">{{ $metrics['pending_grading_count'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Komposisi Maskot KAVI untuk Dosen (Clean & Ringan) --}}
                <div class="hero-welcome-right">
                    <div class="kavi-hero-composition" style="width: 150px; height: 150px;">
                        <img src="{{ asset('images/illustrations/kavi-belajar.png') }}" alt="Kavi Dosen Pengampu" class="kavi-hero-img" style="max-height: 140px;">
                    </div>
                </div>
            </div>

            {{-- B. NOTIFICATIONS PANEL DOSEN --}}
            <div class="card-modern dashboard-notif-panel" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="card-header-row" style="margin-bottom: 1rem;">
                        <h3 class="card-header-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                            </svg>
                            Notifications
                        </h3>
                        <a href="{{ route('notifications.index') }}" class="card-header-link">See all &rarr;</a>
                    </div>

                    @php
                        $userNotifications = auth()->user()->notifications()->latest()->take(3)->get();
                    @endphp

                    @forelse ($userNotifications as $notif)
                        @php
                            $notifData = $notif->data;
                            $url = $notifData['url'] ?? route('notifications.index');
                        @endphp
                        <a href="{{ $url }}" class="notif-card-item">
                            <div class="notif-icon-box yellow">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.85rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $notifData['title'] ?? ($notifData['assignment_title'] ?? 'Pemberitahuan Tugas / Pengumpulan') }}
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b;">
                                    {{ $notif->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    @empty
                        <div style="text-align: center; padding: 2rem 1rem; color: #64748b; font-size: 0.9rem;">
                            Belum ada notifikasi baru untuk Anda.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- BAGIAN BAWAH DOSEN: MATA KULIAH ANDA & PENGUMPULAN BELUM DINILAI --}}
        <div class="dashboard-bottom-grid">
            <div class="card-modern">
                <div class="card-header-row">
                    <h3 class="card-header-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                            <path d="M6 6h10"/>
                            <path d="M6 10h10"/>
                        </svg>
                        Mata Kuliah Anda
                    </h3>
                    <a href="{{ route('courses.index') }}" class="card-header-link">Lihat Semua &rarr;</a>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.35rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    @forelse ($myCourses as $course)
                        <div class="course-card-compact" onclick="window.location='{{ route('courses.show', $course) }}'" style="margin-bottom: 0; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                            <div style="flex: 1; min-width: 0; display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; overflow: hidden;">
                                <h4 class="course-compact-title" style="margin: 0; font-size: 0.875rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $course->name }}</h4>
                                <span class="badge-code-modern" style="font-size: 0.7rem; padding: 0.12rem 0.45rem; flex-shrink: 0;">[{{ $course->code }}]</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted" style="text-align: center; padding: 1.5rem 0; margin: 0; font-size: 0.85rem;">Anda belum ditugaskan untuk mengampu mata kuliah.</p>
                    @endforelse
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header-row">
                    <h3 class="card-header-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        Pengumpulan Belum Dinilai
                    </h3>
                </div>

                @php
                    $pendingByCourse = $pendingSubmissions->groupBy(fn ($item) => $item->assignment?->course_id);
                @endphp

                <div style="display: flex; flex-direction: column; gap: 0.35rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    @forelse ($pendingByCourse as $cId => $courseSubmissions)
                        @php
                            $targetCourse = $courseSubmissions->first()?->assignment?->course;
                            $countPending = $courseSubmissions->count();
                        @endphp
                        @if ($targetCourse)
                            <div class="course-card-compact" onclick="window.location='{{ route('courses.submissions', $targetCourse) }}'" title="Lihat pengumpulan {{ $targetCourse->name }}" style="margin-bottom: 0; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                                <div style="flex: 1; min-width: 0; display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; overflow: hidden;">
                                    <h4 class="course-compact-title" style="margin: 0; font-size: 0.875rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $targetCourse->name }}</h4>
                                    <span class="badge-code-modern" style="font-size: 0.7rem; padding: 0.12rem 0.45rem; flex-shrink: 0;">({{ $targetCourse->code }})</span>
                                </div>
                                <div style="flex-shrink: 0;">
                                    <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px; white-space: nowrap;">
                                        {{ $countPending }} Mahasiswa
                                    </span>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div style="text-align: center; padding: 1.5rem 1rem; border: 1px dashed #e2e8f0; border-radius: 10px; background: #f8fafc;">
                            <div style="font-weight: 600; color: #0f172a; font-size: 0.85rem;">Semua Selesai Dinilai</div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">Tidak ada tugas yang menunggu evaluasi.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    @else
        {{-- ======================================================== --}}
        {{-- DASHBOARD MAHASISWA (REDESIGN SESUAI REFERENSI)         --}}
        {{-- ======================================================== --}}

        {{-- BARIS ATAS: HERO BANNER & NOTIFICATIONS --}}
        <div class="dashboard-top-grid">
            
            {{-- A. HERO WELCOME SECTION --}}
            <div class="hero-welcome-card">
                <div class="hero-welcome-left">
                    <div class="hero-welcome-text">
                        <span class="badge badge-primary" style="margin-bottom: 0.5rem; display: inline-block; font-size: 0.75rem; font-weight: 700;">Dashboard Mahasiswa</span>
                        <h1 class="hero-welcome-title">Hi, {{ auth()->user()->name }}!</h1>
                        <p class="hero-welcome-subtitle">Selamat datang di Kampus LMS!</p>
                    </div>

                    {{-- 3 Kartu Metrik Ringkas Mahasiswa (Simple: Teks Label + Angka Total) --}}
                    <div class="hero-metric-row">
                        {{-- Mata Kuliah yang Diikuti --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-text">
                                <span class="label">Mata Kuliah yang Diikuti</span>
                                <span class="value">{{ $metrics['courses_count'] }}</span>
                            </div>
                        </div>

                        {{-- Tugas Mendatang --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-text">
                                <span class="label">Tugas Mendatang</span>
                                <span class="value">{{ $metrics['upcoming_count'] }}</span>
                            </div>
                        </div>

                        {{-- Tugas Terkumpul --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-text">
                                <span class="label">Tugas Terkumpul</span>
                                <span class="value">{{ $metrics['submitted_count'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Komposisi Maskot KAVI untuk Mahasiswa (Clean & Ringan) --}}
                <div class="hero-welcome-right">
                    <div class="kavi-hero-composition" style="width: 150px; height: 150px;">
                        <img src="{{ asset('images/illustrations/kavi-dashboard.png') }}" alt="Kavi Maskot KampusLMS" class="kavi-hero-img" style="max-height: 140px;">
                    </div>
                </div>
            </div>

            {{-- B. NOTIFICATIONS PANEL --}}
            <div class="card-modern dashboard-notif-panel" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="card-header-row" style="margin-bottom: 1rem;">
                        <h3 class="card-header-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                            </svg>
                            Notifications
                        </h3>
                        <a href="{{ route('notifications.index') }}" class="card-header-link">See all &rarr;</a>
                    </div>

                    @php
                        $userNotifications = auth()->user()->notifications()->latest()->take(3)->get();
                    @endphp

                    @forelse ($userNotifications as $notif)
                        @php
                            $notifData = $notif->data;
                            $notifType = $notifData['type'] ?? '';
                            $isAssignment = ($notifType === 'assignment_published');
                            $url = $notifData['url'] ?? route('notifications.index');
                        @endphp
                        <a href="{{ $url }}" class="notif-card-item">
                            <div class="notif-icon-box {{ $isAssignment ? 'yellow' : 'blue' }}">
                                @if ($isAssignment)
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                @else
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 14 14"/>
                                    </svg>
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.85rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $isAssignment ? ($notifData['title'] ?? 'Tugas Perkuliahan Baru') : ($notifData['assignment_title'] ?? 'Nilai Tugas Keluar') }}
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b;">
                                    {{ $notif->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    @empty
                        <div style="text-align: center; padding: 2rem 1rem; color: #64748b; font-size: 0.9rem;">
                            Belum ada notifikasi baru untuk Anda.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- C. BAGIAN BAWAH: HANYA DUA SECTION UTAMA --}}
        <div class="dashboard-bottom-grid">
            
            {{-- 1. MATAKULIAH ANDA --}}
            <div class="card-modern">
                <div class="card-header-row">
                    <h3 class="card-header-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                            <path d="M6 6h10"/>
                            <path d="M6 10h10"/>
                        </svg>
                        Matakuliah Anda
                    </h3>
                    <a href="{{ route('courses.index') }}" class="card-header-link">Lihat Semua &rarr;</a>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.35rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    @forelse ($enrolledCourses as $course)
                        <div class="course-card-compact" onclick="window.location='{{ route('courses.show', $course) }}'" style="margin-bottom: 0; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                            <div style="flex: 1; min-width: 0; display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; overflow: hidden;">
                                <h4 class="course-compact-title" style="margin: 0; font-size: 0.875rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $course->name }}</h4>
                                <span class="badge-code-modern" style="font-size: 0.7rem; padding: 0.12rem 0.45rem; flex-shrink: 0;">[{{ $course->code }}]</span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-card" style="padding: 1.5rem 1rem;">
                            <p class="empty-state-text">Anda belum terdaftar pada mata kuliah apapun.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- 2. TUGAS MENDATANG (LIST RINGKAS: JUDUL TUGAS UTAMA, KURSUS & KODE DI BAWAHNYA) --}}
            <div class="card-modern">
                <div class="card-header-row">
                    <h3 class="card-header-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        Tugas Mendatang
                    </h3>
                    <a href="{{ route('courses.index') }}" class="card-header-link">Lihat Semua &rarr;</a>
                </div>

                @if ($upcomingAssignments->isNotEmpty())
                    <div style="display: flex; flex-direction: column; gap: 0.35rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                        @foreach ($upcomingAssignments as $assignment)
                            <div class="course-card-compact" onclick="window.location='{{ route('assignments.show', $assignment) }}'" style="margin-bottom: 0; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0.55rem 0.75rem; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                                <div style="flex: 1; min-width: 0; overflow: hidden;">
                                    {{-- Nama Tugas Utama --}}
                                    <div style="font-size: 0.85rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 0.15rem;">
                                        {{ $assignment->title }}
                                    </div>
                                    {{-- Mata Kuliah & Kode di Bawahnya (Kecil) --}}
                                    <div style="font-size: 0.72rem; color: #64748b; display: flex; align-items: center; gap: 0.35rem; min-width: 0; overflow: hidden;">
                                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0;">{{ $assignment->course?->name }}</span>
                                        @if ($assignment->course?->code)
                                            <span class="badge-code-modern" style="font-size: 0.65rem; padding: 0.08rem 0.35rem; flex-shrink: 0;">({{ $assignment->course?->code }})</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px; flex-shrink: 0; white-space: nowrap;">
                                    {{ $assignment->due_at->translatedFormat('d M, H:i') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Empty State dengan Ilustrasi Kalender Sesuai Referensi Visual --}}
                    <div class="empty-state-card" style="padding: 1.5rem 1rem;">
                        <img src="{{ asset('images/illustrations/calendar-empty.png') }}" alt="Tidak Ada Tugas Mendatang" class="empty-state-img" style="max-height: 50px;">
                        <p class="empty-state-text" style="font-size: 0.825rem;">Tidak ada tugas mendatang saat ini. Kerja bagus!</p>
                    </div>
                @endif
            </div>

        </div>

    @endif

</x-layout>
