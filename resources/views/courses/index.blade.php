{{-- Halaman Daftar Mata Kuliah --}}
<x-layout title="Daftar Mata Kuliah">

    <div class="header-actions-row">
        <div>
            <h1 class="page-title">Mata Kuliah</h1>
            <p class="page-subtitle">Kelola seluruh mata kuliah akademik yang terdaftar di database</p>
        </div>
        <a href="{{ route('courses.create') }}" class="btn btn-primary">
            + Tambah Mata Kuliah
        </a>
    </div>

    {{-- Tabel daftar mata kuliah --}}
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-code">Kode</th>
                    <th>Nama Mata Kuliah</th>
                    <th class="col-sks">SKS</th>
                    <th>Dosen Pengampu</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td>
                            <span class="badge badge-code">{{ $course->code }}</span>
                        </td>
                        <td>
                            <a href="{{ route('courses.show', $course) }}" class="table-link">
                                {{ $course->name }}
                            </a>
                        </td>
                        <td class="text-center font-semibold text-body">
                            {{ $course->sks }}
                        </td>
                        <td class="text-body font-medium">
                            {{ $course->lecturer?->name ?? 'Belum Ditugaskan' }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $course->status }}">
                                {{ ucfirst($course->status) }}
                            </span>
                        </td>
                        <td class="action-buttons-cell">
                            <div class="action-buttons-group">
                                <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary btn-sm">
                                    Detail
                                </a>
                                <a href="{{ route('courses.edit', $course) }}" class="btn btn-edit btn-sm">
                                    Edit
                                </a>
                                <form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata kuliah {{ $course->name }}?');" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-cell">
                            Belum ada data mata kuliah di database.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layout>
