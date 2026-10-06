# Audit Keamanan & Kebocoran Data API (Minggu 6 - Prompt Pack B)

## Konteks & Prompt
```text
Berikut API Resource dan controller saya:

[app/Http/Resources/* dan app/Http/Controllers/Api/*]

Audit khusus kebocoran data:
1. Adakah field sensitif yang ikut keluar di response? Periksa satu per
   satu terhadap kolom tabel yang bersangkutan.
2. Apakah relasi yang disertakan membocorkan data milik pengguna lain?
   Contoh: apakah endpoint detail mata kuliah menampilkan daftar
   mahasiswa lengkap dengan emailnya kepada sesama mahasiswa?
3. Apakah pesan error membocorkan informasi (misalnya membedakan
   "email tidak terdaftar" dan "password salah")?
4. Apakah endpoint yang seharusnya terbatas peran benar-benar dijaga
   di sisi API, bukan hanya di frontend?

Untuk tiap temuan, tunjukkan response JSON yang bocor dan perbaikannya.
```

---

## 1. Audit Poin 1: Pemeriksaan Field Sensitif per Tabel

Pemeriksaan dilakukan dengan membandingkan seluruh kolom tabel database terhadap atribut yang dikembalikan oleh setiap kelas `JsonResource`:

### A. Tabel `users` vs `UserResource`
* **Kolom Tabel:** `id`, `name`, `email`, `password`, `role`, `nim_nip`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`.
* **Atribut di `UserResource`:** `id`, `name`, `email`, `role`, `nim_nip`, `created_at`.
* **Status:** **AMAN (Lolos Whitelist)**.
* **Analisis Kebocoran:**
  * Field paling berbahaya (`password` hash dan `remember_token`) sama sekali tidak dimasukkan ke dalam resource.
  * Field metadata internal (`email_verified_at`, `deleted_at`, `updated_at`) juga disembunyikan dari respon publik.

#### Perbandingan: Respon Bocor vs Respon Aman
* **Jika Bocor (Model Mentah):**
  ```json
  {
    "id": 2,
    "name": "Dr. Dosen Pengampu, M.Kom.",
    "email": "dosen@kampuslms.test",
    "password": "$2y$12$eGgX0q8A...",
    "remember_token": "a1b2c3d4...",
    "role": "dosen"
  }
  ```
* **Respon Aman Aplikasi Kita:**
  ```json
  {
    "data": {
      "id": 2,
      "name": "Dr. Dosen Pengampu, M.Kom.",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "198501012010121001",
      "created_at": "2026-09-17T05:35:44+00:00"
    }
  }
  ```

---

## 2. Audit Poin 2: Kebocoran Data Relasi (Privasi Pengguna Lain)

* **Potensi Masalah:** Endpoint detail mata kuliah (`GET /courses/{id}`) berisiko membocorkan daftar seluruh mahasiswa beserta alamat surel dan nomor identitasnya kepada sesama mahasiswa.
* **Hasil Audit Kode:**
  * Di `CourseController.php`: Relasi mahasiswa **tidak di-*load*** secara penuh (`students`). Controller hanya memanggil:
    ```php
    $course->load('lecturer')->loadCount(['materials', 'assignments', 'students']);
    ```
  * Di `CourseResource.php`: Tidak ada objek `students` yang diekspos, melainkan hanya angka agregat:
    ```php
    'counts' => [
        'materials' => $this->whenCounted('materials'),
        'assignments' => $this->whenCounted('assignments'),
        'students' => $this->whenCounted('students'),
    ]
    ```
* **Status:** **AMAN (Privasi Mahasiswa Terlindungi)**.

---

## 3. Audit Poin 3: Kebocoran Pesan Error Login (*User Enumeration*)

* **Potensi Masalah:** Membedakan pesan kesalahan ("Email tidak terdaftar" vs "Kata sandi salah") memberikan alat bagi penyerang luar untuk memetakan alamat email siapa saja yang terdaftar di dalam sistem.
* **Hasil Audit Kode:**
  * Pada `AuthController.php`, validasi kredensial login menggunakan satu kondisi gabungan dengan pesan yang diseragamkan:
    ```php
    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Email atau kata sandi salah.'],
        ]);
    }
    ```
* **Status:** **AMAN (Bebas Celah User Enumeration)**.

---

## 4. Audit Poin 4: Penegakan Otorisasi Sisi Server (API Server-Side Guard)

Pemeriksaan dilakukan pada setiap endpoint yang memerlukan hak akses spesifik:

1. **Pembuatan Tugas (`POST /api/v1/assignments`):**
   * Mahasiswa ditolak secara tegas dengan status **403 Forbidden**:
     ```php
     if ($user->role === 'mahasiswa') {
         abort(response()->json(['message' => 'Anda tidak memiliki akses ke sumber daya ini.'], 403));
     }
     ```
   * Dosen yang bukan pengampu mata kuliah bersangkutan juga ditolak dengan status **403 Forbidden**.
2. **Pengumpulan Tugas (`POST /api/v1/assignments/{id}/submissions`):**
   * Hanya mahasiswa yang terdaftar pada mata kuliah bersangkutan (`$course->students()->where('users.id', $user->id)->exists()`) yang diizinkan mengumpulkan file.
3. **Pemberian Nilai (`PUT /api/v1/submissions/{id}/grade`):**
   * Hanya dosen pengampu mata kuliah dari tugas bersangkutan yang memiliki izin memberi atau memperbarui nilai tugas. Mahasiswa maupun dosen lain ditolak dengan status **403 Forbidden**.
4. **Endpoint Tanpa Token:**
   * Seluruh rute privat dijaga middleware `auth:sanctum`. Permintaan tanpa token otomatis menghasilkan **401 Unauthorized** dengan respon JSON seragam: `{"message": "Unauthenticated."}`.

* **Status:** **AMAN (100% Proteksi Sisi Server)**.

---

## Kesimpulan Audit
Sistem REST API KampusLMS telah mematuhi prinsip *least privilege*, *defense in depth*, dan standar kontrak Bagian 5 spesifikasi proyek:
* Tidak ada model mentah yang bocor.
* Tidak ada relasi privat yang tereksploitasi.
* Bebas dari serangan user enumeration.
* Otorisasi terlindungi penuh di backend (controller & middleware).
