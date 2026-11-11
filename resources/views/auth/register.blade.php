<x-layout title="Registrasi Pengguna Baru - Khusus Administrator" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Pengguna', 'url' => route('users.index')],
    ['label' => 'Daftarkan Pengguna']
]">

    <div class="page-header">
        <div>
            <h1>Registrasi Pengguna Baru</h1>
            <p>Pendaftaran akun civitas akademika (Hanya dapat diakses oleh Administrator)</p>
        </div>
    </div>

    <div class="form-card">
        <form action="{{ route('register.store') }}" method="POST">
            @csrf

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nama beserta gelar jika ada" required autofocus>
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Alamat Surel (Email Kampus)</label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="user@kampuslms.test" required>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="role" class="form-label">Peran (Role Sistem)</label>
                    <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">-- Pilih Peran Pengguna --</option>
                        <option value="mahasiswa" {{ old('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        <option value="dosen" {{ old('role') === 'dosen' ? 'selected' : '' }}>Dosen Pengampu</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator Sistem</option>
                    </select>
                    @error('role')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="nim_nip" class="form-label">Nomor Induk (NIM / NIP)</label>
                    <input type="text" name="nim_nip" id="nim_nip" class="form-control @error('nim_nip') is-invalid @enderror" value="{{ old('nim_nip') }}" placeholder="Contoh: 10241017 atau 19850101...">
                    <p class="form-hint">Opsional, unik untuk setiap civitas akademika.</p>
                    @error('nim_nip')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi Awal</label>
                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Awal</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Ulangi kata sandi awal" required>
                </div>
            </div>

            <div class="form-actions-row">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Daftarkan Pengguna
                </button>
            </div>
        </form>
    </div>

</x-layout>
