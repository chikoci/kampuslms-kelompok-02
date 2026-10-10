<x-layout title="Lupa Kata Sandi - KampusLMS">

    <div class="page-header" style="text-align: center; max-width: 480px; margin: 0 auto 2rem auto;">
        <h1>Atur Ulang Kata Sandi</h1>
        <p>Masukkan alamat surel Anda untuk menerima tautan pembaruan kata sandi</p>
    </div>

    <div class="form-card" style="max-width: 480px; margin: 0 auto;">
        @if (session('status'))
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                {{ session('status') }}
            </div>
        @endif

        {{-- Tautan demo untuk kemudahan pengujian di lokal --}}
        @if (session('demo_reset_url'))
            <div class="alert alert-info" style="margin-bottom: 1.5rem; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 1rem; border-radius: 8px; font-size: 0.85rem;">
                <strong>Tautan Demo Lokal:</strong><br>
                <a href="{{ session('demo_reset_url') }}" style="color: #1d4ed8; word-break: break-all;">
                    Klik di sini untuk langsung membuka formulir reset kata sandi &rarr;
                </a>
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Alamat Surel (Email)</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="nama@kampuslms.test" required autofocus>
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem 1rem;">
                    Kirim Tautan Atur Ulang
                </button>
                <a href="{{ route('login') }}" class="btn btn-secondary" style="width: 100%; justify-content: center; text-align: center; padding: 0.65rem 1rem;">
                    &larr; Kembali ke Halaman Masuk
                </a>
            </div>
        </form>
    </div>

</x-layout>
