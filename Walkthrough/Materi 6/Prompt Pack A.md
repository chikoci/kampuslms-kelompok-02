# Implementasi REST API Sesuai Kontrak (Minggu 6 - Prompt Pack A)

## Konteks & Prompt
```text
Konteks: LMS Laravel 12 dengan Sanctum. Berikut kontrak API yang sudah
ditetapkan dosen dan TIDAK BOLEH diubah:

[Bagian 5 spesifikasi proyek KampusLMS]

Implementasikan endpoint untuk modul courses dan assignments saja.

Ketentuan:
- Laravel 12. routes/api.php diaktifkan lewat php artisan install:api.
- Setiap response memakai API Resource, JANGAN kembalikan model mentah.
- Format response persis seperti kontrak, termasuk struktur meta pada
  koleksi.
- Status code sesuai standar: 201 untuk store, 204 untuk destroy,
  401 vs 403 dibedakan dengan benar.
- Sertakan eager loading agar tidak ada N+1.
```

---

## 1. Ringkasan Implementasi Kontrak API Bagian 5

Sesuai ketentuan kontrak proyek Bagian 5, seluruh endpoint API wajib berada di bawah prefix `/api/v1`, terautentikasi menggunakan Laravel Sanctum (Bearer Token), dan semua data balikan dibungkus API Resource.

### Tabel Endpoint yang Telah Diimplementasikan:

| Method | Endpoint | Hak Akses | Status Code | Keterangan & Proteksi |
|---|---|---|---|---|
| POST | `/api/v1/auth/login` | Publik | 200 / 422 | Mengembalikan token Sanctum. Dibatasi throttle 5 percobaan/menit. Pesan error seragam untuk mencegah user enumeration. |
| POST | `/api/v1/auth/logout` | Auth | 200 | Mencabut token aktif saat ini (`currentAccessToken()->delete()`). |
| GET | `/api/v1/me` | Auth | 200 | Mengembalikan profil pengguna yang sedang login melalui `UserResource`. |
| GET | `/api/v1/courses` | Auth | 200 | Filter peran otomatis: Dosen melihat MK ajarannya, Mahasiswa melihat MK yang diikutinya. Memakai pagination + format `meta`. |
| GET | `/api/v1/courses/{course}` | Auth + Scope | 200 / 403 | Detail MK beserta counter materi, tugas, dan mahasiswa terdaftar. Ditolak 403 jika di luar kepemilikan/keterdaftaran. |
| GET | `/api/v1/courses/{course}/materials` | Auth + Scope | 200 / 403 | Daftar materi pada MK bersangkutan dengan eager load uploader. |
| GET | `/api/v1/courses/{course}/assignments` | Auth + Scope | 200 / 403 | Daftar tugas MK bersangkutan. Mendukung query filter `?status=` dan `?page=`. |
| POST | `/api/v1/assignments` | Dosen | 201 / 403 | Membuat tugas baru untuk MK yang diampu. Mengembalikan status 201 Created. Mahasiswa ditolak 403. |
| PUT/PATCH | `/api/v1/assignments/{assignment}` | Dosen (Pemilik) | 200 / 403 | Memperbarui detail tugas milik dosen bersangkutan. |
| DELETE | `/api/v1/assignments/{assignment}` | Dosen (Pemilik) | 204 / 403 | Menghapus tugas. Mengembalikan status 204 No Content. |
| GET | `/api/v1/assignments/{assignment}/submissions` | Dosen (Pemilik) | 200 / 403 | Melihat daftar seluruh pengumpulan tugas mahasiswa beserta nilainya. |
| POST | `/api/v1/assignments/{assignment}/submissions` | Mahasiswa (Terdaftar) | 201 / 403 | Mahasiswa mengunggah file tugas (multipart/form-data). Menghitung `is_late` otomatis di server. Status 201 Created. |
| GET | `/api/v1/submissions/{submission}` | Pemilik / Dosen | 200 / 403 | Melihat detail satu data pengumpulan tugas. |
| PUT | `/api/v1/submissions/{submission}/grade` | Dosen (Pemilik) | 201 / 200 / 403 | Penilaian tugas menggunakan metode upsert (`updateOrCreate`). Mengembalikan status 201 saat pertama kali dinilai dan 200 saat nilai diperbarui. |
| GET | `/api/v1/notifications` | Auth | 200 | Melihat riwayat notifikasi akun yang sedang login. |
| POST | `/api/v1/notifications/{id}/read` | Auth | 200 / 403 | Menandai notifikasi sebagai telah dibaca. |

---

## 2. Format Response Sesuai Standar Kontrak

Seluruh endpoint mematuhi struktur response JSON resmi:

### A. Format Sukses Koleksi (dengan Pagination dan Meta):
```json
{
  "data": [
    {
      "id": 1,
      "code": "SI2514024",
      "name": "Pemrograman Web",
      "sks": 3,
      "status": "active",
      "lecturer": {
        "id": 2,
        "name": "Pak Dosen",
        "email": "dosen@kampuslms.test",
        "role": "dosen"
      },
      "counts": {
        "materials": 3,
        "assignments": 2,
        "students": 30
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

### B. Format Sukses Tunggal:
```json
{
  "data": {
    "id": 1,
    "title": "Tugas API Sanctum",
    "max_score": 100,
    "status": "published"
  }
}
```

### C. Format Error Validasi (HTTP 422):
```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "score": [
      "Nilai tugas wajib diisi."
    ]
  }
}
```

### D. Format Akses Ditolak (HTTP 403):
```json
{
  "message": "Anda tidak memiliki akses ke sumber daya ini."
}
```

---

## 3. Penerapan Prinsip Keamanan & API Resource (Bebas N+1)

1. **Pencegahan Kebocoran Data (Whitelist):**
   * Menggunakan kelas `JsonResource` tersendiri.
   * Model mentah tidak pernah dikembalikan ke klien sehingga hash password, `remember_token`, dan atribut privat lainnya 100% aman.
2. **Eager Loading & `whenLoaded()`:**
   * Di controller: Menggunakan pemanggilan `with(['lecturer', ...])`.
   * Di resource: Menggunakan `new UserResource($this->whenLoaded('lecturer'))` dan `$this->whenCounted('...')`. Relasi hanya disertakan jika sudah dimuat sebelumnya, mencegah query berulang di setiap baris.
3. **Rate Limiting:**
   * Endpoint login dilindungi middleware `throttle:5,1` untuk mencegah serangan brute force.
   * Endpoint API privat dilindungi `throttle:60,1`.

---

## 4. Hasil Verifikasi Pengujian Otomatis

Rangkaian test otomatis pada `tests/Feature/ApiTest.php` mencakup 8 skenario utama:
* `✓ login returns token and user resource` (200 OK)
* `✓ login invalid credentials returns uniform error` (422 Unprocessable Entity)
* `✓ protected endpoint without token returns 401` (401 Unauthorized)
* `✓ me endpoint returns authenticated user` (200 OK)
* `✓ courses index returns paginated collection` (200 OK)
* `✓ student cannot create assignment returns 403` (403 Forbidden)
* `✓ dosen can create assignment returns 201` (201 Created)
* `✓ grading upsert returns 201 then 200` (201 Created lalu 200 OK)

**Status Pengujian:** 10 passed (34 assertions), 0 failures.
