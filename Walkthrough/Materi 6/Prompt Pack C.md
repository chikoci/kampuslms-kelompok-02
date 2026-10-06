# Skrip Uji Otorisasi REST API Berbasis cURL (Minggu 6 - Prompt Pack C)

## Konteks & Prompt
```text
Berikut route API saya:

[routes/api.php]

Buatkan skrip pengujian berbasis curl yang membuktikan otorisasi
bekerja. Untuk setiap endpoint, uji empat kondisi:
1. Tanpa token sama sekali -> harus 401
2. Token mahasiswa mengakses endpoint dosen -> harus 403
3. Token dosen A mengakses data milik dosen B -> harus 403
4. Token yang benar dengan hak yang benar -> harus 200/201

Sajikan sebagai skrip bash yang bisa saya jalankan, dengan komentar
menjelaskan hasil yang diharapkan tiap perintah.
```

---

## 1. Implementasi Skrip Pengujian Otomatis

Skrip pengujian telah dibuat pada berkas [scripts/test-api.sh](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/scripts/test-api.sh). 

Skrip ini bekerja secara dinamis:
1. Melakukan login otomatis via `curl` untuk mengambil Bearer Token bagi akun **Dosen A** (`dosen@kampuslms.test`) dan akun **Mahasiswa** (`mahasiswa@kampuslms.test`).
2. Menjalankan 15 skenario pengujian yang mencakup 4 kondisi otorisasi keamanan.
3. Memeriksa status HTTP code yang dikembalikan server dan menampilkan label `[PASS]` atau `[FAIL]` berwarna di terminal.

---

## 2. Rincian 4 Kondisi yang Diuji & Hasil Eksekusi

### Kondisi 1: Akses Tanpa Token Sama Sekali (Harus 401 Unauthorized)
Membuktikan bahwa seluruh rute privat terlindungi middleware Sanctum dan menolak akses publik:
* `GET /api/v1/courses` tanpa token -> **HTTP 401 (PASS)**
* `GET /api/v1/me` tanpa token -> **HTTP 401 (PASS)**
* `POST /api/v1/assignments` tanpa token -> **HTTP 401 (PASS)**

### Kondisi 2: Token Mahasiswa Mengakses Fitur Dosen (Harus 403 Forbidden)
Membuktikan bahwa otorisasi peran berjalan di sisi server, bukan hanya di frontend:
* Mahasiswa memanggil `POST /api/v1/assignments` (membuat tugas) -> **HTTP 403 (PASS)**
* Mahasiswa memanggil `PUT /api/v1/assignments/1` (mengubah tugas) -> **HTTP 403 (PASS)**
* Mahasiswa memanggil `DELETE /api/v1/assignments/1` (menghapus tugas) -> **HTTP 403 (PASS)**
* Mahasiswa memanggil `GET /api/v1/assignments/1/submissions` (mengintip submission kelas) -> **HTTP 403 (PASS)**
* Mahasiswa memanggil `PUT /api/v1/submissions/1/grade` (memberi nilai) -> **HTTP 403 (PASS)**

### Kondisi 3: Token Dosen A Mengakses Data Milik Dosen B (Harus 403 Forbidden)
Membuktikan bahwa middleware role saja tidak cukup dan proteksi kepemilikan objek (IDOR) aktif:
* Dosen A memanggil `GET /api/v1/courses/2` (mata kuliah milik Dosen B) -> **HTTP 403 (PASS)**
* Dosen A memanggil `POST /api/v1/assignments` dengan `course_id: 2` (membuat tugas di MK dosen lain) -> **HTTP 403 (PASS)**

### Kondisi 4: Token Valid & Hak Akses Sesuai (Harus 200 OK / 201 Created)
Membuktikan jalur normal (*happy path*) berjalan mulus sesuai spesifikasi:
* Dosen A memanggil profil diri `GET /api/v1/me` -> **HTTP 200 OK (PASS)**
* Dosen A melihat daftar mata kuliahnya `GET /api/v1/courses` -> **HTTP 200 OK (PASS)**
* Dosen A melihat detail mata kuliah ajarannya `GET /api/v1/courses/1` -> **HTTP 200 OK (PASS)**
* Dosen A membuat tugas baru pada mata kuliah ajarannya `POST /api/v1/assignments` -> **HTTP 201 Created (PASS)**
* Mahasiswa memanggil profil diri `GET /api/v1/me` -> **HTTP 200 OK (PASS)**

---

## 3. Log Hasil Eksekusi di Terminal

```text
======================================================
   MEMULAI PENGUJIAN OTORISASI REST API KAMPUSLMS     
======================================================

>> Mengambil Token Akun Uji Coba...
  - Token Dosen A diperoleh.
  - Token Mahasiswa diperoleh.

>> [KONDISI 1] Akses Tanpa Token (Harus 401 Unauthorized)...
  [PASS] GET /courses tanpa token -> HTTP 401 (Sesuai: 401)
  [PASS] GET /me tanpa token -> HTTP 401 (Sesuai: 401)
  [PASS] POST /assignments tanpa token -> HTTP 401 (Sesuai: 401)

>> [KONDISI 2] Mahasiswa Mengakses Fitur Dosen (Harus 403 Forbidden)...
  [PASS] Mahasiswa memanggil POST /assignments -> HTTP 403 (Sesuai: 403)
  [PASS] Mahasiswa memanggil PUT /assignments/1 -> HTTP 403 (Sesuai: 403)
  [PASS] Mahasiswa memanggil DELETE /assignments/1 -> HTTP 403 (Sesuai: 403)
  [PASS] Mahasiswa memanggil GET /assignments/1/submissions -> HTTP 403 (Sesuai: 403)
  [PASS] Mahasiswa memanggil PUT /submissions/1/grade -> HTTP 403 (Sesuai: 403)

>> [KONDISI 3] Dosen A Mengakses Data Milik Dosen B (Harus 403 Forbidden)...
  [PASS] Dosen A memanggil GET /courses/2 (milik Dosen B) -> HTTP 403 (Sesuai: 403)
  [PASS] Dosen A membuat tugas pada Course ID 2 -> HTTP 403 (Sesuai: 403)

>> [KONDISI 4] Token Valid & Hak Akses Sesuai (Harus 200/201)...
  [PASS] Dosen A memanggil GET /me -> HTTP 200 (Sesuai: 200)
  [PASS] Dosen A memanggil GET /courses -> HTTP 200 (Sesuai: 200)
  [PASS] Dosen A memanggil GET /courses/1 -> HTTP 200 (Sesuai: 200)
  [PASS] Dosen A membuat tugas pada Course ID 1 -> HTTP 201 (Sesuai: 201)
  [PASS] Mahasiswa memanggil GET /me -> HTTP 200 (Sesuai: 200)

======================================================
  PENGUJIAN SELESAI: SELURUH OTORISASI BERFUNGSI 100% 
======================================================
```

---

## Cara Menjalankan Skrip Ini Mandiri
Buka Git Bash di root proyek KampusLMS, lalu ketik:
```bash
bash scripts/test-api.sh
```
*(Pastikan `php artisan serve` aktif di latar belakang pada port 8000).*
