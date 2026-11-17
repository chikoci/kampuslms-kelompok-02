{{-- Halaman Daftar Mata Kuliah Mahasiswa --}}
<x-layout title="Mata Kuliah - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Mata Kuliah']
]">

    {{-- A. PAGE BANNER HEADER DENGAN ILUSTRASI BERUANG BELAJAR --}}
    <div class="page-banner-card">
        <div>
            <h1 class="page-banner-title">Mata Kuliah</h1>
            <p class="page-banner-subtitle">Kelola dan lihat seluruh mata kuliah akademik yang Anda ikuti.</p>
        </div>
        <div class="page-banner-actions-wrap" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: center;">
            @can('create', App\Models\Course::class)
                <a href="{{ route('courses.create') }}" class="btn btn-primary btn-sm page-banner-btn" style="border-radius: 9999px; padding: 0.5rem 1.25rem;">
                    + Tambah Mata Kuliah
                </a>
            @endcan
            <div class="kavi-banner-composition">
                <div class="kavi-banner-blob"></div>
                <div class="kavi-sparkle s1" style="top: 8px; left: 10px; font-size: 0.85rem;">&#10022;</div>
                <div class="kavi-sparkle s2" style="top: 20px; right: 8px; font-size: 0.8rem;">&#10022;</div>
                <img src="{{ asset('images/illustrations/kavi-matakuliah.png') }}" alt="Kavi Belajar Mata Kuliah" class="kavi-banner-img">
                <div class="kavi-pedestal-shadow" style="width: 100px; height: 12px; bottom: -2px;"></div>
            </div>
        </div>
    </div>

    {{-- B. SEARCH & FILTER CONTAINER --}}
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

    {{-- C. DAFTAR MATA KULIAH (COMPACT & 1-KLIK LANGSUNG MENUJU DETAIL) --}}
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        @forelse ($courses as $course)
            <div class="course-card-compact" onclick="window.location='{{ route('courses.show', $course) }}'">
                <div style="flex: 1; min-width: 0; padding-right: 0.5rem;">
                    {{-- Nama Mata Kuliah --}}
                    <h2 class="course-compact-title">
                        {{ $course->name }}
                    </h2>

                    {{-- Baris Metadata Ringkas & Rapi --}}
                    <div class="course-compact-meta">
                        <span class="badge-code-modern" style="font-size: 0.7rem; padding: 0.12rem 0.45rem;">{{ $course->code }}</span>
                        <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 0.68rem; font-weight: 700; padding: 0.12rem 0.45rem; border-radius: 6px;">
                            {{ $course->sks }} SKS
                        </span>
                        <span class="badge-status-dot {{ $course->status }}" style="font-size: 0.68rem; padding: 0.12rem 0.45rem;">
                            <span class="dot"></span>
                            {{ ucfirst($course->status) }}
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            {{ $course->lecturer?->name ?? 'Belum Ditugaskan' }}
                        </span>
                    </div>
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

                    {{-- Chevron 1-Klik Buka Kelas --}}
                    <div class="course-chevron">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
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
