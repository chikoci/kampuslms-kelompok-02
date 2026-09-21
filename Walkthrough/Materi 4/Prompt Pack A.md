# Rancangan Aturan Validasi Mata Kuliah (Minggu 4 - Prompt Pack A)

## Konteks & Skema
Sesuai spesifikasi proyek KampusLMS (Bagian 4.1 & 4.2), tabel `courses` memiliki skema berikut:
- `id` (bigint, PK)
- `code` (string max 20, unique)
- `name` (string max 150)
- `description` (text, nullable)
- `sks` (tinyint / integer, 1-6)
- `lecturer_id` (bigint FK ke `users.id`)
- `status` (enum: `draft`, `active`, `archived`)

---

## 1. Tabel Rancangan Aturan Validasi

Berikut aturan validasi terpisah untuk operasi **STORE** (tambah baru) dan **UPDATE** (perbarui):

| Field | Aturan Validasi (STORE) | Aturan Validasi (UPDATE) | Alasan & Penjelasan Desain |
|---|---|---|---|
| **code** | `required`, `string`, `max:20`, `unique:courses,code` | `required`, `string`, `max:20`, `unique:courses,code (ignore ID ini)` | Wajib diisi, dibatasi 20 karakter agar hemat dan rapi. Pada update, wajib mengecualikan ID data sendiri agar tidak ditolak sistem saat kodenya tidak diubah. |
| **name** | `required`, `string`, `max:150` | `required`, `string`, `max:150` | Wajib diisi, berupa teks, dibatasi 150 karakter agar sesuai panjang kolom database dan tidak merusak layout. |
| **description** | `nullable`, `string` | `nullable`, `string` | Bersifat opsional (boleh tidak diisi). Jika diisi, harus berupa teks valid. |
| **sks** | `required`, `integer`, `between:1,6` | `required`, `integer`, `between:1,6` | Wajib diisi berupa angka bulat antara 1 sampai 6, mencegah input 0 atau beban SKS yang tidak realistis (misal 99). |
| **lecturer_id** | `required`, `integer`, `exists:users,id` | `required`, `integer`, `exists:users,id` | Wajib diisi dan ID dosen harus ada di tabel `users`. Mencegah pembuatan data yatim atau kegagalan foreign key database. |
| **status** | `required`, `in:draft,active,archived` | `required`, `in:draft,active,archived` | Wajib diisi dan nilainya dikunci hanya boleh salah satu dari 3 pilihan enum yang sah. |

---

## 2. Perbedaan Kritis Aturan `unique` pada STORE vs UPDATE

### Operasi STORE (Pengecekan Mutlak)
Pada operasi penambahan data baru (`store`), aturan yang dipakai adalah:
```php
'code' => ['required', 'string', 'max:20', 'unique:courses,code']
```
Karena baris ini adalah data baru, kode yang dimasukkan sama sekali belum boleh ada di seluruh tabel `courses`.

### Operasi UPDATE (Pengecekan Relatif / Ignore ID)
Pada operasi pengeditan data (`update`), aturan yang dipakai harus mengecualikan record yang sedang diedit:
```php
'code' => [
    'required',
    'string',
    'max:20',
    Rule::unique('courses', 'code')->ignore($this->course)
]
```

### Kenapa Update Butuh Perlakuan Berbeda?
1. Skenario umum: Dosen atau Admin membuka halaman edit mata kuliah `SI2514024`.
2. Pengguna hanya ingin mengoreksi nama mata kuliah atau mengubah SKS dari 3 menjadi 4, tanpa menyentuh kolom kode (`code` tetap `SI2514024`).
3. Jika aturan `unique` ditulis polos tanpa `ignore`, Laravel akan menjalankan query:
   ```sql
   SELECT count(*) FROM courses WHERE code = 'SI2514024';
   ```
4. Query tersebut mengembalikan nilai `1` (karena data tersebut memang sudah ada di database, yaitu milik data itu sendiri).
5. Akibatnya, validasi gagal dan form menampilkan error palsu: *"Kode mata kuliah ini sudah dipakai"*.
6. Dengan menggunakan `ignore($this->course)`, query validasi akan ditambahkan klausa:
   ```sql
   SELECT count(*) FROM courses WHERE code = 'SI2514024' AND id != <id_kursus_ini>;
   ```
   Sehingga data miliknya sendiri diabaikan dari pengecekan duplikasi.

---

## 3. Rencana Pesan Validasi Kustom (`messages`)

Agar antarmuka pengguna ramah dan jelas, pesan error tidak boleh memakai teks bawaan bahasa Inggris:

```php
public function messages(): array
{
    return [
        'code.required'        => 'Kode mata kuliah wajib diisi.',
        'code.unique'          => 'Kode mata kuliah ini sudah terdaftar.',
        'code.max'             => 'Kode mata kuliah maksimal 20 karakter.',
        'name.required'        => 'Nama mata kuliah wajib diisi.',
        'name.max'             => 'Nama mata kuliah maksimal 150 karakter.',
        'sks.required'         => 'Beban SKS wajib diisi.',
        'sks.integer'          => 'Beban SKS harus berupa angka bulat.',
        'sks.between'          => 'Beban SKS harus antara 1 sampai 6.',
        'lecturer_id.required' => 'Dosen pengampu wajib dipilih.',
        'lecturer_id.exists'   => 'Dosen yang dipilih tidak valid atau tidak terdaftar di sistem.',
        'status.required'      => 'Status mata kuliah wajib dipilih.',
        'status.in'            => 'Status yang dipilih tidak sah (pilihan: draft, active, archived).',
    ];
}
```

---

## 4. Status Kesiapan
Rancangan aturan ini telah diverifikasi sesuai skema spesifikasi proyek dan siap diimplementasikan ke dalam Form Request Laravel 12 (`StoreCourseRequest` dan `UpdateCourseRequest`) pada **Prompt Pack B**.
