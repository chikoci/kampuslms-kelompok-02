<x-layout title="Dashboard Akademik - KampusLMS">

    @if ($role === 'admin')
        {{-- ======================================================== --}}
        {{-- DASHBOARD ADMINISTRATOR SISTEM                           --}}
        {{-- ======================================================== --}}
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1>Dashboard Administrator</h1>
                    <p>Ringkasan manajemen sistem, infrastruktur data, dan kontrol pengguna KampusLMS</p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="{{ route('register') }}" class="btn btn-primary">+ Daftarkan Pengguna</a>
                    <a href="{{ route('courses.create') }}" class="btn btn-secondary">+ Tambah Mata Kuliah</a>
                </div>
            </div>
        </div>

        <div class="metric-grid">
            <a href="{{ route('users.index') }}" class="dashboard-card-link">
                <div class="card card-accent-blue">
                    <p class="metric-label label-blue">Total Pengguna</p>
                    <p class="metric-value">{{ $metrics['users_count'] }}</p>
                    <p class="metric-sub">{{ $metrics['students_count'] }} Mahasiswa &bull; {{ $metrics['lecturers_count'] }} Dosen</p>
                </div>
            </a>

            <a href="{{ route('courses.index') }}" class="dashboard-card-link">
                <div class="card card-accent-green">
                    <p class="metric-label label-green">Mata Kuliah</p>
                    <p class="metric-value">{{ $metrics['courses_count'] }}</p>
                    <p class="metric-sub">{{ $metrics['materials_count'] }} Materi Terpublikasi</p>
                </div>
            </a>

            <div class="card card-accent-amber">
                <p class="metric-label label-amber">Tugas & Evaluasi</p>
                <p class="metric-value">{{ $metrics['assignments_count'] }}</p>
                <p class="metric-sub">{{ $metrics['submissions_count'] }} Berkas Pengumpulan</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
            {{-- Pengguna Terbaru --}}
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700;">Pengguna Terbaru</h3>
                    <a href="{{ route('users.index') }}" style="font-size: 0.85rem; color: #2563eb; text-decoration: none;">Lihat Semua &rarr;</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentUsers as $u)
                                <tr>
                                    <td><strong>{{ $u->name }}</strong></td>
                                    <td>
                                        <span class="badge {{ $u->role === 'admin' ? 'badge-danger' : ($u->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}">
                                            {{ ucfirst($u->role) }}
                                        </span>
                                    </td>
                                    <td>{{ $u->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mata Kuliah Terbaru --}}
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700;">Mata Kuliah Aktif</h3>
                    <a href="{{ route('courses.index') }}" style="font-size: 0.85rem; color: #2563eb; text-decoration: none;">Lihat Semua &rarr;</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>Dosen Pengampu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentCourses as $c)
                                <tr>
                                    <td><code>{{ $c->code }}</code></td>
                                    <td><a href="{{ route('courses.show', $c) }}" style="color: #2563eb; font-weight: 600; text-decoration: none;">{{ $c->name }}</a></td>
                                    <td>{{ $c->lecturer->name ?? 'Belum Ditentukan' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif ($role === 'dosen')
        {{-- ======================================================== --}}
        {{-- DASHBOARD DOSEN PENGAMPU                                 --}}
        {{-- ======================================================== --}}
        <div class="page-header">
            <h1>Dashboard Dosen Pengampu</h1>
            <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong> (NIP: {{ auth()->user()->nim_nip ?? '-' }})</p>
        </div>

        <div class="metric-grid">
            <a href="{{ route('courses.index') }}" class="dashboard-card-link">
                <div class="card card-accent-blue">
                    <p class="metric-label label-blue">Mata Kuliah Diampu</p>
                    <p class="metric-value">{{ $metrics['courses_count'] }}</p>
                    <p class="metric-sub">{{ $metrics['total_students'] }} Mahasiswa Terdaftar</p>
                </div>
            </a>

            <div class="card card-accent-green">
                <p class="metric-label label-green">Tugas Dibuat</p>
                <p class="metric-value">{{ $metrics['total_assignments'] }}</p>
                <p class="metric-sub">Tugas pada seluruh kelas Anda</p>
            </div>

            <div class="card card-accent-amber">
                <p class="metric-label label-amber">Menunggu Penilaian</p>
                <p class="metric-value">{{ $metrics['pending_grading_count'] }}</p>
                <p class="metric-sub">Submission belum diperiksa</p>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">Mata Kuliah yang Anda Ajar</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                @forelse ($myCourses as $course)
                    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span class="badge badge-primary">{{ $course->code }}</span>
                                <span style="font-size: 0.8rem; color: #64748b;">{{ $course->sks }} SKS</span>
                            </div>
                            <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                                <a href="{{ route('courses.show', $course) }}" style="color: #0f172a; text-decoration: none; font-weight: 700;">{{ $course->name }}</a>
                            </h4>
                            <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5; margin-bottom: 1rem;">
                                {{ Str::limit($course->description, 100) }}
                            </p>
                        </div>
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.825rem; color: #475569;">
                            <span>{{ $course->students_count }} Mahasiswa</span>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="{{ route('courses.materials.index', $course) }}" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">Materi ({{ $course->materials_count }})</a>
                                <a href="{{ route('courses.assignments.index', $course) }}" class="btn btn-primary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">Tugas ({{ $course->assignments_count }})</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card" style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 2rem;">
                        Anda belum ditugaskan untuk mengampu mata kuliah apapun.
                    </div>
                @endforelse
            </div>
        </div>

    @else
        {{-- ======================================================== --}}
        {{-- DASHBOARD MAHASISWA                                      --}}
        {{-- ======================================================== --}}
        <div class="page-header">
            <h1>Dashboard Mahasiswa</h1>
            <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong> (NIM: {{ auth()->user()->nim_nip ?? '-' }})</p>
        </div>

        <div class="metric-grid">
            <a href="{{ route('courses.index') }}" class="dashboard-card-link">
                <div class="card card-accent-blue">
                    <p class="metric-label label-blue">Mata Kuliah Diikuti</p>
                    <p class="metric-value">{{ $metrics['courses_count'] }}</p>
                    <p class="metric-sub">Kelas aktif semester ini</p>
                </div>
            </a>

            <div class="card card-accent-amber">
                <p class="metric-label label-amber">Tugas Mendatang</p>
                <p class="metric-value">{{ $metrics['upcoming_count'] }}</p>
                <p class="metric-sub">Tenggat waktu aktif</p>
            </div>

            <div class="card card-accent-green">
                <p class="metric-label label-green">Tugas Terkumpul</p>
                <p class="metric-value">{{ $metrics['submitted_count'] }}</p>
                <p class="metric-sub">{{ $metrics['graded_count'] }} Sudah Dinilai</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
            {{-- Mata Kuliah yang Diikuti --}}
            <div class="card">
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem;">Mata Kuliah Anda</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @forelse ($enrolledCourses as $course)
                        <div style="padding: 0.85rem; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span class="badge badge-primary" style="font-size: 0.75rem;">{{ $course->code }}</span>
                                <h4 style="margin: 0.35rem 0 0.2rem 0; font-size: 0.95rem;">
                                    <a href="{{ route('courses.show', $course) }}" style="color: #0f172a; text-decoration: none; font-weight: 600;">{{ $course->name }}</a>
                                </h4>
                                <p style="font-size: 0.8rem; color: #64748b;">Dosen: {{ $course->lecturer->name ?? '-' }}</p>
                            </div>
                            <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">Buka Kelas &rarr;</a>
                        </div>
                    @empty
                        <p style="color: #64748b; font-size: 0.9rem;">Anda belum terdaftar pada mata kuliah apapun.</p>
                    @endforelse
                </div>
            </div>

            {{-- Tugas Mendatang --}}
            <div class="card">
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem;">Tugas Mendatang</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @forelse ($upcomingAssignments as $assignment)
                        <div style="padding: 0.85rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <h4 style="margin: 0; font-size: 0.95rem;">
                                    <a href="{{ route('assignments.show', $assignment) }}" style="color: #0f172a; text-decoration: none; font-weight: 600;">{{ $assignment->title }}</a>
                                </h4>
                                <span class="badge badge-warning" style="font-size: 0.75rem;">Tenggat: {{ $assignment->due_at->format('d M Y') }}</span>
                            </div>
                            <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.35rem;">Mata Kuliah: {{ $assignment->course->name }}</p>
                        </div>
                    @empty
                        <p style="color: #64748b; font-size: 0.9rem;">Tidak ada tugas mendatang saat ini. Kerja bagus!</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

</x-layout>
