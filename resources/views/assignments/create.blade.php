<x-layout title="Buat Tugas: {{ $course->name }}"
    :backUrl="route('courses.show', $course)"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
        ['label' => $course->name, 'url' => route('courses.show', $course)],
        ['label' => 'Tambah Tugas']
    ]">
    <div style="max-width: 750px; margin: 1.5rem auto; padding: 2rem; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9;">
            <h1 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0 0 0.35rem 0;">Buat Tugas Perkuliahan Baru</h1>
            <p style="color: #6b7280; font-size: 0.875rem;">Mata Kuliah: <strong>{{ $course->name }} ({{ $course->code }})</strong></p>
        </div>

        <form action="{{ route('courses.assignments.store', $course) }}" method="POST">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Judul Tugas *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="form-control" placeholder="Contoh: Tugas 2 - Implementasi Policy dan RBAC" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                @error('title')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Batas Waktu (Due Date & Time) *</label>
                    <input type="datetime-local" name="due_at" value="{{ old('due_at') }}" required class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    @error('due_at')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Nilai Maksimal (1 - 100) *</label>
                    <input type="number" name="max_score" min="1" max="100" value="{{ old('max_score', 100) }}" required class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    @error('max_score')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Status Tugas *</label>
                    <select name="status" required class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published (Diterbitkan)</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (Konsep)</option>
                        <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>Closed (Ditutup)</option>
                    </select>
                    @error('status')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
                <div style="display: flex; align-items: center; margin-top: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #374151; cursor: pointer;">
                        <input type="hidden" name="allow_late" value="0">
                        <input type="checkbox" name="allow_late" value="1" {{ old('allow_late', '1') == '1' ? 'checked' : '' }}>
                        Izinkan Pengumpulan Terlambat
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Petunjuk Pengerjaan</label>
                <textarea name="instructions" rows="4" class="form-control" placeholder="Tuliskan petunjuk pengerjaan tugas, format berkas, dan kriteria penilaian..." style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">{{ old('instructions') }}</textarea>
                @error('instructions')
                    <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-actions-row">
                <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Terbitkan Tugas</button>
            </div>
        </form>
    </div>
</x-layout>
