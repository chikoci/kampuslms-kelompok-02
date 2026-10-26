<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Menampilkan daftar seluruh notifikasi milik pengguna yang sedang login.
     */
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(15);
        $unreadCount = auth()->user()->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mengembalikan jumlah notifikasi belum dibaca dalam format JSON (untuk polling navbar).
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Menandai satu notifikasi sebagai sudah dibaca.
     * Menerapkan pencegahan IDOR ketat: hanya mencari dari notifikasi milik pengguna sendiri.
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back()->with('success', 'Notifikasi berhasil ditandai sebagai sudah dibaca.');
    }

    /**
     * Menandai seluruh notifikasi yang belum dibaca sebagai sudah dibaca.
     */
    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Seluruh notifikasi berhasil ditandai sebagai sudah dibaca.');
    }

    /**
     * Menghapus satu notifikasi.
     * Menerapkan pencegahan IDOR ketat.
     */
    public function destroy(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->delete();

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }
}
