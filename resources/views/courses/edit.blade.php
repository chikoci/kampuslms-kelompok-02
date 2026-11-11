<x-layout title="Edit Mata Kuliah - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
    ['label' => $course->name, 'url' => route('courses.show', $course)],
    ['label' => 'Edit Mata Kuliah']
]">

    <div class="page-header">
        <h1>Edit Mata Kuliah: {{ $course->name }}</h1>
        <p>Perbarui informasi data mata kuliah di bawah ini</p>
    </div>

    <div class="form-card">
        <form action="{{ route('courses.update', $course) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="code" class="form-label">Kode Mata Kuliah</label>
                <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $course->code) }}" required>
                <p class="form-hint">Kode unik mata kuliah (contoh: SI101, SI206).</p>
                @error('code')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="name" class="form-label">Nama Mata Kuliah</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $course->name) }}" required>
                @error('name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Deskripsi Mata Kuliah</label>
                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $course->description) }}</textarea>
                @error('description')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="sks" class="form-label">Bobot SKS</label>
                    <input type="number" name="sks" id="sks" class="form-control @error('sks') is-invalid @enderror" value="{{ old('sks', $course->sks) }}" min="1" max="6" required>
                    @error('sks')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="draft" {{ old('status', $course->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ old('status', $course->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="archived" {{ old('status', $course->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                    @error('status')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="lecturer_id" class="form-label">Dosen Pengampu</label>
                @if (auth()->user()?->role === 'admin')
                    <select name="lecturer_id" id="lecturer_id" class="form-select @error('lecturer_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Dosen Pengampu --</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}" {{ old('lecturer_id', $course->lecturer_id) == $lecturer->id ? 'selected' : '' }}>
                                {{ $lecturer->name }} ({{ $lecturer->nim_nip ?? 'NIP Belum Ada' }})
                            </option>
                        @endforeach
                    </select>
                    @error('lecturer_id')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                @else
                    <input type="text" class="form-control" value="{{ $course->lecturer?->name }} ({{ $course->lecturer?->nim_nip ?? 'NIP Belum Ada' }})" disabled readonly style="background-color: #f3f4f6; color: #4b5563; cursor: not-allowed;">
                    <input type="hidden" name="lecturer_id" value="{{ $course->lecturer_id }}">
                    <p class="form-hint" style="color: #6b7280; font-size: 0.8125rem; margin-top: 0.35rem;">* Hanya Administrator yang berwenang memindahkan atau mengganti Dosen Pengampu mata kuliah.</p>
                @endif
            </div>

            <div class="form-actions-row">
                <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</x-layout>
