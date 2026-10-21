<x-layout title="Edit Materi: {{ $material->title }}">
    <div style="max-width: 700px; margin: 2rem auto; padding: 2rem; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
            <a href="{{ route('courses.show', $material->course_id) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">&larr; Kembali ke {{ $material->course->name }}</a>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-top: 0.5rem;">Edit Materi Perkuliahan</h1>
            <p style="color: #6b7280; font-size: 0.875rem;">Mata Kuliah: <strong>{{ $material->course->name }}</strong></p>
        </div>

        <form action="{{ route('materials.update', $material) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Judul Materi *</label>
                <input type="text" name="title" value="{{ old('title', $material->title) }}" required class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                @error('title')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            @if($material->type === 'file' || $material->file_path)
                <div style="margin-bottom: 1.25rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <div>
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Berkas Saat Ini:</span>
                            <p style="font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-top: 0.125rem;">
                                &#128190; {{ $material->original_name ?? 'Berkas Materi' }}
                                <span style="font-size: 0.75rem; color: #64748b; font-weight: normal;">({{ $material->formatted_file_size }})</span>
                            </p>
                        </div>
                        <a href="{{ route('materials.download', $material) }}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">
                            Unduh Berkas
                        </a>
                    </div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Ganti Berkas (Opsional - PDF, PPTX, DOCX Maks 20 MB)</label>
                    <input type="file" name="file" accept=".pdf,.pptx,.docx" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    <p style="color: #64748b; font-size: 0.75rem; margin-top: 0.25rem;">Biarkan kosong jika tidak ingin mengubah berkas yang sudah ada.</p>
                    @error('file')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @if($material->type === 'link' || $material->external_url)
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Tautan Eksternal (URL) *</label>
                    <input type="url" name="external_url" value="{{ old('external_url', $material->external_url) }}" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    @error('external_url')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Deskripsi / Keterangan</label>
                <textarea name="description" rows="4" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">{{ old('description', $material->description) }}</textarea>
                @error('description')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <a href="{{ route('courses.show', $material->course_id) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-layout>
