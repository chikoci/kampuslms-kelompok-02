<x-layout title="Tambah Pengguna">

    <div class="page-header">
        <h1>Tambah Pengguna Baru</h1>
        <p>Lengkapi formulir untuk membuat akun baru di sistem KampusLMS</p>
    </div>

    <div class="form-card">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" placeholder="contoh@kampuslms.test" required>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="role" class="form-label">Peran (Role)</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="mahasiswa" {{ old('role', 'mahasiswa') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        <option value="dosen" {{ old('role') === 'dosen' ? 'selected' : '' }}>Dosen</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nim_nip" class="form-label">NIM / NIP</label>
                    <input type="text" name="nim_nip" id="nim_nip" class="form-control" value="{{ old('nim_nip') }}" placeholder="Nomor Induk Pegawai / Mahasiswa">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>

            <div class="form-actions">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>

</x-layout>
