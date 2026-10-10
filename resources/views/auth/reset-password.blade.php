<x-layout title="Atur Ulang Kata Sandi - KampusLMS">

    <div class="page-header" style="text-align: center; max-width: 480px; margin: 0 auto 2rem auto;">
        <h1>Kata Sandi Baru</h1>
        <p>Silakan buat kata sandi baru yang kuat untuk akun Anda</p>
    </div>

    <div class="form-card" style="max-width: 480px; margin: 0 auto;">
        <form action="{{ route('password.update') }}" method="POST">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email" class="form-label">Alamat Surel (Email)</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $email) }}" placeholder="nama@kampuslms.test" required readonly>
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi Baru</label>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required autofocus>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Ulangi kata sandi baru" required>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem 1rem;">
                    Simpan Kata Sandi Baru
                </button>
            </div>
        </form>
    </div>

</x-layout>
