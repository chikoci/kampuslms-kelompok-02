{{-- Halaman Daftar Mata Kuliah --}}
<x-layout title="Mata Kuliah - KampusLMS"
    :backUrl="route('dashboard')"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Mata Kuliah']
    ]">

    {{-- A. PAGE BANNER HEADER --}}
    <div class="page-banner-card">
        <div>
            <h1 class="page-banner-title">Mata Kuliah</h1>
            <p class="page-banner-subtitle">Kelola dan lihat seluruh mata kuliah akademik yang Anda ikuti.</p>
        </div>
        <div class="page-banner-actions-wrap" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: center;">
            @can('create', App\Models\Course::class)
                <a href="{{ route('courses.create') }}" class="btn btn-primary btn-sm page-banner-btn" style="border-radius: 8px; padding: 0.35rem 0.75rem; font-weight: 700;">
                    + Mata Kuliah
                </a>
            @endcan
            <div class="kavi-banner-composition">
                <img src="{{ asset('images/illustrations/kavi-matakuliah.png') }}" alt="Kavi Belajar Mata Kuliah" class="kavi-banner-img">
            </div>
        </div>
    </div>

    {{-- B. SEARCH & FILTER CONTAINER (HANYA DITAMPILKAN UNTUK DOSEN / ADMIN, MAHASISWA TIDAK MEMERLUKAN FILTER) --}}
    @if (auth()->user()->role !== 'mahasiswa')
        <div class="filter-container-card">
            <form action="{{ route('courses.index') }}" method="GET" class="filter-flex-form">
                {{-- Search Bar --}}
                <div class="filter-search-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode mata kuliah..." class="filter-search-input">
                </div>

                {{-- Status Filter & Filter Button (Berdampingan Kanan Kiri di Mobile) --}}
                <div class="filter-controls-group">
                    <select name="status" class="filter-select-modern">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>

                    <button type="submit" class="filter-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        <span>Filter</span>
                    </button>

                    @if(request()->hasAny(['q', 'status']))
                        <a href="{{ route('courses.index') }}" class="btn-open-class filter-btn-reset">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    @endif

    {{-- C. DAFTAR MATA KULIAH (COMPACT & JARAK RAPAT 1-KLIK MENUJU DETAIL) --}}
    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
        @forelse ($courses as $course)
            <div class="course-card-compact" onclick="window.location='{{ route('courses.show', $course) }}'" style="margin-bottom: 0;">
                <div style="flex: 1; min-width: 0; padding-right: 0.5rem;">
                    @if (auth()->user()->role === 'mahasiswa')
                        {{-- Baris 1: Nama Mata Kuliah + [Kode Mata Kuliah] Sejajar --}}
                        <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; margin-bottom: 0.2rem;">
                            <h2 class="course-compact-title" style="margin: 0; font-size: 0.925rem; font-weight: 700; color: #0f172a;">
                                {{ $course->name }}
                            </h2>
                            <span class="badge-code-modern" style="font-size: 0.72rem; padding: 0.12rem 0.45rem;">[{{ $course->code }}]</span>
                        </div>

                        {{-- Baris 2: Nama Dosen Pengampu Saja (SKS dihilangkan) --}}
                        <div style="font-size: 0.78rem; color: #64748b; display: flex; align-items: center; gap: 0.3rem;">
                            <span>Dosen Pengampu: {{ $course->lecturer?->name ?? 'Belum Ditugaskan' }}</span>
                        </div>
                    @else
                        {{-- Tampilan Dosen & Admin --}}
                        <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; margin-bottom: 0.2rem;">
                            <h2 class="course-compact-title" style="margin: 0; font-size: 0.925rem; font-weight: 700; color: #0f172a;">
                                {{ $course->name }}
                            </h2>
                            <span class="badge-code-modern" style="font-size: 0.72rem; padding: 0.12rem 0.45rem;">{{ $course->code }}</span>
                            <span class="badge-status-dot {{ $course->status }}" style="font-size: 0.68rem; padding: 0.12rem 0.45rem;">
                                <span class="dot"></span>
                                {{ ucfirst($course->status) }}
                            </span>
                        </div>
                        <div class="course-compact-meta">
                            <span style="font-size: 0.75rem; color: #64748b;">
                                Dosen: {{ $course->lecturer?->name ?? 'Belum Ditugaskan' }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Sisi Kanan: Aksi Cepat (Edit/Hapus bagi Admin/Dosen) & Indikator Chevron --}}
                <div style="display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0;">
                    @can('update', $course)
                        <a href="{{ route('courses.edit', $course) }}" 
                           class="btn-icon-action edit" 
                           title="Edit Mata Kuliah"
                           onclick="event.stopPropagation()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>
                    @endcan

                    @can('delete', $course)
                        <form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata kuliah {{ $course->name }}?');" style="margin: 0; display: inline;" onclick="event.stopPropagation()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-action delete" title="Hapus Mata Kuliah">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        @empty
            <div class="card-modern empty-state-card" style="padding: 2rem 1rem;">
                <img src="{{ asset('images/illustrations/calendar-empty.png') }}" alt="Mata Kuliah Tidak Ditemukan" class="empty-state-img">
                <p class="empty-state-text">Belum ada mata kuliah yang sesuai dengan pencarian atau filter Anda.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination Wrapper --}}
    @if ($courses->hasPages())
        <div class="pagination-wrapper" style="margin-top: 1.5rem;">
            {{ $courses->links() }}
        </div>
    @endif

</x-layout>
