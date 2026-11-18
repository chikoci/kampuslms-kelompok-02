<x-layout title="Tugas: {{ $course->name }}"
    :backUrl="route('courses.show', $course)"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
        ['label' => $course->name, 'url' => route('courses.show', $course)],
        ['label' => 'Daftar Tugas']
    ]">
    <div class="header-actions-row">
        <div>
            <h1 class="page-title">Daftar Tugas: {{ $course->name }}</h1>
        </div>
        @can('create', [\App\Models\Assignment::class, $course])
            <a href="{{ route('courses.assignments.create', $course) }}" class="btn btn-primary btn-sm" style="font-weight: 700; border-radius: 8px;">
                + Tugas
            </a>
        @endcan
    </div>

    {{-- Mobile Compact Cards View (Khusus Mobile <= 768px) --}}
    <div class="assignments-mobile-cards" style="display: none; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
        @forelse($assignments as $assignment)
            <div class="course-card-compact" onclick="window.location='{{ route('assignments.show', $assignment) }}'">
                <div style="flex: 1; min-width: 0; padding-right: 0.5rem;">
                    <h3 class="course-compact-title">{{ $assignment->title }}</h3>
                    <div class="course-compact-meta">
                        @if($assignment->due_at?->isPast())
                            <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 6px;">Lewat</span>
                        @else
                            <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 6px;">Aktif</span>
                        @endif
                        <span class="badge-status-dot {{ $assignment->status }}" style="font-size: 0.65rem; padding: 0.1rem 0.4rem;">
                            <span class="dot"></span>
                            {{ ucfirst($assignment->status) }}
                        </span>
                        <span style="font-size: 0.72rem; color: #64748b;">
                            {{ $assignment->due_at?->format('d M Y, H:i') }}
                        </span>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0;">
                    @can('update', $assignment)
                        <a href="{{ route('assignments.edit', $assignment) }}" class="btn-icon-action edit" title="Edit Tugas" onclick="event.stopPropagation()">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>
                    @endcan
                    @can('delete', $assignment)
                        <form action="{{ route('assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Hapus tugas ini?');" style="margin: 0; display: inline;" onclick="event.stopPropagation()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-action delete" title="Hapus Tugas">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </form>
                    @endcan

                    <div class="course-chevron">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </div>
            </div>
        @empty
            <div class="card-modern empty-state-card" style="padding: 1.5rem 1rem;">
                <p class="empty-state-text">Belum ada tugas untuk mata kuliah ini.</p>
            </div>
        @endforelse
    </div>

    {{-- Desktop Table View --}}
    <div class="table-container assignments-desktop-table">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Judul Tugas</th>
                    <th>Batas Waktu</th>
                    <th>Nilai Maks</th>
                    <th>Status</th>
                    <th>Pengumpulan</th>
                    <th class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td class="font-semibold text-body">
                            <a href="{{ route('assignments.show', $assignment) }}" class="table-link">
                                {{ $assignment->title }}
                            </a>
                        </td>
                        <td>
                            {{ $assignment->due_at?->format('d M Y, H:i') }}
                            @if($assignment->due_at?->isPast())
                                <span class="badge badge-overdue">Lewat</span>
                            @else
                                <span class="badge badge-upcoming">Aktif</span>
                            @endif
                        </td>
                        <td class="font-semibold text-center">{{ $assignment->max_score }}</td>
                        <td>
                            <span class="badge badge-{{ $assignment->status }}">
                                {{ ucfirst($assignment->status) }}
                            </span>
                        </td>
                        <td>
                            @if (auth()->user()?->role === 'mahasiswa')
                                @php
                                    $mySub = $assignment->submissions->firstWhere('user_id', auth()->id());
                                @endphp
                                @if ($mySub)
                                    <a href="{{ route('submissions.show', $mySub) }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Klik untuk melihat detail pengumpulan Anda">
                                        <span class="badge badge-active" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                            &#10003; Sudah Dikumpulkan
                                        </span>
                                    </a>
                                @else
                                    <a href="{{ route('assignments.show', $assignment) }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Klik untuk mengumpulkan tugas">
                                        <span class="badge badge-overdue" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                            Belum Mengumpulkan &rarr;
                                        </span>
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('assignments.submissions.index', $assignment) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">
                                    {{ $assignment->submissions_count }} Pengumpulan &rarr;
                                </a>
                            @endif
                        </td>
                        <td class="action-buttons-cell">
                            <div class="action-buttons-group">
                                <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-secondary btn-sm">Detail</a>
                                @can('update', $assignment)
                                    <a href="{{ route('assignments.edit', $assignment) }}" class="btn btn-edit btn-sm">Edit</a>
                                @endcan
                                @can('delete', $assignment)
                                    <form action="{{ route('assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Hapus tugas ini?');" class="inline-form">
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
                        <td colspan="6" class="empty-cell">Belum ada tugas untuk mata kuliah ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assignments->hasPages())
        <div class="pagination-wrapper">
            {{ $assignments->links() }}
        </div>
    @endif
</x-layout>
