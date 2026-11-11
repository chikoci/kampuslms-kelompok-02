<x-layout title="Tambah Materi: {{ $course->name }} - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
    ['label' => $course->name, 'url' => route('courses.show', $course)],
    ['label' => 'Tambah Materi']
]">
    <div style="max-width: 700px; margin: 2rem auto; padding: 2rem; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
            <a href="{{ route('courses.show', $course) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">&larr; Kembali ke {{ $course->name }}</a>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-top: 0.5rem;">Tambah Materi Perkuliahan</h1>
            <p style="color: #6b7280; font-size: 0.875rem;">Mata Kuliah: <strong>{{ $course->name }} ({{ $course->code }})</strong></p>
        </div>

        <form action="{{ route('courses.materials.store', $course) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Judul Materi *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="form-control" placeholder="Contoh: Pertemuan 09 - Upload File dan Keamanan" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                @error('title')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Tipe Materi *</label>
                <select name="type" id="material_type" required class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" onchange="toggleMaterialInputs()">
                    <option value="file" {{ old('type', 'file') === 'file' ? 'selected' : '' }}>Berkas Dokumen (PDF, PPTX, DOCX - Maks 20 MB)</option>
                    <option value="link" {{ old('type') === 'link' ? 'selected' : '' }}>Tautan Eksternal (URL)</option>
                    <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>Catatan Teks Saja</option>
                </select>
                @error('type')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div id="file_container" style="margin-bottom: 1.25rem; {{ old('type', 'file') === 'file' ? '' : 'display: none;' }}">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Pilih Berkas Materi (PDF, PPTX, DOCX - Maksimal 20 MB) *</label>
                <input type="file" name="file" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem;">Berkas disimpan di disk privat server (bukan public) demi keamanan hak cipta.</p>
                @error('file')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div id="link_container" style="margin-bottom: 1.25rem; {{ old('type') === 'link' ? '' : 'display: none;' }}">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Tautan Eksternal (URL) *</label>
                <input type="url" name="external_url" value="{{ old('external_url') }}" placeholder="https://example.com/slide-materi" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                @error('external_url')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Deskripsi / Petunjuk Bacaan</label>
                <textarea name="description" rows="4" class="form-control" placeholder="Tuliskan ringkasan materi atau petunjuk pembelajaran..." style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">{{ old('description') }}</textarea>
                @error('description')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-actions-row">
                <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Materi</button>
            </div>
        </form>

        <script>
            function toggleMaterialInputs() {
                const type = document.getElementById('material_type').value;
                const fileContainer = document.getElementById('file_container');
                const linkContainer = document.getElementById('link_container');
                if (type === 'file') {
                    fileContainer.style.display = 'block';
                    linkContainer.style.display = 'none';
                } else if (type === 'link') {
                    fileContainer.style.display = 'none';
                    linkContainer.style.display = 'block';
                } else {
                    fileContainer.style.display = 'none';
                    linkContainer.style.display = 'none';
                }
            }
        </script>
    </div>
</x-layout>
