{{-- Halaman Daftar Pengguna --}}
<x-layout title="Daftar Pengguna - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Pengguna']
]">

    <div class="header-actions-row">
        <div>
            <h1 class="page-title">Manajemen Pengguna</h1>
            <p class="page-subtitle">Daftar akun administrator, dosen, dan mahasiswa di KampusLMS</p>
        </div>
        @can('create', App\Models\User::class)
            <a href="{{ route('register') }}" class="btn btn-primary">
                + Tambah Pengguna Baru
            </a>
        @endcan
    </div>

    {{-- Form Pencarian & Filter Peran (Role) --}}
    <div class="filter-card">
        <form action="{{ route('users.index') }}" method="GET" class="filter-form">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama, email, atau NIM/NIP..." class="form-control filter-input">
            
            <div class="filter-controls-group">
                <select name="role" class="form-select filter-select">
                    <option value="">-- Semua Role --</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="dosen" {{ request('role') === 'dosen' ? 'selected' : '' }}>Dosen</option>
                    <option value="mahasiswa" {{ request('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm filter-btn-primary">Filter</button>
                @if(request()->hasAny(['q', 'role']))
                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm filter-btn-reset">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel daftar pengguna --}}
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th class="col-role">Role</th>
                    <th class="col-nim">NIM / NIP</th>
                    <th class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="font-semibold text-body">
                            <a href="{{ route('users.show', $user) }}" class="table-link">
                                {{ $user->name }}
                            </a>
                        </td>
                        <td class="text-body">
                            {{ $user->email }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $user->role }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="text-muted font-mono font-semibold">
                            {{ $user->nim_nip ?? '-' }}
                        </td>
                        <td class="action-buttons-cell">
                            <div class="action-buttons-group">
                                <a href="{{ route('users.show', $user) }}" class="btn btn-secondary btn-sm">
                                    Detail
                                </a>
                                @can('update', $user)
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-edit btn-sm">
                                        Edit
                                    </a>
                                @endcan
                                @can('delete', $user)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $user->name }}?');" class="inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Hapus
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-cell">
                            Belum ada data pengguna yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Link Pagination --}}
    @if ($users->hasPages())
        <div class="pagination-wrapper">
            {{ $users->links() }}
        </div>
    @endif

</x-layout>
