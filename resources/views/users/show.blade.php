<x-layout title="Detail: {{ $user->name }}">

    <div class="header-actions-row">
        <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Daftar
        </a>
        <div class="header-actions-group">
            @can('update', $user)
                <a href="{{ route('users.edit', $user) }}" class="btn btn-edit btn-sm">
                    Edit Pengguna
                </a>
            @endcan
            @can('delete', $user)
                <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?');" class="inline-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        Hapus
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="page-header">
        <div class="title-badge-group">
            <h1 class="page-title-lg">{{ $user->name }}</h1>
            <span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
        </div>
    </div>

    {{-- Grid Kartu Metrik --}}
    <div class="metric-grid">
        <div class="metric-card">
            <p class="metric-label">Alamat Email</p>
            <p class="metric-value metric-value-email">{{ $user->email }}</p>
            <p class="metric-sub">Akun Terdaftar</p>
        </div>

        <div class="metric-card">
            <p class="metric-label">NIM / NIP</p>
            <p class="metric-value font-mono">{{ $user->nim_nip ?? '-' }}</p>
            <p class="metric-sub">Nomor Identitas</p>
        </div>

        <div class="metric-card">
            <p class="metric-label">Terdaftar Sejak</p>
            <p class="metric-value font-medium">{{ $user->created_at?->format('d M Y') ?? '-' }}</p>
            <p class="metric-sub">Status: Aktif</p>
        </div>
    </div>

    @if ($user->role === 'dosen')
        <div class="section-block">
            <div class="section-header">
                <h2 class="section-title">Mata Kuliah yang Diampu</h2>
                <span class="section-counter">Total: {{ $user->taughtCourses->count() }} Mata Kuliah</span>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-code">Kode</th>
                            <th>Nama Mata Kuliah</th>
                            <th class="col-sks">SKS</th>
                            <th class="col-status">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($user->taughtCourses as $course)
                            <tr>
                                <td><span class="badge badge-code">{{ $course->code }}</span></td>
                                <td class="font-semibold text-body">
                                    <a href="{{ route('courses.show', $course) }}" class="table-link">
                                        {{ $course->name }}
                                    </a>
                                </td>
                                <td class="text-center font-semibold text-body">{{ $course->sks }}</td>
                                <td><span class="badge badge-{{ $course->status }}">{{ ucfirst($course->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-cell-sm">
                                    Dosen ini belum mengampu mata kuliah.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($user->role === 'mahasiswa')
        <div class="section-block">
            <div class="section-header">
                <h2 class="section-title">Mata Kuliah yang Diikuti</h2>
                <span class="section-counter">Total: {{ $user->courses->count() }} Mata Kuliah</span>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-code">Kode</th>
                            <th>Nama Mata Kuliah</th>
                            <th>Dosen Pengampu</th>
                            <th class="col-sks">SKS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($user->courses as $course)
                            <tr>
                                <td><span class="badge badge-code">{{ $course->code }}</span></td>
                                <td class="font-semibold text-body">
                                    <a href="{{ route('courses.show', $course) }}" class="table-link">
                                        {{ $course->name }}
                                    </a>
                                </td>
                                <td class="text-body font-medium">{{ $course->lecturer?->name ?? '-' }}</td>
                                <td class="text-center font-semibold text-body">{{ $course->sks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-cell-sm">
                                    Mahasiswa ini belum terdaftar di mata kuliah mana pun.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</x-layout>
