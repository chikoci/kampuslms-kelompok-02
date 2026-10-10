<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Menampilkan formulir permintaan reset kata sandi.
     */
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Alias method kompatibilitas untuk showLinkRequestForm.
     */
    public function showLinkRequestForm()
    {
        return $this->showForgotForm();
    }

    /**
     * Mengirim tautan reset kata sandi.
     * Menggunakan pesan seragam agar tidak membocorkan keberadaan email di sistem.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Str::random(60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Pada sistem simulasi LMS kampus, simpan token di session untuk pengujian langsung
            session()->flash('demo_reset_url', route('password.reset', ['token' => $token, 'email' => $user->email]));
        }

        return back()->with('status', 'Jika alamat surel terdaftar, tautan pengaturan ulang kata sandi telah dikirim.');
    }

    /**
     * Alias method kompatibilitas untuk sendResetLinkEmail.
     */
    public function sendResetLinkEmail(Request $request)
    {
        return $this->sendResetLink($request);
    }

    /**
     * Menampilkan formulir pengaturan ulang kata sandi baru.
     */
    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Memproses pengaturan ulang kata sandi.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages([
                'email' => ['Token pengaturan ulang kata sandi tidak valid atau telah kedaluwarsa.'],
            ]);
        }

        // Cek kedaluwarsa token (misal 60 menit)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            throw ValidationException::withMessages([
                'email' => ['Token pengaturan ulang kata sandi telah kedaluwarsa.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return redirect()->route('login')->with('success', 'Kata sandi berhasil diatur ulang. Silakan masuk menggunakan kata sandi baru Anda.');
        }

        throw ValidationException::withMessages([
            'email' => ['Pengguna dengan surel tersebut tidak ditemukan.'],
        ]);
    }

    /**
     * Alias method kompatibilitas untuk reset.
     */
    public function reset(Request $request)
    {
        return $this->resetPassword($request);
    }
}
