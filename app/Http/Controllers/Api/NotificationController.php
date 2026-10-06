<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Menampilkan daftar notifikasi pengguna yang sedang login.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return NotificationResource::collection($notifications);
    }

    /**
     * Menandai notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ], 403));
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notifikasi telah ditandai sebagai sudah dibaca.',
            'data' => new NotificationResource($notification),
        ]);
    }
}
