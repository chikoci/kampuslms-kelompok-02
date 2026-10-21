<x-layout :title="$assignment->title">
    <div style="max-width: 900px; margin: 2rem auto; padding: 2rem; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
            <div>
                <a href="{{ route('courses.show', $assignment->course_id) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">&larr; Kembali ke {{ $assignment->course->name ?? 'Mata Kuliah' }}</a>
                <h1 style="font-size: 1.75rem; font-weight: 700; color: #111827; margin-top: 0.5rem;">{{ $assignment->title }}</h1>
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem;">
                    Batas Waktu: <strong>{{ $assignment->due_at?->format('d M Y, H:i') }}</strong> | 
                    Nilai Maksimal: <strong>{{ $assignment->max_score }}</strong> |
                    Status: <span class="badge badge-{{ $assignment->status }}">{{ ucfirst($assignment->status) }}</span>
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                @can('update', $assignment)
                    <a href="{{ route('assignments.edit', $assignment) }}" class="btn btn-edit btn-sm">Edit Tugas</a>
                @endcan
                @can('delete', $assignment)
                    <form action="{{ route('assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Hapus tugas ini?');" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                @endcan
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Petunjuk Pengerjaan:</h3>
            <div style="color: #4b5563; line-height: 1.6; background: #f9fafb; padding: 1rem; border-radius: 8px; border: 1px solid #f3f4f6;">
                {{ $assignment->instructions ?: 'Tidak ada petunjuk khusus untuk tugas ini.' }}
            </div>
        </div>

        {{-- Khusus Dosen/Admin: Link ke Rekap Seluruh Pengumpulan --}}
        @can('update', $assignment)
            <div style="margin-bottom: 2rem; padding: 1.25rem; background: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="font-size: 1rem; font-weight: 600; color: #1e40af; margin-bottom: 0.25rem;">Kelola Pengumpulan Mahasiswa</h4>
                    <p style="font-size: 0.875rem; color: #3b82f6;">Total {{ $assignment->submissions()->count() }} mahasiswa telah mengumpulkan tugas ini.</p>
                </div>
                <a href="{{ route('assignments.submissions.index', $assignment) }}" class="btn btn-primary btn-sm">
                    Lihat Semua Pengumpulan &rarr;
                </a>
            </div>
        @endcan

        {{-- Khusus Mahasiswa: Form / Status Pengumpulan Tugas --}}
        @php
            $mySubmission = $assignment->submissions()->where('user_id', auth()->id())->first();
        @endphp

        @if(auth()->user()->role === 'mahasiswa')
            <div style="padding: 1.5rem; background: #fdfdfd; border-radius: 8px; border: 1px solid #e5e7eb;">
                <h4 style="font-size: 1.125rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem;">Status Pengumpulan Tugas Anda</h4>

                @if($mySubmission)
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="padding: 1rem; background: #f9fafb; border-radius: 8px;">
                            <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Waktu Pengumpulan</span>
                            <p style="font-size: 0.95rem; font-weight: 600; color: #111827; margin-top: 0.25rem;">
                                {{ $mySubmission->submitted_at?->format('d M Y, H:i') }}
                            </p>
                        </div>
                        <div style="padding: 1rem; background: #f9fafb; border-radius: 8px;">
                            <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Status Keterlambatan</span>
                            <p style="margin-top: 0.25rem;">
                                @if($mySubmission->is_late)
                                    <span class="badge badge-overdue">Terlambat</span>
                                @else
                                    <span class="badge badge-upcoming">Tepat Waktu</span>
                                @endif
                            </p>
                        </div>
                        <div style="padding: 1rem; background: #f9fafb; border-radius: 8px;">
                            <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Nilai Dosen</span>
                            <p style="font-size: 1.1rem; font-weight: 700; color: #2563eb; margin-top: 0.25rem;">
                                {{ $mySubmission->grade ? $mySubmission->grade->score . ' / ' . $assignment->max_score : 'Belum Dinilai' }}
                            </p>
                        </div>
                    </div>

                    @if($mySubmission->grade && $mySubmission->grade->feedback)
                        <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                            <strong style="color: #166534; font-size: 0.875rem;">Catatan Penilai (Feedback):</strong>
                            <p style="color: #15803d; font-size: 0.9rem; margin-top: 0.25rem;">{{ $mySubmission->grade->feedback }}</p>
                        </div>
                    @endif

                    @if($mySubmission->note)
                        <div style="margin-bottom: 1.5rem;">
                            <strong style="color: #4b5563; font-size: 0.875rem;">Catatan Anda:</strong>
                            <p style="color: #374151; font-size: 0.9rem; margin-top: 0.25rem;">{{ $mySubmission->note }}</p>
                        </div>
                    @endif

                    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                        @if($mySubmission->file_path)
                            <a href="{{ route('submissions.download', $mySubmission) }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 4px;">
                                &#128190; Unduh Berkas: {{ $mySubmission->original_name ?? 'Berkas' }}
                                <span style="font-size: 0.75rem; opacity: 0.85;">({{ $mySubmission->formatted_file_size }})</span>
                            </a>
                        @endif
                        <a href="{{ route('submissions.show', $mySubmission) }}" class="btn btn-secondary btn-sm">
                            Lihat Detail Pengumpulan &rarr;
                        </a>
                        @can('delete', $mySubmission)
                            <form action="{{ route('submissions.destroy', $mySubmission) }}" method="POST" onsubmit="return confirm('Hapus pengumpulan tugas Anda? Berkas fisik akan ikut terhapus dari disk server.');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus Pengumpulan</button>
                            </form>
                        @endcan
                    </div>

                    {{-- Form Kumpulkan Ulang (Re-submission via updateOrCreate) --}}
                    @can('update', $mySubmission)
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #d1d5db;">
                            <details>
                                <summary style="cursor: pointer; font-size: 0.875rem; font-weight: 600; color: #2563eb;">
                                    &#8635; Ingin Mengumpulkan Ulang? Klik di sini untuk mengunggah berkas baru (akan menimpa berkas lama)
                                </summary>
                                <form action="{{ route('assignments.submissions.store', $assignment) }}" method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                                    @csrf
                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Pilih Berkas Pengganti (PDF, DOC, DOCX - Maksimal 5 MB) *</label>
                                        <input type="file" name="file" accept=".pdf,.doc,.docx" required class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                                        @error('file')
                                            <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Catatan Pembaruan (Opsional)</label>
                                        <textarea name="note" rows="2" class="form-control" placeholder="Catatan revisi atau pembaruan tugas..." style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">{{ old('note', $mySubmission->note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Perbarui dan Timpa Pengumpulan
                                    </button>
                                </form>
                            </details>
                        </div>
                    @endcan
                @else
                    <form action="{{ route('assignments.submissions.store', $assignment) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Unggah Berkas Tugas (PDF, DOC, DOCX - Maksimal 5 MB) *</label>
                            <input type="file" name="file" accept=".pdf,.doc,.docx" required class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                            <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem;">Berkas disimpan di disk privat `storage/app/private` untuk menjaga kerahasiaan lembar jawaban.</p>
                            @error('file')
                                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Catatan Tambahan (Opsional)</label>
                            <textarea name="note" rows="3" class="form-control" placeholder="Tuliskan catatan untuk dosen pengampu jika ada..." style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">{{ old('note') }}</textarea>
                            @error('note')
                                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary" style="padding: 0.625rem 1.5rem; font-weight: 600;">
                            Kumpulkan Tugas Sekarang
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</x-layout>
