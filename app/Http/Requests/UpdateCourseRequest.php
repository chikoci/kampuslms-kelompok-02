<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki hak akses untuk request ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('course')) ?? false;
    }

    /**
     * Aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('courses', 'code')->ignore($this->course),
            ],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'sks'         => ['required', 'integer', 'between:1,6'],
            'lecturer_id' => ['required', 'integer', 'exists:users,id'],
            'status'      => ['required', 'in:draft,active,archived'],
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
            'code.required'        => 'Kode mata kuliah wajib diisi.',
            'code.unique'          => 'Kode mata kuliah ini sudah terdaftar di sistem.',
            'code.max'             => 'Kode mata kuliah tidak boleh lebih dari 20 karakter.',
            'name.required'        => 'Nama mata kuliah wajib diisi.',
            'name.max'             => 'Nama mata kuliah tidak boleh lebih dari 150 karakter.',
            'sks.required'         => 'Beban SKS wajib diisi.',
            'sks.integer'          => 'Beban SKS harus berupa angka bulat.',
            'sks.between'          => 'Beban SKS harus antara 1 sampai 6.',
            'lecturer_id.required' => 'Dosen pengampu wajib dipilih.',
            'lecturer_id.exists'   => 'Dosen pengampu yang dipilih tidak valid atau tidak terdaftar.',
            'status.required'      => 'Status mata kuliah wajib dipilih.',
            'status.in'            => 'Status yang dipilih tidak valid (pilihan: draft, active, archived).',
        ];
    }
}
