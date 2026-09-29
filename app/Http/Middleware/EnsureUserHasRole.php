<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Menangani request masuk dan memverifikasi peran pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles Peran yang diizinkan (misal: 'admin', 'dosen')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Kasus Pengguna Belum Login
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated: Anda harus login terlebih dahulu.'
                ], 401);
            }

            abort(401, 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

        // 2. Kasus Pengguna Sudah Login, tapi Perannya Salah
        if (! in_array($request->user()->role, $roles, true)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses yang diizinkan untuk membuka halaman ini.');
        }

        // 3. Pengguna Sah & Peran Cocok
        return $next($request);
    }
}
