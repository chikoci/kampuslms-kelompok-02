{{-- Halaman Daftar Pengguna --}}
<x-layout title="Daftar Pengguna - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Pengguna']
]">

    {{-- A. PAGE BANNER HEADER DENGAN ILUSTRASI MASKOT KAVI --}}
    <div class="page-banner-card">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                <span class="badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 9999px;">
                    Administrator
                </span>
            </div>
            <h1 class="page-banner-title">Manajemen Pengguna</h1>
            <p class="page-banner-subtitle">Kelola dan pantau seluruh akun civitas akademika (Mahasiswa, Dosen, Administrator) di KampusLMS.</p>
        </div>
        <div class="page-banner-actions-wrap" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: center;">
            @can('create', App\Models\User::class)
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm page-banner-btn" style="border-radius: 9999px; padding: 0.55rem 1.35rem; display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; text-decoration: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="22" y1="11" x2="16" y2="11"/>
                    </svg>
                    + Tambah Pengguna
                </a>
            @endcan
            <div class="kavi-banner-composition">
                <div class="kavi-banner-blob"></div>
                <div class="kavi-sparkle s1" style="top: 8px; left: 10px; font-size: 0.85rem;">&#10022;</div>
                <div class="kavi-sparkle s2" style="top: 20px; right: 8px; font-size: 0.8rem;">&#10022;</div>
                <img src="{{ asset('images/illustrations/kavi-dashboard.png') }}" alt="Kavi Manajemen Pengguna" class="kavi-banner-img">
                <div class="kavi-pedestal-shadow" style="width: 100px; height: 12px; bottom: -2px;"></div>
            </div>
        </div>
    </div>

    {{-- B. SEARCH & FILTER CONTAINER --}}
    <div class="filter-container-card">
        <form action="{{ route('users.index') }}" method="GET" class="filter-flex-form">
            {{-- Search Bar --}}
            <div class="filter-search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" 
                       name="q" 
                       value="{{ request('q') }}" 
                       placeholder="Cari nama, email, atau NIM/NIP pengguna..." 
                       class="filter-search-input">
            </div>

            <div class="filter-controls-group">
                {{-- Filter Role --}}
                <div class="filter-select-box">
                    <select name="role" class="filter-select-input">
                        <option value="">Semua Peran (Role)</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                        <option value="dosen" {{ request('role') === 'dosen' ? 'selected' : '' }}>Dosen Pengampu</option>
                        <option value="mahasiswa" {{ request('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                    </select>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); pointer-events: none;">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </div>

                <button type="submit" class="filter-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    <span>Filter</span>
                </button>

                @if(request()->hasAny(['q', 'role']))
                    <a href="{{ route('users.index') }}" class="filter-btn-reset">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- C. TABEL DAFTAR PENGGUNA (DESKTOP & MOBILE RESPONSIVE) --}}
    <div class="card-modern" style="padding: 0; overflow: hidden; margin-top: 1.25rem;">
        
        {{-- 1. Desktop Table View (> 767px) --}}
        <div class="users-desktop-table" style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 1rem 1.25rem; font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; text-align: left;">Pengguna</th>
                        <th style="padding: 1rem 1.25rem; font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; text-align: left;">Email Kampus</th>
                        <th style="padding: 1rem 1.25rem; font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; text-align: center;">Peran (Role)</th>
                        <th style="padding: 1rem 1.25rem; font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; text-align: center;">NIM / NIP</th>
                        <th style="padding: 1rem 1.25rem; font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                            <td style="padding: 1rem 1.25rem;">
                                @php
                                    $uNames = explode(' ', $user->name);
                                    $uInitials = count($uNames) >= 2 
                                        ? strtoupper(substr($uNames[0], 0, 1) . substr($uNames[1], 0, 1))
                                        : strtoupper(substr($user->name, 0, 2));
                                    $uBg = $user->role === 'admin' ? '#fee2e2' : ($user->role === 'dosen' ? '#dbeafe' : '#d1fae5');
                                    $uColor = $user->role === 'admin' ? '#ef4444' : ($user->role === 'dosen' ? '#2563eb' : '#10b981');
                                @endphp
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 38px; height: 38px; border-radius: 12px; background: {{ $uBg }}; color: {{ $uColor }}; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 800; flex-shrink: 0;">
                                        {{ $uInitials }}
                                    </div>
                                    <div>
                                        <a href="{{ route('users.show', $user) }}" style="font-weight: 700; color: #0f172a; text-decoration: none; font-size: 0.925rem; display: block; line-height: 1.2;">
                                            {{ $user->name }}
                                        </a>
                                        <span style="font-size: 0.75rem; color: #94a3b8;">Terdaftar: {{ $user->created_at?->format('d M Y') ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem 1.25rem; color: #475569; font-size: 0.875rem;">
                                {{ $user->email }}
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: center;">
                                <span class="badge {{ $user->role === 'admin' ? 'badge-danger' : ($user->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.75rem; text-transform: capitalize; padding: 0.25rem 0.65rem;">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: center; font-family: monospace; font-weight: 600; color: #334155; font-size: 0.85rem;">
                                {{ $user->nim_nip ?? '-' }}
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 0.4rem; justify-content: flex-end;">
                                    <a href="{{ route('users.show', $user) }}" class="btn-open-class" style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">
                                        Detail
                                    </a>
                                    @can('update', $user)
                                        <a href="{{ route('users.edit', $user) }}" class="btn-open-class" style="padding: 0.35rem 0.75rem; font-size: 0.775rem; color: #2563eb; border-color: #bfdbfe; background: #eff6ff;">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('delete', $user)
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $user->name }}?');" style="margin: 0; display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-open-class" style="padding: 0.35rem 0.75rem; font-size: 0.775rem; color: #dc2626; border-color: #fecaca; background: #fef2f2;">
                                                Hapus
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state-card" style="padding: 3rem 1.5rem;">
                                <img src="{{ asset('images/illustrations/calendar-empty.png') }}" alt="Pengguna Tidak Ditemukan" class="empty-state-img">
                                <p class="empty-state-text">Belum ada data pengguna yang sesuai dengan filter pencarian Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 2. Mobile Cards View (<= 767px) --}}
        <div class="users-mobile-cards" style="display: none; flex-direction: column;">
            @forelse ($users as $user)
                @php
                    $uNames = explode(' ', $user->name);
                    $uInitials = count($uNames) >= 2 
                        ? strtoupper(substr($uNames[0], 0, 1) . substr($uNames[1], 0, 1))
                        : strtoupper(substr($user->name, 0, 2));
                    $uBg = $user->role === 'admin' ? '#fee2e2' : ($user->role === 'dosen' ? '#dbeafe' : '#d1fae5');
                    $uColor = $user->role === 'admin' ? '#ef4444' : ($user->role === 'dosen' ? '#2563eb' : '#10b981');
                @endphp
                <div class="user-card-item" style="padding: 0.75rem 0.85rem; border-bottom: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 0.45rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.6rem; min-width: 0; flex: 1;">
                            <div style="width: 34px; height: 34px; border-radius: 10px; background: {{ $uBg }}; color: {{ $uColor }}; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 800; flex-shrink: 0;">
                                {{ $uInitials }}
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <a href="{{ route('users.show', $user) }}" style="font-weight: 700; color: #0f172a; text-decoration: none; font-size: 0.9rem; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; line-height: 1.2;">
                                    {{ $user->name }}
                                </a>
                                <div style="font-size: 0.72rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $user->email }}
                                </div>
                            </div>
                        </div>
                        <span class="badge {{ $user->role === 'admin' ? 'badge-danger' : ($user->role === 'dosen' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.68rem; text-transform: capitalize; padding: 0.15rem 0.45rem; border-radius: 6px; flex-shrink: 0;">
                            {{ $user->role }}
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #64748b; padding-top: 0.35rem; border-top: 1px dashed #f1f5f9;">
                        <span>NIM/NIP: <strong style="color: #334155; font-family: monospace;">{{ $user->nim_nip ?? '-' }}</strong></span>
                        <div style="display: flex; gap: 0.35rem; align-items: center;">
                            <a href="{{ route('users.show', $user) }}" class="btn-open-class" style="padding: 0.25rem 0.6rem; font-size: 0.72rem;">Detail</a>
                            @can('update', $user)
                                <a href="{{ route('users.edit', $user) }}" class="btn-open-class" style="padding: 0.25rem 0.6rem; font-size: 0.72rem; color: #2563eb; border-color: #bfdbfe; background: #eff6ff;">Edit</a>
                            @endcan
                            @can('delete', $user)
                                <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $user->name }}?');" style="margin: 0; display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-open-class" style="padding: 0.25rem 0.6rem; font-size: 0.72rem; color: #dc2626; border-color: #fecaca; background: #fef2f2;">Hapus</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state-card" style="padding: 2.5rem 1.5rem; text-align: center;">
                    <img src="{{ asset('images/illustrations/calendar-empty.png') }}" alt="Pengguna Tidak Ditemukan" class="empty-state-img">
                    <p class="empty-state-text">Belum ada data pengguna yang sesuai dengan filter pencarian Anda.</p>
                </div>
            @endforelse
        </div>

    </div>

    {{-- Link Pagination --}}
    @if ($users->hasPages())
        <div class="pagination-wrapper">
            {{ $users->links() }}
        </div>
    @endif

</x-layout>
