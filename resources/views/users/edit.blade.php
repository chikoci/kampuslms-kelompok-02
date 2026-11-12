<x-layout title="Edit Pengguna - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Pengguna', 'url' => route('users.index')],
    ['label' => $user->name, 'url' => route('users.show', $user)],
    ['label' => 'Edit']
]">

    <div class="form-card" style="margin-top: 1rem;">
        {{-- Card Header Terpadu di Dalam 1 Card --}}
        <div style="margin-bottom: 2rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <span class="badge {{ $user->role === 'admin' ? 'badge-danger' : ($user->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.75rem; text-transform: capitalize; padding: 0.25rem 0.65rem;">
                    {{ $user->role }}
                </span>
                @if($user->nim_nip)
                    <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">ID: {{ $user->nim_nip }}</span>
                @endif
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 0.35rem 0; letter-spacing: -0.02em;">
                Edit Pengguna: {{ $user->name }}
            </h1>
            <p style="font-size: 0.925rem; color: #64748b; margin: 0; line-height: 1.4;">
                Perbarui informasi profil akun dan hak akses pengguna di KampusLMS
            </p>
        </div>

        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="role" class="form-label">Peran (Role)</label>
                    <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="mahasiswa" {{ old('role', $user->role) === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        <option value="dosen" {{ old('role', $user->role) === 'dosen' ? 'selected' : '' }}>Dosen</option>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                    @error('role')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="nim_nip" class="form-label">NIM / NIP</label>
                    <input type="text" name="nim_nip" id="nim_nip" class="form-control @error('nim_nip') is-invalid @enderror" value="{{ old('nim_nip', $user->nim_nip) }}">
                    @error('nim_nip')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi Baru (Opsional)</label>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Kosongkan jika tidak ingin mengubah password">
                <p class="form-hint">Isi minimal 6 karakter hanya bila ingin mengganti kata sandi pengguna.</p>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-actions-row">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</x-layout>
