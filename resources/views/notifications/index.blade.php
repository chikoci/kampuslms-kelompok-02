<x-layout title="Daftar Notifikasi - KampusLMS" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Pusat Pemberitahuan']
]">

    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1 class="page-title" style="margin-bottom: 0.25rem;">Pusat Pemberitahuan</h1>
            <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
                Notifikasi tugas baru dan pembaruan nilai perkuliahan Anda.
                @if ($unreadCount > 0)
                    <span class="badge badge-primary" style="margin-left: 0.5rem; font-size: 0.75rem;">{{ $unreadCount }} Belum Dibaca</span>
                @endif
            </p>
        </div>

        @if ($unreadCount > 0)
            <div>
                <form action="{{ route('notifications.readAll') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.45rem 0.9rem;">
                        Tandai Semua Sudah Dibaca
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div class="card" style="text-align: center; padding: 2.5rem 1.5rem; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.6;">🔕</div>
            <h3 style="margin-bottom: 0.35rem; color: #1e293b; font-size: 1.1rem;">Belum Ada Notifikasi</h3>
            <p class="text-muted" style="max-width: 420px; margin: 0 auto; font-size: 0.875rem;">
                Saat dosen menerbitkan tugas baru atau memberikan penilaian, pemberitahuan akan otomatis muncul di sini.
            </p>
        </div>
    @else
        <div class="notification-list" style="display: flex; flex-direction: column; gap: 0.5rem;">
            @foreach ($notifications as $notification)
                @php
                    $isUnread = is_null($notification->read_at);
                    $data = $notification->data;
                    $type = $data['type'] ?? '';
                    $isAssignment = ($type === 'assignment_published');
                    $targetUrl = $data['url'] ?? '#';
                @endphp

                <div class="course-card-compact" style="padding: 0.75rem 1rem; border-color: {{ $isUnread ? '#93c5fd' : '#e2e8f0' }}; background: {{ $isUnread ? '#f0f9ff' : '#ffffff' }};" onclick="window.location='{{ $targetUrl }}'">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 0;">
                        <div style="font-size: 1.25rem; line-height: 1; padding: 0.45rem; border-radius: 8px; background: {{ $isAssignment ? '#e0f2fe' : '#fef3c7' }}; flex-shrink: 0;">
                            {{ $isAssignment ? '📝' : '🏆' }}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.2rem; flex-wrap: wrap;">
                                <span class="badge {{ $isAssignment ? 'badge-primary' : 'badge-warning' }}" style="font-size: 0.65rem; padding: 0.1rem 0.4rem;">
                                    {{ $isAssignment ? 'Tugas Baru' : 'Nilai Keluar' }}
                                </span>
                                <span style="font-size: 0.72rem; color: #64748b;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                                @if ($isUnread)
                                    <span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #2563eb;" title="Belum dibaca"></span>
                                @endif
                            </div>

                            <h4 class="course-compact-title" style="margin: 0; font-size: 0.875rem;">
                                {{ $isAssignment ? ($data['title'] ?? 'Tugas Perkuliahan') : ($data['assignment_title'] ?? 'Tugas Perkuliahan') }}
                            </h4>

                            <p style="margin: 0.15rem 0 0 0; font-size: 0.75rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $data['course_name'] ?? '-' }}
                                @if (!empty($data['due_at']))
                                    &bull; Tenggat: {{ \Carbon\Carbon::parse($data['due_at'])->translatedFormat('d M Y, H:i') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0; margin-left: 0.5rem;">
                        @if ($isUnread)
                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST" style="margin: 0;" onclick="event.stopPropagation()">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.25rem 0.5rem;" title="Tandai sudah dibaca">
                                    ✓ Dibaca
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus notifikasi ini?');" onclick="event.stopPropagation()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-action delete" style="width: 26px; height: 26px;" title="Hapus notifikasi">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach

            <div style="margin-top: 1.25rem;">
                {{ $notifications->links() }}
            </div>
        </div>
    @endif
</x-layout>
