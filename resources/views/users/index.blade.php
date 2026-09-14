{{-- Halaman Daftar Pengguna --}}
<x-layout title="Daftar Pengguna">

    <div class="header-actions-row">
        <div>
            <h1 class="page-title">Manajemen Pengguna</h1>
            <p class="page-subtitle">Daftar akun administrator, dosen, dan mahasiswa di KampusLMS</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            + Tambah Pengguna Baru
        </a>
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
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-edit btn-sm">
                                    Edit
                                </a>
                                <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $user->name }}?');" class="inline-form">
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
                        <td colspan="5" class="empty-cell">
                            Belum ada pengguna terdaftar di database.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layout>
