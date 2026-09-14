<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Menampilkan daftar seluruh pengguna.
     */
    public function index()
    {
        $users = User::latest()->get();

        return view('users.index', compact('users'));
    }

    /**
     * Menampilkan formulir penambahan pengguna baru.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Menyimpan data pengguna baru ke database.
     * PENTING: Kolom 'role' TIDAK ada di $fillable dan diisi secara eksplisit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,dosen,mahasiswa'],
            'nim_nip' => ['nullable', 'string', 'unique:users,nim_nip'],
        ]);

        // Mengisi atribut aman yang ada di $fillable (name, email, password, nim_nip)
        $user = new User($validated);

        // Penugasan eksplisit untuk role (role dikeluarkan dari $fillable demi keamanan)
        $user->role = $validated['role'];
        $user->save();

        return redirect()->route('users.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail satu pengguna beserta relasi terkaitnya.
     */
    public function show(User $user)
    {
        $user->load(['taughtCourses', 'courses']);

        return view('users.show', compact('user'));
    }

    /**
     * Menampilkan formulir edit data pengguna.
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Memperbarui data pengguna di database.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:admin,dosen,mahasiswa'],
            'nim_nip' => ['nullable', 'string', 'unique:users,nim_nip,' . $user->id],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->nim_nip = $validated['nim_nip'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        // Penugasan eksplisit untuk role
        $user->role = $validated['role'];
        $user->save();

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna (Soft Delete).
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
