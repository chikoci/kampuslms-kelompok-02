@php
    $isStaff = auth()->user()?->role === 'dosen' || auth()->user()?->role === 'admin';
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
        ['label' => $submission->assignment->course->name, 'url' => route('courses.show', $submission->assignment->course)],
        ['label' => $submission->assignment->title, 'url' => route('assignments.show', $submission->assignment)],
    ];
    if ($isStaff) {
        $breadcrumbs[] = ['label' => 'Penilaian Tugas', 'url' => route('assignments.submissions.index', $submission->assignment)];
        $breadcrumbs[] = ['label' => $submission->student->name];
    } else {
        $breadcrumbs[] = ['label' => 'Pengumpulan Saya'];
    }
@endphp

<x-layout title="Detail Pengumpulan: {{ $submission->student->name }} - KampusLMS" :breadcrumbs="$breadcrumbs">

    <div class="submission-detail-container">
        <div class="card-modern">
            
            {{-- 1. Navigasi Cepat Mahasiswa (Previous / Next) untuk Dosen & Admin --}}
            @if($isStaff && isset($totalSubmissions) && $totalSubmissions > 0)
                <div class="submission-nav-bar">
                    <div class="submission-nav-info">
                        <span class="submission-nav-badge">Penilaian Pengumpulan</span>
                        <span class="submission-nav-counter">
                            Mahasiswa <strong>{{ $currentIndex }}</strong> dari <strong>{{ $totalSubmissions }}</strong>
                        </span>
                    </div>

                    <div class="submission-nav-buttons">
                        @if($prevSubmission)
                            <a href="{{ route('submissions.show', $prevSubmission) }}" class="btn btn-secondary btn-sm nav-track-link" title="Nilai Mahasiswa Sebelumnya: {{ $prevSubmission->student->name }}">
                                &larr; Mahasiswa Sebelumnya
                            </a>
                        @else
                            <button type="button" class="btn btn-secondary btn-sm" disabled style="opacity: 0.45; cursor: not-allowed;">
                                &larr; Mahasiswa Sebelumnya
                            </button>
                        @endif

                        @if($nextSubmission)
                            <a href="{{ route('submissions.show', $nextSubmission) }}" class="btn btn-primary btn-sm nav-track-link" title="Nilai Mahasiswa Berikutnya: {{ $nextSubmission->student->name }}">
                                Mahasiswa Berikutnya &rarr;
                            </a>
                        @else
                            <button type="button" class="btn btn-secondary btn-sm" disabled style="opacity: 0.45; cursor: not-allowed;">
                                Mahasiswa Berikutnya &rarr;
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- 2. Header Informasi Tugas & Status Pengumpulan --}}
            <div style="margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="badge-code-modern">{{ $submission->assignment->course->code ?? 'KULIAH' }}</span>
                        <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">{{ $submission->assignment->course->name }}</span>
                    </div>
                    <span class="badge" style="background: {{ $submission->is_late ? '#fee2e2' : '#ecfdf5' }}; color: {{ $submission->is_late ? '#b91c1c' : '#047857' }}; font-weight: 700; font-size: 0.75rem; border-radius: 8px; padding: 0.25rem 0.65rem; border: 1px solid {{ $submission->is_late ? '#fecaca' : '#a7f3d0' }};">
                        {{ $submission->is_late ? '⚠️ Terlambat Dikumpulkan' : '✓ Dikumpulkan Tepat Waktu' }}
                    </span>
                </div>

                <h1 style="font-size: 1.65rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem 0; letter-spacing: -0.02em;">
                    {{ $submission->assignment->title }}
                </h1>
                <p style="color: #64748b; font-size: 0.875rem; margin: 0;">
                    Tenggat Pengumpulan: <strong style="color: #1e293b;">{{ $submission->assignment->due_at ? $submission->assignment->due_at->translatedFormat('d F Y, H:i') : 'Tidak ditentukan' }} WIB</strong>
                </p>
            </div>

            {{-- 3. Grid Rapi: Informasi Mahasiswa & Waktu Pengumpulan --}}
            <div class="submission-info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                {{-- Data Mahasiswa --}}
                <div style="padding: 1.25rem; background: #f8fafc; border-radius: 16px; border: 1px solid #eef2f6;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Identitas Mahasiswa</div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-top: 0.35rem;">
                        {{ $submission->student->name ?? 'Mahasiswa' }}
                    </div>
                    <div style="display: flex; gap: 0.75rem; font-size: 0.825rem; color: #475569; margin-top: 0.25rem;">
                        <span>NIM: <strong>{{ $submission->student->nim_nip ?? '-' }}</strong></span>
                        <span>•</span>
                        <span>Surel: {{ $submission->student->email }}</span>
                    </div>
                </div>

                {{-- Waktu Pengumpulan --}}
                <div style="padding: 1.25rem; background: #f8fafc; border-radius: 16px; border: 1px solid #eef2f6;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Waktu Pengumpulan</div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-top: 0.35rem;">
                        {{ $submission->submitted_at ? $submission->submitted_at->translatedFormat('d F Y, H:i') : '-' }} WIB
                    </div>
                    <div style="font-size: 0.825rem; color: #64748b; margin-top: 0.25rem;">
                        {{ $submission->submitted_at ? $submission->submitted_at->diffForHumans() : '-' }}
                    </div>
                </div>
            </div>

            {{-- 4. Catatan Mahasiswa --}}
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 0.925rem; font-weight: 700; color: #0f172a; margin-bottom: 0.45rem;">Catatan Mahasiswa:</h3>
                <div style="color: #334155; line-height: 1.6; background: #f8fafc; padding: 1rem 1.25rem; border-radius: 14px; border: 1px solid #eef2f6; font-size: 0.9rem;">
                    {{ $submission->note ?: 'Tidak ada catatan tambahan yang dicantumkan oleh mahasiswa.' }}
                </div>
            </div>

            {{-- 5. Berkas Lampiran Tugas --}}
            @if($submission->file_path)
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 0.925rem; font-weight: 700; color: #0f172a; margin-bottom: 0.45rem;">Berkas Tugas Mahasiswa:</h3>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; background: #ffffff; border: 1.5px solid #dbeafe; border-radius: 14px; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 0;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.925rem; word-break: break-all;">
                                    {{ $submission->original_name ?: 'Berkas Pengumpulan Tugas' }}
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b;">
                                    Ukuran berkas: {{ $submission->formatted_file_size }}
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('submissions.download', $submission) }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1.15rem; font-weight: 600;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Unduh Berkas
                        </a>
                    </div>
                </div>
            @endif

            {{-- 6. Bagian Penilaian Dosen (Pemisahan Visual Jelas) --}}
            <div style="border-top: 2px dashed #e2e8f0; padding-top: 1.75rem; margin-top: 1.5rem;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="7"/>
                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                    </svg>
                    Evaluasi &amp; Penilaian
                </h3>

                {{-- Status Nilai Tersimpan --}}
                @if($submission->grade)
                    <div style="padding: 1.5rem; background: #f0fdf4; border-radius: 16px; border: 1.5px solid #86efac; margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                            <span style="font-size: 0.95rem; font-weight: 700; color: #166534;">Nilai Diberikan:</span>
                            <span style="font-size: 2rem; font-weight: 800; color: #15803d; letter-spacing: -0.02em;">
                                {{ number_format($submission->grade->score, 2) }} <span style="font-size: 1.05rem; color: #166534; font-weight: 600;">/ {{ $submission->assignment->max_score ?? 100 }}</span>
                            </span>
                        </div>

                        @if($submission->grade->feedback)
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #86efac;">
                                <strong style="color: #166534; font-size: 0.85rem;">Catatan Umpan Balik (Feedback):</strong>
                                <p style="color: #15803d; font-size: 0.925rem; margin-top: 0.35rem; line-height: 1.6;">{{ $submission->grade->feedback }}</p>
                            </div>
                        @endif

                        <p style="font-size: 0.78rem; color: #16a34a; margin-top: 0.75rem; margin-bottom: 0;">
                            Dinilai oleh: <strong>{{ $submission->grade->grader?->name ?? 'Dosen Pengampu' }}</strong> pada {{ $submission->grade->graded_at?->translatedFormat('d M Y, H:i') }} WIB
                        </p>
                    </div>
                @else
                    <div style="padding: 1.25rem 1.5rem; background: #fffbeb; border-radius: 16px; border: 1.5px solid #fde68a; margin-bottom: 1.5rem;">
                        <p style="color: #b45309; font-weight: 600; margin: 0; font-size: 0.9rem;">
                            ⏳ Pengumpulan tugas ini belum diberikan penilaian.
                        </p>
                    </div>
                @endif

                {{-- Form Penilaian Khusus Dosen Pengampu / Admin --}}
                @can('create', [\App\Models\Grade::class, $submission])
                    <div style="padding: 1.5rem; background: #f8fafc; border-radius: 18px; border: 1.5px solid #e2e8f0;">
                        <h4 style="font-size: 1rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem;">
                            {{ $submission->grade ? 'Perbarui Nilai & Feedback Mahasiswa' : 'Beri Nilai Mahasiswa' }}
                        </h4>

                        <form id="gradeForm" action="{{ route('submissions.grades.store', $submission) }}" method="POST">
                            @csrf
                            <div style="margin-bottom: 1.25rem;">
                                <label for="scoreInput" style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem;">
                                    Nilai Akhir (Skala 0 - {{ $submission->assignment->max_score ?? 100 }})
                                </label>
                                <input type="number" 
                                       step="0.01" 
                                       id="scoreInput"
                                       name="score" 
                                       min="0" 
                                       max="{{ $submission->assignment->max_score ?? 100 }}" 
                                       value="{{ old('score', $submission->grade?->score) }}" 
                                       required 
                                       class="filter-search-input" 
                                       style="width: 180px; padding-left: 1rem; height: 44px; font-weight: 700; font-size: 1.05rem;"
                                       placeholder="Contoh: 85.5">
                                @error('score')
                                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.35rem; font-weight: 600;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <label for="feedbackInput" style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem;">
                                    Catatan / Umpan Balik (Feedback) untuk Mahasiswa
                                </label>
                                <textarea id="feedbackInput" 
                                          name="feedback" 
                                          rows="4" 
                                          class="filter-search-input" 
                                          style="padding: 0.85rem 1rem; border-radius: 12px; width: 100%; height: auto; min-height: 100px; line-height: 1.5;" 
                                          placeholder="Tuliskan evaluasi, saran perbaikan, atau apresiasi terkait tugas yang dikumpulkan...">{{ old('feedback', $submission->grade?->feedback) }}</textarea>
                                @error('feedback')
                                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.35rem; font-weight: 600;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.45rem; font-weight: 700;">
                                    {{ $submission->grade ? 'Perbarui Nilai' : 'Simpan Penilaian' }}
                                </button>
                                
                                @if($submission->grade)
                                    <button type="button" 
                                            onclick="if(confirm('Apakah Anda yakin ingin menghapus penilaian ini?')) document.getElementById('delete-grade-form').submit();"
                                            class="btn btn-secondary" 
                                            style="color: #dc2626; border-color: #fecaca; background: #fff; padding: 0.65rem 1.25rem;">
                                        Hapus Nilai
                                    </button>
                                @endif
                            </div>
                        </form>

                        @if($submission->grade)
                            <form id="delete-grade-form" action="{{ route('submissions.grades.destroy', $submission) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    </div>
                @endcan
            </div>

            {{-- 7. Area Bawah Card: Tombol Kembali di Pojok Kanan Bawah di Dalam Card --}}
            <div class="submission-card-bottom-bar">
                <div class="submission-bottom-back">
                    <a href="{{ route('assignments.show', $submission->assignment_id) }}" class="btn btn-secondary nav-track-link" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.65rem 1.25rem; font-weight: 600;">
                        &larr; Kembali ke Detail Tugas
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- Script Perlindungan Data Form & Peringatan Perubahan yang Belum Disimpan --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('gradeForm');
            let isFormDirty = false;

            if (form) {
                const initialScore = document.getElementById('scoreInput') ? document.getElementById('scoreInput').value : '';
                const initialFeedback = document.getElementById('feedbackInput') ? document.getElementById('feedbackInput').value : '';

                form.addEventListener('input', function () {
                    const currentScore = document.getElementById('scoreInput') ? document.getElementById('scoreInput').value : '';
                    const currentFeedback = document.getElementById('feedbackInput') ? document.getElementById('feedbackInput').value : '';
                    isFormDirty = (currentScore !== initialScore || currentFeedback !== initialFeedback);
                });

                form.addEventListener('submit', function () {
                    isFormDirty = false;
                });
            }

            // Peringatan saat dosen mencoba berpindah ke mahasiswa lain atau kembali saat form ada perubahan
            const navLinks = document.querySelectorAll('.nav-track-link');
            navLinks.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    if (isFormDirty) {
                        const confirmed = confirm('Ada perubahan nilai atau feedback yang belum disimpan. Yakin ingin berpindah halaman tanpa menyimpan?');
                        if (!confirmed) {
                            e.preventDefault();
                        }
                    }
                });
            });

            // Perlindungan refresh/tutup tab
            window.addEventListener('beforeunload', function (e) {
                if (isFormDirty) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        });
    </script>

</x-layout>
