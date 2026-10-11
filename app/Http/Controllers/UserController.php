<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Menampilkan daftar seluruh pengguna.
     * Hanya Admin yang berhak mengakses direktori pengguna.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('q'), fn ($query) =>
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('email', 'like', '%' . $request->q . '%')
                      ->orWhere('nim_nip', 'like', '%' . $request->q . '%');
                }))
            ->when($request->filled('role'), fn ($query) =>
                $query->where('role', $request->role))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Menampilkan formulir penambahan pengguna baru.
     */
    public function create()
    {
        Gate::authorize('create', User::class);

        return view('auth.register');
    }

    /**
     * Menyimpan data pengguna baru ke database.
     */
    public function store(StoreUserRequest $request)
    {
        Gate::authorize('create', User::class);

        $validated = $request->validated();

        $user = new User($validated);
        $user->role = $validated['role'];
        $user->save();

        return redirect()->route('users.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail satu pengguna.
     */
    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user->load(['taughtCourses', 'courses']);

        return view('users.show', compact('user'));
    }

    /**
     * Menampilkan formulir edit data pengguna.
     */
    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    /**
     * Memperbarui data pengguna di database.
     * Pencegahan Privilege Escalation: Kolom role hanya diperbarui jika lolos policy updateRole.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->nim_nip = $validated['nim_nip'] ?? null;

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        if (! empty($validated['role']) && Gate::allows('updateRole', $user)) {
            $user->role = $validated['role'];
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna (Soft Delete).
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
