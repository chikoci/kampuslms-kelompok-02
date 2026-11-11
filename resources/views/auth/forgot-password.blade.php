<x-layout title="Lupa Kata Sandi - KampusLMS">

    <div class="login-canvas">
        {{-- SISI KIRI: FORM ATUR ULANG KATA SANDI --}}
        <div class="login-form-pane">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 0.78rem; padding: 0.35rem 0.85rem; border-radius: 9999px; margin-bottom: 1.25rem; width: fit-content; border: 1px solid #dbeafe;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span>Pemulihan Akun</span>
            </div>

            <h1 style="font-size: 2.15rem; font-weight: 800; color: #0f172a; margin: 0 0 0.5rem 0; letter-spacing: -0.025em; line-height: 1.2;">
                Lupa Kata Sandi?
            </h1>
            <p style="color: #64748b; font-size: 0.92rem; margin: 0 0 2rem 0; line-height: 1.5;">
                Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
            </p>

            @if (session('status'))
                <div class="alert alert-success" style="border-radius: 14px; margin-bottom: 1.5rem; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.85rem 1rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.6rem;">
                    <span style="font-weight: 700; font-size: 1rem;">&#10003;</span>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            {{-- Tautan demo untuk kemudahan pengujian di lokal --}}
            @if (session('demo_reset_url'))
                <div class="alert alert-info" style="margin-bottom: 1.5rem; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 0.9rem 1rem; border-radius: 14px; font-size: 0.85rem;">
                    <strong>Tautan Demo Lokal:</strong><br>
                    <a href="{{ session('demo_reset_url') }}" style="color: #2563eb; word-break: break-all; font-weight: 600; text-decoration: underline;">
                        Klik di sini untuk langsung membuka formulir reset kata sandi &rarr;
                    </a>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST">
                @csrf

                {{-- Input Email --}}
                <div style="margin-bottom: 1.5rem;">
                    <label for="email" style="display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem;">
                        Alamat Email
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           class="filter-search-input @error('email') is-invalid @enderror" 
                           value="{{ old('email') }}" 
                           placeholder="nama@kampuslms.test" 
                           required 
                           autofocus 
                           style="padding-left: 1.15rem; height: 48px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; font-size: 0.9rem;">
                    @error('email')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.35rem; font-weight: 500;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tombol Kirim Tautan --}}
                <button type="submit" class="login-submit-btn" style="height: 48px; margin-bottom: 0.85rem;">
                    Kirim Tautan Atur Ulang &rarr;
                </button>

                {{-- Link Kembali ke Halaman Masuk --}}
                <a href="{{ route('login') }}" class="btn-open-class" style="width: 100%; display: flex; justify-content: center; align-items: center; padding: 0.75rem 1rem; border-radius: 14px; font-size: 0.875rem; font-weight: 600; text-decoration: none; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; transition: all 0.2s ease;">
                    &larr; Kembali ke Halaman Masuk
                </a>
            </form>
        </div>

        {{-- SISI KANAN: ILUSTRASI MASKOT KAVI DENGAN KOMPOSISI DEKORATIF --}}
        <div class="login-mascot-pane">
            <div class="login-deco-bubble b1"></div>
            <div class="login-deco-bubble b2"></div>
            <div class="login-deco-bubble b3"></div>
            <div class="login-deco-bubble b4"></div>

            <div class="kavi-sparkle s1" style="top: 18%; left: 18%;">&#10022;</div>
            <div class="kavi-sparkle s2" style="top: 24%; right: 16%;">&#10022;</div>
            <div class="kavi-sparkle s3" style="bottom: 22%; right: 22%;">&#10022;</div>

            <div class="login-mascot-wrapper">
                <img src="{{ asset('images/illustrations/kavi-login.png') }}" 
                     alt="KAVI Maskot KampusLMS" 
                     class="login-mascot-kavi">
                <div class="login-cloud-pedestal"></div>
            </div>
        </div>
    </div>

</x-layout>
