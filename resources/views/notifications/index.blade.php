<x-layout>
    <x-slot:title>Daftar Notifikasi - KampusLMS</x-slot:title>

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
        <div class="card" style="text-align: center; padding: 3.5rem 1.5rem; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.6;">🔕</div>
            <h3 style="margin-bottom: 0.5rem; color: #1e293b;">Belum Ada Notifikasi</h3>
            <p class="text-muted" style="max-width: 420px; margin: 0 auto;">
                Saat dosen menerbitkan tugas baru atau memberikan penilaian, pemberitahuan akan otomatis muncul di sini.
            </p>
        </div>
    @else
        <div class="notification-list" style="display: flex; flex-direction: column; gap: 0.85rem;">
            @foreach ($notifications as $notification)
                @php
                    $isUnread = is_null($notification->read_at);
                    $data = $notification->data;
                    $type = $data['type'] ?? '';
                    $isAssignment = ($type === 'assignment_published');
                @endphp

                <div class="card" style="padding: 1.25rem 1.5rem; border-radius: 10px; border: 1px solid {{ $isUnread ? '#93c5fd' : '#e2e8f0' }}; background: {{ $isUnread ? '#f0f9ff' : '#ffffff' }}; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; transition: all 0.2s ease;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem; flex: 1; min-width: 260px;">
                        <div style="font-size: 1.5rem; line-height: 1; padding: 0.6rem; border-radius: 8px; background: {{ $isAssignment ? '#e0f2fe' : '#fef3c7' }};">
                            {{ $isAssignment ? '📝' : '🏆' }}
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <span class="badge {{ $isAssignment ? 'badge-primary' : 'badge-warning' }}" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">
                                    {{ $isAssignment ? 'Tugas Baru' : 'Nilai Keluar' }}
                                </span>
                                <span style="font-size: 0.8rem; color: #64748b;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                                @if ($isUnread)
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2563eb;" title="Belum dibaca"></span>
                                @endif
                            </div>

                            <h4 style="margin: 0 0 0.35rem 0; font-size: 1rem; color: #0f172a;">
                                @if ($isAssignment)
                                    Tugas baru: <a href="{{ $data['url'] ?? '#' }}" style="color: #0284c7; text-decoration: none; font-weight: 600;">{{ $data['title'] ?? 'Tugas Perkuliahan' }}</a>
                                @else
                                    Penilaian selesai: <a href="{{ $data['url'] ?? '#' }}" style="color: #0284c7; text-decoration: none; font-weight: 600;">{{ $data['assignment_title'] ?? 'Tugas Perkuliahan' }}</a>
                                @endif
                            </h4>

                            <p style="margin: 0; font-size: 0.85rem; color: #475569;">
                                Mata Kuliah: <strong>{{ $data['course_name'] ?? '-' }}</strong>
                                @if (!empty($data['due_at']))
                                    | Tenggat: {{ \Carbon\Carbon::parse($data['due_at'])->translatedFormat('d F Y, H:i') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        @if (!empty($data['url']))
                            <a href="{{ $data['url'] }}" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                                Buka Tugas
                            </a>
                        @endif

                        @if ($isUnread)
                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.65rem;" title="Tandai sudah dibaca">
                                    ✓ Dibaca
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus notifikasi ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="font-size: 0.8rem; padding: 0.35rem 0.65rem; background: transparent; color: #ef4444; border: 1px solid #fecaca;" title="Hapus notifikasi">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach

            <div style="margin-top: 1.5rem;">
                {{ $notifications->links() }}
            </div>
        </div>
    @endif
</x-layout>
