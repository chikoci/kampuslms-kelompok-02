{{-- Halaman Detail Materi Perkuliahan --}}
<x-layout title="Materi: {{ $material->title }} - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
    ['label' => $material->course->name, 'url' => route('courses.show', $material->course)],
    ['label' => $material->title]
]">

    <div class="card-modern" style="max-width: 900px; margin: 0 auto;">
        {{-- Navigasi Kembali di Dalam Container Card Sesuai Permintaan UI/UX --}}
        <div style="margin-bottom: 1.25rem;">
            <a href="{{ route('courses.show', $material->course_id) }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;">
                &larr; Kembali ke {{ $material->course->name ?? 'Mata Kuliah' }}
            </a>
        </div>
        {{-- Header Materi --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span class="badge-code-modern" style="font-size: 0.75rem;">{{ $material->course->code ?? 'MATERI' }}</span>
                    <span class="badge" style="background: #eff6ff; color: #2563eb; font-size: 0.75rem; font-weight: 700; border-radius: 6px; padding: 0.2rem 0.5rem;">
                        Tipe: {{ ucfirst($material->type) }}
                    </span>
                </div>
                <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 0.35rem 0;">
                    {{ $material->title }}
                </h1>
                <p style="color: #64748b; font-size: 0.85rem; margin: 0;">
                    Diunggah pada {{ $material->created_at?->translatedFormat('d F Y, H:i') }} &bull; Mata Kuliah: <strong>{{ $material->course->name }}</strong>
                </p>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                @can('update', $material)
                    <a href="{{ route('materials.edit', $material) }}" class="btn-open-class" style="color: #d97706; border-color: #fde68a;">
                        Edit
                    </a>
                @endcan
                @can('delete', $material)
                    <form action="{{ route('materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus materi ini?');" style="display: inline; margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-open-class" style="color: #dc2626; border-color: #fecaca;">
                            Hapus
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Deskripsi / Catatan Materi --}}
        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">Keterangan Materi:</h3>
            <div style="color: #334155; line-height: 1.7; background: #f8fafc; padding: 1.25rem 1.5rem; border-radius: 16px; border: 1px solid #eef2f6;">
                {{ $material->description ?: 'Tidak ada keterangan tambahan yang dicantumkan.' }}
            </div>
        </div>

        {{-- Berkas Lampiran atau Tautan Eksternal --}}
        @if($material->original_name || $material->file_path)
            <div style="padding: 1.5rem; background: #eff6ff; border-radius: 18px; border: 1px solid #bfdbfe; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <strong style="color: #1e40af; font-size: 1rem; display: block; margin-bottom: 0.25rem;">Berkas Materi Unduhan</strong>
                    <p style="color: #3b82f6; font-size: 0.85rem; margin: 0;">{{ $material->original_name ?? 'Berkas Perkuliahan' }}</p>
                </div>
                <a href="{{ route('materials.download', $material) }}" class="filter-btn-primary" style="text-decoration: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Unduh Berkas
                </a>
            </div>
        @elseif($material->type === 'link' && $material->external_url)
            <div style="padding: 1.5rem; background: #eff6ff; border-radius: 18px; border: 1px solid #bfdbfe; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <strong style="color: #1e40af; font-size: 1rem; display: block; margin-bottom: 0.25rem;">Tautan Eksternal Perkuliahan</strong>
                    <p style="color: #3b82f6; font-size: 0.85rem; margin: 0;">{{ $material->external_url }}</p>
                </div>
                <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="filter-btn-primary" style="text-decoration: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                    Buka Tautan &rarr;
                </a>
            </div>
        @endif
    </div>

</x-layout>
