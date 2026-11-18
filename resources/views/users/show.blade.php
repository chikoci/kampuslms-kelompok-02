<x-layout title="Detail: {{ $user->name }} - KampusLMS"
    :backUrl="route('users.index')"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Pengguna', 'url' => route('users.index')],
        ['label' => $user->name]
    ]">

    {{-- Header Banner Profil Pengguna --}}
    <div class="card-modern" style="margin-bottom: 1.5rem;">
        {{-- Toolbar Atas: Aksi Edit/Hapus Pengguna --}}
        <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 0.75rem;">
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                @can('update', $user)
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary btn-sm" style="background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit Pengguna
                    </a>
                @endcan
                @can('delete', $user)
                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?');" style="margin: 0; display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-secondary btn-sm" style="background: #fef2f2; color: #dc2626; border-color: #fecaca; font-weight: 700;">
                            Hapus
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Info Profil Utama Pengguna --}}
        <div style="display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap;">
            @php
                $names = explode(' ', $user->name);
                $initials = count($names) >= 2 
                    ? strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1))
                    : strtoupper(substr($user->name, 0, 2));
                $roleColor = $user->role === 'admin' ? '#ef4444' : ($user->role === 'dosen' ? '#2563eb' : '#10b981');
                $roleBg = $user->role === 'admin' ? '#fee2e2' : ($user->role === 'dosen' ? '#dbeafe' : '#d1fae5');
            @endphp
            <div style="width: 64px; height: 64px; border-radius: 20px; background: {{ $roleBg }}; color: {{ $roleColor }}; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                {{ $initials }}
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.35rem; flex-wrap: wrap;">
                    <h1 style="font-size: 1.65rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">{{ $user->name }}</h1>
                    <span class="badge {{ $user->role === 'admin' ? 'badge-danger' : ($user->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.75rem; text-transform: capitalize; padding: 0.3rem 0.75rem;">
                        {{ $user->role }}
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem; color: #64748b; font-size: 0.875rem; flex-wrap: wrap;">
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                        {{ $user->email }}
                    </span>
                    @if($user->nim_nip)
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-family: monospace; font-weight: 600;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="3" rx="2"/>
                                <path d="M7 7h10M7 12h10M7 17h10"/>
                            </svg>
                            ID: {{ $user->nim_nip }}
                        </span>
                    @endif
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Terdaftar: {{ $user->created_at?->format('d M Y') ?? '-' }}
                    </span>
                </div>
            </div>
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
