<x-layout title="Atur Ulang Kata Sandi - KampusLMS">

    <div class="login-canvas">
        {{-- SISI KIRI: FORM KATA SANDI BARU --}}
        <div class="login-form-pane">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 0.78rem; padding: 0.35rem 0.85rem; border-radius: 9999px; margin-bottom: 1.25rem; width: fit-content; border: 1px solid #dbeafe;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Kata Sandi Baru</span>
            </div>

            <h1 style="font-size: 2.15rem; font-weight: 800; color: #0f172a; margin: 0 0 0.5rem 0; letter-spacing: -0.025em; line-height: 1.2;">
                Atur Kata Sandi
            </h1>
            <p style="color: #64748b; font-size: 0.92rem; margin: 0 0 2rem 0; line-height: 1.5;">
                Silakan buat kata sandi baru yang kuat untuk mengamankan akun Anda.
            </p>

            <form action="{{ route('password.update') }}" method="POST">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                {{-- Input Email (Readonly) --}}
                <div style="margin-bottom: 1.25rem;">
                    <label for="email" style="display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem;">
                        Alamat Email
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           class="filter-search-input @error('email') is-invalid @enderror" 
                           value="{{ old('email', $email) }}" 
                           placeholder="nama@kampuslms.test" 
                           required 
                           readonly 
                           style="padding-left: 1.15rem; height: 48px; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 0.9rem; color: #64748b;">
                    @error('email')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.35rem; font-weight: 500;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Input Password Baru --}}
                <div style="margin-bottom: 1.25rem;">
                    <label for="password" style="display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem;">
                        Kata Sandi Baru
                    </label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="filter-search-input @error('password') is-invalid @enderror" 
                           placeholder="Minimal 8 karakter" 
                           required 
                           autofocus 
                           style="padding-left: 1.15rem; height: 48px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; font-size: 0.9rem;">
                    @error('password')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.35rem; font-weight: 500;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div style="margin-bottom: 1.75rem;">
                    <label for="password_confirmation" style="display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem;">
                        Konfirmasi Kata Sandi Baru
                    </label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           class="filter-search-input" 
                           placeholder="Ulangi kata sandi baru" 
                           required 
                           style="padding-left: 1.15rem; height: 48px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; font-size: 0.9rem;">
                </div>

                {{-- Tombol Simpan --}}
                <button type="submit" class="login-submit-btn" style="height: 48px; margin-bottom: 0.85rem;">
                    Simpan Kata Sandi Baru &rarr;
                </button>

                {{-- Link Kembali ke Halaman Masuk --}}
                <a href="{{ route('login') }}" class="btn-open-class" style="width: 100%; display: flex; justify-content: center; align-items: center; padding: 0.75rem 1rem; border-radius: 14px; font-size: 0.875rem; font-weight: 600; text-decoration: none; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; transition: all 0.2s ease;">
                    &larr; Batal &amp; Kembali ke Halaman Masuk
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
