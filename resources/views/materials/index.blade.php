<x-layout title="Materi Perkuliahan: {{ $course->name }}">
    <div class="header-actions-row">
        <div>
            <a href="{{ route('courses.show', $course) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">&larr; Kembali ke {{ $course->name }}</a>
            <h1 class="page-title" style="margin-top: 0.5rem;">Daftar Materi: {{ $course->name }}</h1>
        </div>
        @can('create', [\App\Models\Material::class, $course])
            <a href="{{ route('courses.materials.create', $course) }}" class="btn btn-primary">
                + Tambah Materi
            </a>
        @endcan
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Judul Materi</th>
                    <th>Tipe</th>
                    <th>Keterangan</th>
                    <th>Berkas</th>
                    <th class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                    <tr>
                        <td class="font-semibold text-body">
                            <a href="{{ route('materials.show', $material) }}" class="table-link">
                                {{ $material->title }}
                            </a>
                        </td>
                        <td><span class="badge badge-code">{{ ucfirst($material->type) }}</span></td>
                        <td class="text-muted">{{ Str::limit($material->description ?: '-', 40) }}</td>
                        <td>
                            @if($material->original_name || $material->file_path)
                                <a href="{{ route('materials.download', $material) }}" class="badge badge-file" style="text-decoration: none;">
                                    &#128190; Unduh
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="action-buttons-cell">
                            <div class="action-buttons-group">
                                <a href="{{ route('materials.show', $material) }}" class="btn btn-secondary btn-sm">Detail</a>
                                @can('update', $material)
                                    <a href="{{ route('materials.edit', $material) }}" class="btn btn-edit btn-sm">Edit</a>
                                @endcan
                                @can('delete', $material)
                                    <form action="{{ route('materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus materi ini?');" class="inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-cell">Belum ada materi perkuliahan untuk mata kuliah ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($materials->hasPages())
        <div class="pagination-wrapper">
            {{ $materials->links() }}
        </div>
    @endif
</x-layout>
