<x-layout title="Detail Pengumpulan Tugas">
    <div style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <a href="{{ route('assignments.submissions.index', $submission->assignment_id) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">
                    &larr; Kembali ke Daftar Pengumpulan
                </a>
                <span class="badge {{ $submission->is_late ? 'badge-overdue' : 'badge-upcoming' }}">
                    {{ $submission->is_late ? 'Terlambat' : 'Tepat Waktu' }}
                </span>
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 700; color: #111827;">Pengumpulan Tugas #{{ $submission->id }}</h1>
            <p style="color: #6b7280; font-size: 0.875rem;">
                Tugas: <strong>{{ $submission->assignment->title ?? 'Tugas Perkuliahan' }}</strong> |
                Mata Kuliah: <strong>{{ $submission->assignment->course->name ?? '-' }}</strong>
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Nama Mahasiswa</span>
                <p style="font-size: 1rem; font-weight: 600; color: #111827; margin-top: 0.25rem;">{{ $submission->student->name ?? 'Mahasiswa' }}</p>
                <p style="font-size: 0.8rem; color: #6b7280;">NIM: {{ $submission->student->nim_nip ?? '-' }}</p>
            </div>
            <div style="padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Waktu Pengumpulan</span>
                <p style="font-size: 1rem; font-weight: 600; color: #111827; margin-top: 0.25rem;">
                    {{ $submission->submitted_at ? $submission->submitted_at->translatedFormat('d F Y, H:i') : '-' }}
                </p>
                <p style="font-size: 0.8rem; color: #6b7280;">Batas: {{ $submission->assignment->due_at?->format('d M Y, H:i') }}</p>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Catatan Mahasiswa:</h3>
            <p style="color: #4b5563; line-height: 1.5; background: #fdfdfd; padding: 1rem; border-radius: 8px; border: 1px solid #f3f4f6;">
                {{ $submission->note ?: 'Tidak ada catatan.' }}
            </p>
        </div>

        @if($submission->file_path)
            <div style="margin-bottom: 2rem;">
                <a href="{{ route('submissions.download', $submission) }}" 
                   style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; font-size: 0.875rem; font-weight: 500; border-radius: 8px; background-color: #2563eb; color: #ffffff; text-decoration: none;">
                    &#128190; Unduh Berkas: {{ $submission->original_name ?: 'Berkas Tugas' }} ({{ $submission->formatted_file_size }})
                </a>
            </div>
        @endif

        {{-- Bagian Penilaian (Grade) --}}
        <div style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-top: 1.5rem;">
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin-bottom: 1rem;">Penilaian Dosen</h3>

            @if($submission->grade)
                <div style="padding: 1.25rem; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #166534;">Nilai Diberikan:</span>
                        <span style="font-size: 1.5rem; font-weight: 800; color: #15803d;">
                            {{ $submission->grade->score }} / {{ $submission->assignment->max_score ?? 100 }}
                        </span>
                    </div>
                    @if($submission->grade->feedback)
                        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #86efac;">
                            <strong style="color: #166534; font-size: 0.875rem;">Umpan Balik (Feedback):</strong>
                            <p style="color: #15803d; font-size: 0.9rem; margin-top: 0.25rem;">{{ $submission->grade->feedback }}</p>
                        </div>
                    @endif
                    <p style="font-size: 0.75rem; color: #16a34a; margin-top: 0.5rem;">
                        Dinilai oleh: {{ $submission->grade->grader?->name ?? 'Dosen Pengampu' }} pada {{ $submission->grade->graded_at?->format('d M Y, H:i') }}
                    </p>
                </div>
            @else
                <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem;">Pengumpulan ini belum diberikan nilai.</p>
            @endif

            {{-- Form Penilaian Khusus Dosen Pengampu --}}
            @can('create', [\App\Models\Grade::class, $submission])
                <div style="padding: 1.5rem; background: #f8fafc; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem;">
                        {{ $submission->grade ? 'Perbarui Nilai & Feedback' : 'Beri Nilai Mahasiswa' }}
                    </h4>
                    <form action="{{ route('submissions.grades.store', $submission) }}" method="POST">
                        @csrf
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #334155; margin-bottom: 0.25rem;">
                                Nilai (0 - {{ $submission->assignment->max_score ?? 100 }})
                            </label>
                            <input type="number" step="0.01" name="score" min="0" max="{{ $submission->assignment->max_score ?? 100 }}" value="{{ old('score', $submission->grade?->score) }}" required class="form-control" style="width: 150px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            @error('score')
                                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #334155; margin-bottom: 0.25rem;">
                                Catatan / Feedback untuk Mahasiswa
                            </label>
                            <textarea name="feedback" rows="3" class="form-control" placeholder="Tuliskan umpan balik terkait hasil pengerjaan..." style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;">{{ old('feedback', $submission->grade?->feedback) }}</textarea>
                            @error('feedback')
                                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.25rem; font-weight: 600;">
                                {{ $submission->grade ? 'Perbarui Nilai' : 'Simpan Nilai' }}
                            </button>
                    </form>
                    @if($submission->grade)
                        <form action="{{ route('submissions.grades.destroy', $submission) }}" method="POST" onsubmit="return confirm('Hapus nilai untuk pengumpulan tugas mahasiswa ini?');" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1.25rem; font-weight: 600;">
                                Hapus Nilai
                            </button>
                        </form>
                    @endif
                        </div>
                </div>

                {{-- Navigasi Looping Penilaian Berurutan (Dosen/Admin) --}}
                @if(isset($prevSubmission) || isset($nextSubmission))
                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed #cbd5e1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                        <div>
                            @if($prevSubmission)
                                <a href="{{ route('submissions.show', $prevSubmission) }}" class="btn btn-secondary btn-sm">
                                    &larr; Nilai Sebelumnya: {{ $prevSubmission->student->name }}
                                </a>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('assignments.submissions.index', $submission->assignment) }}" style="font-size: 0.875rem; color: #2563eb; text-decoration: none; font-weight: 500;">
                                &uarr; Kembali ke Daftar Pengumpulan
                            </a>
                        </div>
                        <div>
                            @if($nextSubmission)
                                <a href="{{ route('submissions.show', $nextSubmission) }}" class="btn btn-secondary btn-sm">
                                    Nilai Berikutnya: {{ $nextSubmission->student->name }} &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</x-layout>
