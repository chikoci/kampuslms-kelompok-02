<x-layout title="Registrasi Pengguna Baru - Khusus Administrator" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Pengguna', 'url' => route('users.index')],
    ['label' => 'Daftarkan Pengguna']
]">

    <div class="form-card" style="margin-top: 1rem;">
        {{-- Card Header Terpadu di Dalam 1 Card --}}
        <div style="margin-bottom: 2rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <span class="badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                    </svg>
                    Khusus Administrator
                </span>
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 0.35rem 0; letter-spacing: -0.02em;">
                Registrasi Pengguna Baru
            </h1>
            <p style="font-size: 0.925rem; color: #64748b; margin: 0; line-height: 1.4;">
                Pendaftaran akun civitas akademika (Mahasiswa, Dosen, atau Administrator) ke sistem KampusLMS
            </p>
        </div>

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
