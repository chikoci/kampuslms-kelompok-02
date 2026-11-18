<x-layout :title="$assignment->title . ' - KampusLMS'"
    :backUrl="route('courses.show', $assignment->course_id)"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
        ['label' => $assignment->course->name, 'url' => route('courses.show', $assignment->course)],
        ['label' => $assignment->title]
    ]">

    <div class="card-modern" style="max-width: 900px; margin: 0 auto;">
        {{-- Header Tugas --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span class="badge-code-modern">{{ $assignment->course->code ?? 'TUGAS' }}</span>
                    <span class="badge-status-dot {{ $assignment->status }}">
                        <span class="dot"></span>
                        {{ ucfirst($assignment->status) }}
                    </span>
                </div>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin: 0 0 0.4rem 0;">
                    {{ $assignment->title }}
                </h1>
                <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <span>Batas Waktu: <strong>{{ $assignment->due_at?->translatedFormat('d M Y, H:i') }}</strong></span>
                    <span>&bull;</span>
                    <span>Nilai Maksimal: <strong>{{ $assignment->max_score }}</strong></span>
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                @can('update', $assignment)
                    <a href="{{ route('assignments.edit', $assignment) }}" class="btn-open-class" style="color: #d97706; border-color: #fde68a;">
                        Edit Tugas
                    </a>
                @endcan
                @can('delete', $assignment)
                    <form action="{{ route('assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Hapus tugas ini?');" style="display: inline; margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-open-class" style="color: #dc2626; border-color: #fecaca;">
                            Hapus
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Petunjuk Pengerjaan --}}
        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">Petunjuk Pengerjaan:</h3>
            <div style="color: #334155; line-height: 1.7; background: #f8fafc; padding: 1.25rem 1.5rem; border-radius: 16px; border: 1px solid #eef2f6;">
                {{ $assignment->instructions ?: 'Tidak ada petunjuk khusus untuk tugas ini.' }}
            </div>
        </div>

        {{-- Khusus Dosen/Admin: Link ke Rekap Seluruh Pengumpulan --}}
        @can('update', $assignment)
            <div style="margin-bottom: 2rem; padding: 1.25rem 1.5rem; background: #eff6ff; border-radius: 18px; border: 1px solid #bfdbfe; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h4 style="font-size: 1rem; font-weight: 700; color: #1e40af; margin-bottom: 0.25rem;">Kelola Pengumpulan Mahasiswa</h4>
                    <p style="font-size: 0.85rem; color: #3b82f6; margin: 0;">Total {{ $assignment->submissions()->count() }} mahasiswa telah mengumpulkan tugas ini.</p>
                </div>
                <a href="{{ route('assignments.submissions.index', $assignment) }}" class="filter-btn-primary" style="text-decoration: none;">
                    Lihat Semua Pengumpulan &rarr;
                </a>
            </div>
        @endcan

        {{-- Khusus Mahasiswa: Form / Status Pengumpulan Tugas --}}
        @php
            $mySubmission = $assignment->submissions()->where('user_id', auth()->id())->first();
        @endphp

        @if(auth()->user()->role === 'mahasiswa')
            <div style="padding: 1.75rem; background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    Status Pengumpulan Tugas Anda
                </h3>

                @if($mySubmission)
                    <div class="submission-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="padding: 1rem; background: #f8fafc; border-radius: 14px; border: 1px solid #eef2f6;">
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Waktu Pengumpulan</span>
                            <p style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-top: 0.25rem;">
                                {{ $mySubmission->submitted_at?->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>

                        <div style="padding: 1rem; background: #f8fafc; border-radius: 14px; border: 1px solid #eef2f6;">
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Status Keterlambatan</span>
                            <p style="margin-top: 0.25rem;">
                                @if($mySubmission->is_late)
                                    <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.75rem; font-weight: 700; border-radius: 6px; padding: 0.2rem 0.6rem;">Terlambat</span>
                                @else
                                    <span class="badge" style="background: #ecfdf5; color: #047857; font-size: 0.75rem; font-weight: 700; border-radius: 6px; padding: 0.2rem 0.6rem;">Tepat Waktu</span>
                                @endif
                            </p>
                        </div>

                        <div style="padding: 1rem; background: #f8fafc; border-radius: 14px; border: 1px solid #eef2f6;">
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Nilai Dosen</span>
                            <p style="font-size: 1.25rem; font-weight: 800; color: #2563eb; margin-top: 0.25rem;">
                                {{ $mySubmission->grade ? number_format($mySubmission->grade->score, 2) . ' / ' . $assignment->max_score : 'Belum Dinilai' }}
                            </p>
                        </div>
                    </div>

                    @if($mySubmission->grade && $mySubmission->grade->feedback)
                        <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: #f0fdf4; border-radius: 14px; border: 1px solid #bbf7d0;">
                            <strong style="color: #166534; font-size: 0.875rem;">Catatan Penilai (Feedback):</strong>
                            <p style="color: #15803d; font-size: 0.95rem; margin-top: 0.25rem; line-height: 1.6;">{{ $mySubmission->grade->feedback }}</p>
                        </div>
                    @endif

                    @if($mySubmission->note)
                        <div style="margin-bottom: 1.5rem;">
                            <strong style="color: #475569; font-size: 0.875rem;">Catatan Anda:</strong>
                            <p style="color: #334155; font-size: 0.95rem; margin-top: 0.25rem; background: #f8fafc; padding: 0.85rem 1rem; border-radius: 12px; border: 1px solid #eef2f6;">{{ $mySubmission->note }}</p>
                        </div>
                    @endif

                    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                        @if($mySubmission->file_path)
                            <a href="{{ route('submissions.download', $mySubmission) }}" class="btn-open-class" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Unduh: {{ $mySubmission->original_name ?? 'Berkas' }} ({{ $mySubmission->formatted_file_size }})
                            </a>
                        @endif

                        <a href="{{ route('submissions.show', $mySubmission) }}" class="btn-open-class">
                            Detail Lembar Pengumpulan &rarr;
                        </a>

                        @can('delete', $mySubmission)
                            <form action="{{ route('submissions.destroy', $mySubmission) }}" method="POST" onsubmit="return confirm('Hapus pengumpulan tugas Anda? Berkas fisik akan ikut terhapus dari server.');" style="display: inline; margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-open-class" style="color: #dc2626; border-color: #fecaca;">
                                    Hapus Pengumpulan
                                </button>
                            </form>
                        @endcan
                    </div>

                    {{-- Form Kumpulkan Ulang --}}
                    @can('update', $mySubmission)
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #e2e8f0;">
                            <details>
                                <summary style="cursor: pointer; font-size: 0.875rem; font-weight: 700; color: #2563eb;">
                                    &#8635; Ingin Mengumpulkan Ulang? Klik di sini untuk mengunggah berkas baru
                                </summary>
                                <form action="{{ route('assignments.submissions.store', $assignment) }}" method="POST" enctype="multipart/form-data" style="margin-top: 1.25rem;">
                                    @csrf
                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">
                                            Pilih Berkas Pengganti (PDF, DOC, DOCX - Maksimal 5 MB) *
                                        </label>
                                        <input type="file" name="file" accept=".pdf,.doc,.docx" required class="filter-search-input" style="padding-left: 1rem;">
                                        @error('file')
                                            <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div style="margin-bottom: 1.25rem;">
                                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">
                                            Catatan Pembaruan (Opsional)
                                        </label>
                                        <textarea name="note" rows="2" class="filter-search-input" style="padding-left: 1rem; border-radius: 12px;" placeholder="Catatan revisi atau pembaruan tugas...">{{ old('note', $mySubmission->note) }}</textarea>
                                    </div>

                                    <button type="submit" class="filter-btn-primary">
                                        Perbarui & Timpa Pengumpulan
                                    </button>
                                </form>
                            </details>
                        </div>
                    @endcan

                @else
                    {{-- Form Pertama Kali Mengumpulkan --}}
                    <form action="{{ route('assignments.submissions.store', $assignment) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">
                                Unggah Berkas Jawaban Tugas (PDF, DOC, DOCX - Maksimal 5 MB) *
                            </label>
                            <input type="file" name="file" accept=".pdf,.doc,.docx" required class="filter-search-input" style="padding-left: 1rem;">
                            <p style="color: #64748b; font-size: 0.75rem; margin-top: 0.35rem;">Berkas disimpan di server privat aman untuk menjaga kerahasiaan lembar jawaban.</p>
                            @error('file')
                                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">
                                Catatan Tambahan (Opsional)
                            </label>
                            <textarea name="note" rows="3" class="filter-search-input" style="padding-left: 1rem; border-radius: 12px;" placeholder="Tuliskan catatan tambahan untuk dosen pengampu...">{{ old('note') }}</textarea>
                            @error('note')
                                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="filter-btn-primary" style="padding: 0.75rem 1.75rem; font-size: 0.95rem;">
                            Kumpulkan Tugas Sekarang
                        </button>
                    </form>
                @endif
            </div>
        @endif

    </div>

</x-layout>
