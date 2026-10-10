<x-layout title="Masuk - KampusLMS">

    <div class="page-header" style="text-align: center; max-width: 480px; margin: 0 auto 2rem auto;">
        <h1>Masuk ke KampusLMS</h1>
        <p>Silakan masuk menggunakan akun civitas akademika Anda</p>
    </div>

    <div class="form-card" style="max-width: 480px; margin: 0 auto;">
        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Alamat Surel (Email)</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="nama@kampuslms.test" required autofocus>
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Kata Sandi</label>
                    <a href="{{ route('password.request') }}" style="font-size: 0.8rem; color: #2563eb; text-decoration: none;">Lupa kata sandi?</a>
                </div>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember" style="font-size: 0.875rem; color: #475569; cursor: pointer;">Ingat sesi masuk saya</label>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem 1rem;">
                    Masuk Sekarang
                </button>
            </div>
        </form>

        {{-- Bantuan Akun Demo Praktikum --}}
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px dashed #cbd5e1; font-size: 0.825rem; color: #64748b;">
            <p style="font-weight: 600; color: #334155; margin-bottom: 0.5rem;">Akun Demo Praktikum (Kata sandi: <code>password</code>):</p>
            <ul style="list-style: none; padding-left: 0; display: flex; flex-direction: column; gap: 0.35rem;">
                <li><strong>Admin:</strong> <code>admin@kampuslms.test</code></li>
                <li><strong>Dosen:</strong> <code>dosen@kampuslms.test</code></li>
                <li><strong>Mahasiswa:</strong> <code>mahasiswa@kampuslms.test</code></li>
            </ul>
        </div>
    </div>

</x-layout>
