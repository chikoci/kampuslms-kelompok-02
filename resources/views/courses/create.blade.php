<x-layout title="Tambah Mata Kuliah">

    <div class="page-header">
        <h1>Tambah Mata Kuliah Baru</h1>
        <p>Lengkapi formulir di bawah ini untuk mendaftarkan mata kuliah baru ke sistem</p>
    </div>

    <div class="form-card">
        <form action="{{ route('courses.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="code" class="form-label">Kode Mata Kuliah</label>
                <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" placeholder="Contoh: SI206" required>
                <p class="form-hint">Kode unik mata kuliah (maksimal 20 karakter, contoh: SI101, SI206).</p>
            </div>

            <div class="form-group">
                <label for="name" class="form-label">Nama Mata Kuliah</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Pemrograman Berorientasi Objek" required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Deskripsi Mata Kuliah</label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Ringkasan silabus dan capaian pembelajaran mata kuliah">{{ old('description') }}</textarea>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="sks" class="form-label">Bobot SKS</label>
                    <input type="number" name="sks" id="sks" class="form-control" value="{{ old('sks', 3) }}" min="1" max="6" required>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="lecturer_id" class="form-label">Dosen Pengampu</label>
                <select name="lecturer_id" id="lecturer_id" class="form-select" required>
                    <option value="">-- Pilih Dosen Pengampu --</option>
                    @foreach ($lecturers as $lecturer)
                        <option value="{{ $lecturer->id }}" {{ old('lecturer_id') == $lecturer->id ? 'selected' : '' }}>
                            {{ $lecturer->name }} ({{ $lecturer->nim_nip ?? 'NIP Belum Ada' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-actions">
                <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan Mata Kuliah
                </button>
            </div>
        </form>
    </div>

</x-layout>
