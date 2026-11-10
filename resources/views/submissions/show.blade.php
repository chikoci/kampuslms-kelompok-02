<x-layout title="Detail Pengumpulan Tugas - KampusLMS">

    {{-- Navigasi Kembali --}}
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('assignments.show', $submission->assignment_id) }}" class="btn-open-class" style="border-radius: 9999px;">
            &larr; Kembali ke Detail Tugas
        </a>
    </div>

    <div class="card-modern" style="max-width: 850px; margin: 0 auto;">
        {{-- Header Pengumpulan --}}
        <div style="margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                <span class="badge-code-modern">{{ $submission->assignment->course->code ?? 'SUBMISSION' }}</span>
                <span class="badge" style="background: {{ $submission->is_late ? '#fee2e2' : '#ecfdf5' }}; color: {{ $submission->is_late ? '#b91c1c' : '#047857' }}; font-weight: 700; font-size: 0.75rem; border-radius: 6px; padding: 0.2rem 0.6rem;">
                    {{ $submission->is_late ? 'Terlambat' : 'Tepat Waktu' }}
                </span>
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem 0;">
                Pengumpulan: {{ $submission->assignment->title ?? 'Tugas Perkuliahan' }}
            </h1>
            <p style="color: #64748b; font-size: 0.85rem; margin: 0;">
                Mata Kuliah: <strong>{{ $submission->assignment->course->name ?? '-' }}</strong>
            </p>
        </div>

        {{-- Grid Info Mahasiswa & Waktu --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div style="padding: 1.25rem; background: #f8fafc; border-radius: 16px; border: 1px solid #eef2f6;">
                <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Nama Mahasiswa</span>
                <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.25rem;">
                    {{ $submission->student->name ?? 'Mahasiswa' }}
                </div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.15rem;">
                    NIM: {{ $submission->student->nim_nip ?? '-' }}
                </div>
            </div>

            <div style="padding: 1.25rem; background: #f8fafc; border-radius: 16px; border: 1px solid #eef2f6;">
                <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Waktu Pengumpulan</span>
                <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.25rem;">
                    {{ $submission->submitted_at ? $submission->submitted_at->translatedFormat('d F Y, H:i') : '-' }}
                </div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.15rem;">
                    Tenggat: {{ $submission->assignment->due_at?->translatedFormat('d M Y, H:i') }}
                </div>
            </div>
        </div>

        {{-- Catatan Mahasiswa --}}
        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">Catatan Mahasiswa:</h3>
            <p style="color: #334155; line-height: 1.6; background: #f8fafc; padding: 1rem 1.25rem; border-radius: 14px; border: 1px solid #eef2f6; margin: 0;">
                {{ $submission->note ?: 'Tidak ada catatan yang dicantumkan.' }}
            </p>
        </div>

        {{-- Berkas Lampiran --}}
        @if($submission->file_path)
            <div style="margin-bottom: 2rem;">
                <a href="{{ route('submissions.download', $submission) }}" class="filter-btn-primary" style="text-decoration: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Unduh Berkas: {{ $submission->original_name ?: 'Berkas Tugas' }} ({{ $submission->formatted_file_size }})
                </a>
            </div>
        @endif

        {{-- Bagian Penilaian Dosen (Grade) --}}
        <div style="border-top: 1px solid #e2e8f0; padding-top: 1.75rem; margin-top: 1.5rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="7"/>
                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                </svg>
                Penilaian Dosen
            </h3>

            @if($submission->grade)
                <div style="padding: 1.5rem; background: #f0fdf4; border-radius: 16px; border: 1px solid #bbf7d0; margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                        <span style="font-size: 0.95rem; font-weight: 700; color: #166534;">Nilai Diberikan:</span>
                        <span style="font-size: 1.75rem; font-weight: 800; color: #15803d;">
                            {{ number_format($submission->grade->score, 2) }} <span style="font-size: 1rem; color: #166534; font-weight: 600;">/ {{ $submission->assignment->max_score ?? 100 }}</span>
                        </span>
                    </div>

                    @if($submission->grade->feedback)
                        <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #86efac;">
                            <strong style="color: #166534; font-size: 0.85rem;">Catatan Umpan Balik (Feedback):</strong>
                            <p style="color: #15803d; font-size: 0.95rem; margin-top: 0.25rem; line-height: 1.6;">{{ $submission->grade->feedback }}</p>
                        </div>
                    @endif

                    <p style="font-size: 0.75rem; color: #16a34a; margin-top: 0.75rem;">
                        Dinilai oleh: {{ $submission->grade->grader?->name ?? 'Dosen Pengampu' }} pada {{ $submission->grade->graded_at?->translatedFormat('d M Y, H:i') }}
                    </p>
                </div>
            @else
                <div style="padding: 1.5rem; background: #fffbeb; border-radius: 16px; border: 1px solid #fde68a; margin-bottom: 1.5rem;">
                    <p style="color: #b45309; font-weight: 600; margin: 0; font-size: 0.9rem;">
                        Pengumpulan tugas ini belum diberikan penilaian oleh dosen pengampu.
                    </p>
                </div>
            @endif

            {{-- Form Penilaian Khusus Dosen Pengampu --}}
            @can('create', [\App\Models\Grade::class, $submission])
                <div style="padding: 1.5rem; background: #f8fafc; border-radius: 18px; border: 1px solid #e2e8f0;">
                    <h4 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem;">
                        {{ $submission->grade ? 'Perbarui Nilai & Feedback' : 'Beri Nilai Mahasiswa' }}
                    </h4>
                    <form action="{{ route('submissions.grades.store', $submission) }}" method="POST">
                        @csrf
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">
                                Nilai (0 - {{ $submission->assignment->max_score ?? 100 }})
                            </label>
                            <input type="number" step="0.01" name="score" min="0" max="{{ $submission->assignment->max_score ?? 100 }}" value="{{ old('score', $submission->grade?->score) }}" required class="filter-search-input" style="width: 160px; padding-left: 1rem;">
                            @error('score')
                                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">
                                Catatan / Feedback untuk Mahasiswa
                            </label>
                            <textarea name="feedback" rows="3" class="filter-search-input" style="padding-left: 1rem; border-radius: 12px;" placeholder="Tuliskan umpan balik terkait hasil pengerjaan...">{{ old('feedback', $submission->grade?->feedback) }}</textarea>
                            @error('feedback')
                                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <button type="submit" class="filter-btn-primary">
                                Simpan Penilaian
                            </button>
                            @if($submission->grade)
                                <a href="{{ route('submissions.grades.destroy', $submission) }}" 
                                   onclick="event.preventDefault(); if(confirm('Hapus penilaian ini?')) document.getElementById('delete-grade-form').submit();"
                                   class="btn-open-class" style="color: #dc2626; border-color: #fecaca;">
                                    Hapus Nilai
                                </a>
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
    </div>

</x-layout>
