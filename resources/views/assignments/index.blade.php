<x-layout title="Tugas: {{ $course->name }}">
    <div class="header-actions-row">
        <div>
            <a href="{{ route('courses.show', $course) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">&larr; Kembali ke {{ $course->name }}</a>
            <h1 class="page-title" style="margin-top: 0.5rem;">Daftar Tugas: {{ $course->name }}</h1>
        </div>
        @can('create', [\App\Models\Assignment::class, $course])
            <a href="{{ route('courses.assignments.create', $course) }}" class="btn btn-primary">
                + Tambah Tugas
            </a>
        @endcan
    </div>

    <div class="table-container">
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
                            <a href="{{ route('assignments.submissions.index', $assignment) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">
                                {{ $assignment->submissions()->count() }} Pengumpulan &rarr;
                            </a>
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
