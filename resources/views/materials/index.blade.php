<x-layout title="Materi Perkuliahan: {{ $course->name }}"
    :backUrl="route('courses.show', $course)"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
        ['label' => $course->name, 'url' => route('courses.show', $course)],
        ['label' => 'Daftar Materi']
    ]">
    <div class="header-actions-row">
        <div>
            <h1 class="page-title">Daftar Materi: {{ $course->name }}</h1>
        </div>
        @can('create', [\App\Models\Material::class, $course])
            <a href="{{ route('courses.materials.create', $course) }}" class="btn btn-primary btn-sm" style="font-weight: 700; border-radius: 8px;">
                + Materi
            </a>
        @endcan
    </div>

    {{-- Mobile Compact Cards View (Khusus Mobile <= 768px) --}}
    <div class="materials-mobile-cards" style="display: none; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
        @forelse($materials as $material)
            <div class="course-card-compact" onclick="window.location='{{ route('materials.show', $material) }}'">
                <div style="flex: 1; min-width: 0; padding-right: 0.5rem;">
                    <h3 class="course-compact-title">{{ $material->title }}</h3>
                    <div class="course-compact-meta">
                        <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 6px;">
                            {{ ucfirst($material->type) }}
                        </span>
                        @if($material->formatted_file_size)
                            <span style="font-size: 0.72rem; color: #64748b;">{{ $material->formatted_file_size }}</span>
                        @endif
                        <span style="font-size: 0.72rem; color: #64748b;">
                            {{ $material->created_at?->format('d M Y') }}
                        </span>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0;">
                    @can('update', $material)
                        <a href="{{ route('materials.edit', $material) }}" class="btn-icon-action edit" title="Edit Materi" onclick="event.stopPropagation()">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>
                    @endcan
                    @can('delete', $material)
                        <form action="{{ route('materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus materi ini?');" style="margin: 0; display: inline;" onclick="event.stopPropagation()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-action delete" title="Hapus Materi">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        @empty
            <div class="card-modern empty-state-card" style="padding: 1.5rem 1rem;">
                <p class="empty-state-text">Belum ada materi perkuliahan untuk mata kuliah ini.</p>
            </div>
        @endforelse
    </div>

    {{-- Desktop Table View --}}
    <div class="table-container materials-desktop-table">
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
