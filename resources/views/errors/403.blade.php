<x-layout title="403 - Akses Ditolak">
    <div style="max-width: 600px; margin: 4rem auto; text-align: center; padding: 2.5rem; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background-color: #fee2e2; color: #dc2626; margin-bottom: 1.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>

        <h1 style="font-size: 1.875rem; font-weight: 700; color: #111827; margin-bottom: 0.75rem;">
            403 - Akses Ditolak
        </h1>

        <p style="font-size: 1rem; color: #4b5563; line-height: 1.5; margin-bottom: 2rem;">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses atau izin yang sah untuk membuka sumber daya ini.' }}
        </p>

        <div style="display: flex; justify-content: center; gap: 1rem;">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" 
               style="display: inline-flex; align-items: center; padding: 0.625rem 1.25rem; font-size: 0.875rem; font-weight: 500; border-radius: 8px; background-color: #f3f4f6; color: #374151; text-decoration: none; border: 1px solid #d1d5db;">
                &larr; Kembali ke Halaman Sebelumnya
            </a>

            <a href="{{ route('home') }}" 
               style="display: inline-flex; align-items: center; padding: 0.625rem 1.25rem; font-size: 0.875rem; font-weight: 500; border-radius: 8px; background-color: #2563eb; color: #ffffff; text-decoration: none;">
                Halaman Utama
            </a>
        </div>
    </div>
</x-layout>
