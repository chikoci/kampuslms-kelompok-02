<x-layout title="Materi: {{ $material->title }}">
    <div style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
            <div>
                <a href="{{ route('courses.show', $material->course_id) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">&larr; Kembali ke {{ $material->course->name }}</a>
                <h1 style="font-size: 1.75rem; font-weight: 700; color: #111827; margin-top: 0.5rem;">{{ $material->title }}</h1>
                <p style="color: #6b7280; font-size: 0.875rem;">Tipe: <strong>{{ ucfirst($material->type) }}</strong> | Diunggah pada {{ $material->created_at?->format('d M Y') }}</p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                @can('update', $material)
                    <a href="{{ route('materials.edit', $material) }}" class="btn btn-edit btn-sm">Edit</a>
                @endcan
                @can('delete', $material)
                    <form action="{{ route('materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus materi ini?');" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                @endcan
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Keterangan Materi:</h3>
            <div style="color: #4b5563; line-height: 1.6; background: #f9fafb; padding: 1.25rem; border-radius: 8px; border: 1px solid #f3f4f6;">
                {{ $material->description ?: 'Tidak ada deskripsi tambahan.' }}
            </div>
        </div>

        @if($material->original_name || $material->file_path)
            <div style="padding: 1.25rem; background: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: #1e40af; font-size: 0.95rem;">Dokumen Lampiran</strong>
                    <p style="color: #3b82f6; font-size: 0.85rem; margin-top: 0.25rem;">{{ $material->original_name ?? 'Berkas Materi Perkuliahan' }}</p>
                </div>
                <a href="{{ route('materials.download', $material) }}" class="btn btn-primary btn-sm">
                    &#128190; Unduh Berkas
                </a>
            </div>
        @endif
    </div>
</x-layout>
