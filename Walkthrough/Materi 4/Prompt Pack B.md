# Implementasi Form Request Laravel 12 (Minggu 4 - Prompt Pack B)

## 1. Kode Kelas Form Request

### A. `StoreCourseRequest`
File: `app/Http/Requests/StoreCourseRequest.php`

Dibuat dengan perintah Artisan:
```bash
php artisan make:request StoreCourseRequest
```

Kode lengkap:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki hak akses untuk request ini.
     */
    public function authorize(): bool
    {
        // TODO: Minggu 7 diganti dengan pengecekan otorisasi Policy sungguhan
        return true;
    }

    /**
     * Aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:20', 'unique:courses,code'],
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
```

---

### B. `UpdateCourseRequest`
File: `app/Http/Requests/UpdateCourseRequest.php`

Dibuat dengan perintah Artisan:
```bash
php artisan make:request UpdateCourseRequest
```

Kode lengkap:
```php
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
        // TODO: Minggu 7 diganti dengan pengecekan otorisasi Policy sungguhan
        return true;
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
```

---

## 2. Cara Pemanggilan di `CourseController`

Import kelas Form Request di bagian atas `CourseController`:
```php
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
```

Implementasi method `store()` dan `update()`:
```php
/**
 * Menyimpan data mata kuliah baru ke database.
 */
public function store(StoreCourseRequest $request)
{
    // Hanya mengambil field yang lolos validasi rules
    $course = Course::create($request->validated());

    return redirect()
        ->route('courses.index')
        ->with('success', 'Mata kuliah berhasil ditambahkan.');
}

/**
 * Memperbarui data mata kuliah di database.
 */
public function update(UpdateCourseRequest $request, Course $course)
{
    // Hanya mengambil field yang lolos validasi rules
    $course->update($request->validated());

    return redirect()
        ->route('courses.index')
        ->with('success', 'Mata kuliah berhasil diperbarui.');
}
```

---

## 3. Kenapa Wajib `$request->validated()` dan Bukan `$request->all()`?

1. **Bahaya `$request->all()`:**
   - `$request->all()` mengambil **seluruh parameter mentah** yang dikirim pengguna via HTTP (baik dari form browser, inspect element, maupun request `curl`/Postman).
   - Jika penyerang menyisipkan field liar tambahan (misalnya `is_admin=1` atau `id=123`), field tersebut akan ikut masuk ke dalam array yang dikirim ke database.
   - Jika developer sedikit saja lengah pada properti `$fillable`, field berbahaya tersebut langsung tertulis ke database (kerentanan Mass Assignment).

2. **Keamanan `$request->validated()`:**
   - `$request->validated()` bertindak sebagai filter keamanan ketat: fungsi ini **hanya mengembalikan field yang didefinisikan secara eksplisit di method `rules()`**.
   - Input liar tambahan yang tidak terdaftar di `rules()` akan langsung dibuang secara otomatis sebelum data menyentuh database.
