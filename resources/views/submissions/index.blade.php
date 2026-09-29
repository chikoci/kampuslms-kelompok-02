<x-layout title="Daftar Pengumpulan Tugas">
    <div class="header-actions-row">
        <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Tugas
        </a>
    </div>

    <div class="page-header">
        <h1 class="page-title-lg">Pengumpulan Tugas: {{ $assignment->title }}</h1>
        <p style="color: #6b7280; font-size: 0.875rem;">Mata Kuliah: <strong>{{ $assignment->course->name ?? '-' }}</strong> | Total: <strong>{{ $submissions->total() }} Mahasiswa</strong></p>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Mahasiswa</th>
                    <th>Waktu Kumpul</th>
                    <th>Status</th>
                    <th>Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $submission)
                    <tr>
                        <td class="font-semibold text-body">{{ $submission->student->name ?? 'Mahasiswa' }}</td>
                        <td class="text-muted">{{ $submission->submitted_at?->format('d M Y, H:i') ?? '-' }}</td>
                        <td>
                            @if($submission->is_late)
                                <span class="badge badge-overdue">Terlambat</span>
                            @else
                                <span class="badge badge-upcoming">Tepat Waktu</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ Str::limit($submission->note ?: '-', 40) }}</td>
                        <td>
                            <a href="{{ route('submissions.show', $submission) }}" class="btn btn-sm" style="background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 4px 10px; border-radius: 6px;">
                                Periksa &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-cell-sm">Belum ada mahasiswa yang mengumpulkan tugas ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($submissions->hasPages())
        <div class="pagination-wrapper" style="margin-top: 1.5rem;">
            {{ $submissions->links() }}
        </div>
    @endif
</x-layout>
