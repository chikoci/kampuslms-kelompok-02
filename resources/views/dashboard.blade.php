<x-layout title="Dashboard Akademik - KampusLMS">

    @if ($role === 'admin')
        {{-- ======================================================== --}}
        {{-- DASHBOARD ADMINISTRATOR SISTEM                           --}}
        {{-- ======================================================== --}}

        {{-- BARIS ATAS: HERO BANNER & NOTIFICATIONS ADMIN --}}
        <div class="dashboard-top-grid">
            
            {{-- A. HERO WELCOME SECTION --}}
            <div class="hero-welcome-card">
                <div class="hero-welcome-left">
                    <div class="hero-welcome-text">
                        <h1 class="hero-welcome-title">Hi, {{ auth()->user()->name }}!</h1>
                        <p class="hero-welcome-subtitle">Ringkasan manajemen sistem, infrastruktur data, dan kontrol pengguna KampusLMS.</p>
                    </div>

                    <div class="hero-metric-row">
                        {{-- Total Pengguna --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon blue">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Total<br>Pengguna</span>
                                <span class="value">{{ $metrics['users_count'] }}</span>
                            </div>
                        </div>

                        {{-- Total Mata Kuliah --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                    <path d="M6 6h10"/>
                                    <path d="M6 10h10"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Mata Kuliah<br>Terdaftar</span>
                                <span class="value">{{ $metrics['courses_count'] }}</span>
                            </div>
                        </div>

                        {{-- Tugas & Evaluasi --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon yellow">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Tugas &<br>Evaluasi</span>
                                <span class="value">{{ $metrics['assignments_count'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="hero-welcome-actions" style="display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap;">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm" style="border-radius: 9999px; padding: 0.4rem 1.15rem; font-size: 0.8rem;">+ Daftarkan Pengguna</a>
                        <a href="{{ route('courses.create') }}" class="btn btn-secondary btn-sm" style="border-radius: 9999px; padding: 0.4rem 1.15rem; font-size: 0.8rem;">+ Tambah Mata Kuliah</a>
                    </div>
                </div>

                {{-- Integrasi Komposisi Maskot KAVI untuk Admin --}}
                <div class="hero-welcome-right">
                    <div class="kavi-hero-composition">
                        <div class="kavi-hero-blob"></div>
                        <div class="kavi-sparkle s1">&#10022;</div>
                        <div class="kavi-sparkle s2">&#10022;</div>
                        <div class="kavi-sparkle s3">&#10022;</div>
                        <img src="{{ asset('images/illustrations/kavi-dashboard.png') }}" alt="Kavi Administrator" class="kavi-hero-img">
                        <div class="kavi-pedestal-shadow"></div>
                    </div>
                </div>
            </div>

            {{-- B. NOTIFICATIONS PANEL ADMIN --}}
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
                            <div class="notif-icon-box blue">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.85rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $notifData['title'] ?? ($notifData['assignment_title'] ?? 'Pemberitahuan Sistem') }}
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

        {{-- BAGIAN BAWAH: PENGGUNA TERBARU & MATA KULIAH TERBARU --}}
        <div class="dashboard-bottom-grid">
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
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach ($recentUsers as $usr)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border-radius: 12px; background: #f8fafc; border: 1px solid #eef2f6; cursor: pointer; transition: all 0.15s ease;" onclick="window.location='{{ route('users.show', $usr) }}'">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem; color: #0f172a;">{{ $usr->name }}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">{{ $usr->email }}</div>
                            </div>
                            <span class="badge {{ $usr->role === 'admin' ? 'badge-danger' : ($usr->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.7rem; text-transform: capitalize;">
                                {{ $usr->role }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

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
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach ($recentCourses as $c)
                        <div class="course-card-item" onclick="window.location='{{ route('courses.show', $c) }}'" style="cursor: pointer;">
                            <div class="course-info-col">
                                <span class="badge-code-modern">{{ $c->code }}</span>
                                <div style="font-weight: 600; font-size: 0.9rem; color: #0f172a; margin-top: 0.25rem;">{{ $c->name }}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">Dosen: {{ $c->lecturer?->name ?? 'Belum Ditugaskan' }}</div>
                            </div>
                            <a href="{{ route('courses.show', $c) }}" class="btn-open-class" onclick="event.stopPropagation()">Buka &rarr;</a>
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
                        <h1 class="hero-welcome-title">Hi, {{ auth()->user()->name }}!</h1>
                        <p class="hero-welcome-subtitle">Kelola mata kuliah, materi ajar, dan evaluasi tugas mahasiswa pengampu Anda.</p>
                    </div>

                    <div class="hero-metric-row">
                        {{-- Mata Kuliah Diampu --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon blue">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                    <path d="M6 6h10"/>
                                    <path d="M6 10h10"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Mata Kuliah<br>Diampu</span>
                                <span class="value">{{ $metrics['courses_count'] }}</span>
                            </div>
                        </div>

                        {{-- Total Tugas --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Total Tugas<br>Diterbitkan</span>
                                <span class="value">{{ $metrics['total_assignments'] }}</span>
                            </div>
                        </div>

                        {{-- Menunggu Penilaian --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon yellow">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Menunggu<br>Penilaian</span>
                                <span class="value">{{ $metrics['pending_grading_count'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Integrasi Komposisi Maskot KAVI untuk Dosen --}}
                <div class="hero-welcome-right">
                    <div class="kavi-hero-composition">
                        <div class="kavi-hero-blob"></div>
                        <div class="kavi-sparkle s1">&#10022;</div>
                        <div class="kavi-sparkle s2">&#10022;</div>
                        <div class="kavi-sparkle s3">&#10022;</div>
                        <img src="{{ asset('images/illustrations/kavi-belajar.png') }}" alt="Kavi Dosen Pengampu" class="kavi-hero-img">
                        <div class="kavi-pedestal-shadow"></div>
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
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @forelse ($myCourses as $course)
                        <div class="course-card-item" onclick="window.location='{{ route('courses.show', $course) }}'" style="cursor: pointer;">
                            <div class="course-info-col">
                                <span class="badge-code-modern">{{ $course->code }}</span>
                                <h4 class="course-title">{{ $course->name }}</h4>
                                <div class="course-meta-text">
                                    <span>{{ $course->students_count }} Mahasiswa</span> &bull;
                                    <span>{{ $course->sks }} SKS</span>
                                </div>
                            </div>
                            <a href="{{ route('courses.show', $course) }}" class="btn-open-class" onclick="event.stopPropagation()">Buka Kelas &rarr;</a>
                        </div>
                    @empty
                        <p class="text-muted" style="text-align: center; padding: 2rem 0;">Anda belum ditugaskan untuk mengampu mata kuliah.</p>
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
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @forelse ($pendingSubmissions as $submission)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border-radius: 12px; background: #f8fafc; border: 1px solid #eef2f6; cursor: pointer; transition: all 0.15s ease;" onclick="window.location='{{ route('submissions.show', $submission) }}'">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem; color: #0f172a;">{{ $submission->student?->name }}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">{{ $submission->assignment?->title }} ({{ $submission->assignment?->course?->code }})</div>
                            </div>
                            <a href="{{ route('submissions.show', $submission) }}" class="btn-open-class" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;" onclick="event.stopPropagation()">Beri Nilai &rarr;</a>
                        </div>
                    @empty
                        <p class="text-muted" style="text-align: center; padding: 2rem 0;">Semua pengumpulan sudah selesai dinilai. Kerja bagus!</p>
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
                        <h1 class="hero-welcome-title">Hi, {{ auth()->user()->name }}!</h1>
                        <p class="hero-welcome-subtitle">Selamat datang di Kampus LMS!</p>
                    </div>

                    <div class="hero-metric-row">
                        {{-- Mata Kuliah yang Diikuti --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon blue">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                    <path d="M6 6h10"/>
                                    <path d="M6 10h10"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Mata Kuliah<br>yang Diikuti</span>
                                <span class="value">{{ $metrics['courses_count'] }}</span>
                            </div>
                        </div>

                        {{-- Tugas Mendatang --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon yellow">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Tugas<br>Mendatang</span>
                                <span class="value">{{ $metrics['upcoming_count'] }}</span>
                            </div>
                        </div>

                        {{-- Tugas Terkumpul --}}
                        <div class="hero-metric-item">
                            <div class="hero-metric-icon green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </div>
                            <div class="hero-metric-text">
                                <span class="label">Tugas<br>Terkumpul</span>
                                <span class="value">{{ $metrics['submitted_count'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Integrasi Komposisi Maskot KAVI --}}
                <div class="hero-welcome-right">
                    <div class="kavi-hero-composition">
                        <div class="kavi-hero-blob"></div>
                        <div class="kavi-sparkle s1">&#10022;</div>
                        <div class="kavi-sparkle s2">&#10022;</div>
                        <div class="kavi-sparkle s3">&#10022;</div>
                        <img src="{{ asset('images/illustrations/kavi-dashboard.png') }}" alt="Kavi Maskot KampusLMS" class="kavi-hero-img">
                        <div class="kavi-pedestal-shadow"></div>
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

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @forelse ($enrolledCourses as $course)
                        {{-- Sesuai aturan: TIDAK MENGGUNAKAN LOGO/ICON BESAR DI SEBELAH KIRI CARD --}}
                        <div class="course-card-item" onclick="window.location='{{ route('courses.show', $course) }}'" style="cursor: pointer;">
                            <div class="course-info-col">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="badge-code-modern">{{ $course->code }}</span>
                                    <span style="font-size: 0.75rem; color: #64748b;">{{ $course->sks }} SKS</span>
                                </div>
                                <h4 class="course-title">{{ $course->name }}</h4>
                                <div class="course-meta-text">
                                    <span>Dosen: {{ $course->lecturer?->name ?? 'Belum Ditugaskan' }}</span>
                                </div>
                            </div>
                            <div class="course-actions-col">
                                <a href="{{ route('courses.show', $course) }}" class="btn-open-class" onclick="event.stopPropagation()">
                                    Buka Kelas &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-card">
                            <p class="empty-state-text">Anda belum terdaftar pada mata kuliah apapun.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- 2. TUGAS MENDATANG --}}
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
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @foreach ($upcomingAssignments as $assignment)
                            <div class="course-card-item" onclick="window.location='{{ route('assignments.show', $assignment) }}'" style="cursor: pointer;">
                                <div class="course-info-col">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span class="badge badge-warning" style="font-size: 0.7rem;">
                                            Tenggat: {{ $assignment->due_at->translatedFormat('d M Y, H:i') }}
                                        </span>
                                    </div>
                                    <h4 class="course-title">
                                        <a href="{{ route('assignments.show', $assignment) }}" style="color: inherit; text-decoration: none;">
                                            {{ $assignment->title }}
                                        </a>
                                    </h4>
                                    <div class="course-meta-text">
                                        <span>Mata Kuliah: {{ $assignment->course?->name }}</span>
                                    </div>
                                </div>
                                <div class="course-actions-col">
                                    <a href="{{ route('assignments.show', $assignment) }}" class="btn-open-class" onclick="event.stopPropagation()">
                                        Kumpulkan &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Empty State dengan Ilustrasi Kalender Sesuai Referensi Visual --}}
                    <div class="empty-state-card" style="padding: 2.75rem 1.5rem;">
                        <img src="{{ asset('images/illustrations/calendar-empty.png') }}" alt="Tidak Ada Tugas Mendatang" class="empty-state-img">
                        <p class="empty-state-text">Tidak ada tugas mendatang saat ini. Kerja bagus!</p>
                    </div>
                @endif
            </div>

        </div>

    @endif

</x-layout>
