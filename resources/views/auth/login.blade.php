<x-layout title="Masuk - KampusLMS">

    <div class="login-canvas">
        {{-- SISI KIRI: FORM LOGIN --}}
        <div class="login-form-pane">
            <h1 style="font-size: 2.25rem; font-weight: 800; color: #0f172a; margin: 0 0 0.45rem 0; letter-spacing: -0.025em; line-height: 1.2;">
                Welcome Back!
            </h1>
            <p style="color: #64748b; font-size: 0.95rem; margin: 0 0 2.25rem 0;">
                Let's continue your learning journey together.
            </p>

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                {{-- Input Email --}}
                <div style="margin-bottom: 1.25rem;">
                    <label for="email" style="display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem;">
                        Email
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           class="filter-search-input @error('email') is-invalid @enderror" 
                           value="{{ old('email') }}" 
                           placeholder="Enter your email address" 
                           required 
                           autofocus 
                           style="padding-left: 1.15rem; height: 48px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; font-size: 0.9rem;">
                    @error('email')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.35rem; font-weight: 500;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Input Password --}}
                <div style="margin-bottom: 1.15rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <label for="password" style="font-size: 0.875rem; font-weight: 600; color: #1e293b; margin: 0;">
                            Password
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" style="font-size: 0.8rem; color: #2563eb; text-decoration: none; font-weight: 600;">
                                Lupa kata sandi?
                            </a>
                        @endif
                    </div>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="filter-search-input @error('password') is-invalid @enderror" 
                           placeholder="Enter your password" 
                           required 
                           style="padding-left: 1.15rem; height: 48px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; font-size: 0.9rem;">
                    @error('password')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.35rem; font-weight: 500;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember Me Sesi --}}
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.75rem;">
                    <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }} style="border-radius: 4px; accent-color: #2563eb; width: 16px; height: 16px; cursor: pointer;">
                    <label for="remember" style="font-size: 0.85rem; color: #64748b; cursor: pointer; user-select: none;">
                        Ingat sesi masuk saya
                    </label>
                </div>

                {{-- Tombol Masuk --}}
                <button type="submit" class="login-submit-btn" style="height: 48px;">
                    Masuk &rarr;
                </button>
            </form>

            {{-- Catatan Akun Mahasiswa Terdaftar Oleh Admin --}}
            <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px dashed #e2e8f0; text-align: center;">
                <p style="font-size: 0.8rem; color: #94a3b8; margin: 0 0 0.5rem 0;">
                    Akun civitas akademika dibuat dan dikelola oleh Administrator KampusLMS.
                </p>
                {{-- Bantuan Cepat Pengujian Akun Praktikum --}}
                <div style="display: flex; gap: 0.35rem; justify-content: center; flex-wrap: wrap;">
                    <button type="button" onclick="fillDemo('mahasiswa@kampuslms.test', 'password')" class="btn-open-class" style="font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 8px;">
                        🎓 Akun Mahasiswa
                    </button>
                    <button type="button" onclick="fillDemo('dosen@kampuslms.test', 'password')" class="btn-open-class" style="font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 8px;">
                        👨‍🏫 Akun Dosen
                    </button>
                    <button type="button" onclick="fillDemo('admin@kampuslms.test', 'password')" class="btn-open-class" style="font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 8px;">
                        ⚙️ Akun Admin
                    </button>
                </div>
            </div>
        </div>

        {{-- SISI KANAN: ILUSTRASI MASKOT KAVI DENGAN KOMPOSISI DEKORATIF --}}
        <div class="login-mascot-pane">
            {{-- Elemen Dekorasi Organik & Geometris Sesuai Referensi --}}
            <div class="login-deco-bubble b1"></div>
            <div class="login-deco-bubble b2"></div>
            <div class="login-deco-bubble b3"></div>
            <div class="login-deco-bubble b4"></div>

            {{-- Sparkle Aksen di Sekitar Karakter --}}
            <div class="kavi-sparkle s1" style="top: 18%; left: 18%;">&#10022;</div>
            <div class="kavi-sparkle s2" style="top: 24%; right: 16%;">&#10022;</div>
            <div class="kavi-sparkle s3" style="bottom: 22%; right: 22%;">&#10022;</div>

            {{-- Wrapper Karakter KAVI --}}
            <div class="login-mascot-wrapper">
                <img src="{{ asset('images/illustrations/kavi-login.png') }}" 
                     alt="KAVI Maskot KampusLMS" 
                     class="login-mascot-kavi">
                {{-- Bayangan / Dudukan Kaki Lembut --}}
                <div class="login-cloud-pedestal"></div>
            </div>
        </div>
    </div>

    {{-- Script Bantuan Cepat Pengujian Akun Demo --}}
    <script>
        function fillDemo(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>

</x-layout>
