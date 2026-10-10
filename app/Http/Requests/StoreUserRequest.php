<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki hak akses untuk request ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    /**
     * Aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', 'in:admin,dosen,mahasiswa'],
            'nim_nip'  => ['nullable', 'string', 'max:50', 'unique:users,nim_nip'],
        ];
    }

    /**
     * Pesan kustom untuk aturan validasi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'Nama lengkap pengguna wajib diisi.',
            'name.max'          => 'Nama pengguna maksimal 255 karakter.',
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Alamat email ini sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min'      => 'Kata sandi minimal 6 karakter.',
            'role.required'     => 'Peran (role) pengguna wajib dipilih.',
            'role.in'           => 'Peran yang dipilih tidak valid (pilihan: admin, dosen, mahasiswa).',
            'nim_nip.unique'    => 'NIM/NIP ini sudah terdaftar untuk pengguna lain.',
            'nim_nip.max'       => 'NIM/NIP maksimal 50 karakter.',
        ];
    }
}
