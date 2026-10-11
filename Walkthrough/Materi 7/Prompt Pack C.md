# Skrip Pengujian Otorisasi & Verifikasi CI (Minggu 7 - Prompt Pack C)

## 1. Skrip Uji Keamanan: `scripts/test-authz.sh`
Skrip ini dirancang untuk menguji seluruh matriks keamanan tanpa intervensi manual menggunakan cURL, sesi cookie otentikasi resmi, dan evaluasi kode status HTTP.

### Cara Menjalankan di Terminal Lokal:
```bash
# Pastikan server aplikasi berjalan di port 8000
php artisan serve --port=8000

# Jalankan skrip uji otorisasi (via Git Bash atau Linux/macOS)
./scripts/test-authz.sh
```

### 25 Skenario Uji yang Dieksekusi:
1. **Otentikasi:** Sesi login resmi Admin, Dosen, Mahasiswa disiapkan.
2. **Dashboard:** Admin (200), Dosen (200), Mahasiswa (200), Tamu (302 redirect login).
3. **Mata Kuliah:**
   * Admin buka form tambah MK (200).
   * Dosen coba buat MK baru (403).
   * Mahasiswa coba buat MK baru (403).
   * Dosen edit MK sendiri (200).
   * Dosen coba edit MK dosen lain (403).
   * Mahasiswa coba edit MK (403).
   * Mahasiswa melihat MK yang diikutinya (200).
   * Mahasiswa coba melihat MK yang tidak diikutinya (403).
4. **Materi & Tugas:**
   * Dosen tambah materi pada MK sendiri (200).
   * Dosen coba tambah materi pada MK dosen lain (403).
   * Mahasiswa coba tambah materi (403).
   * Dosen buat tugas pada MK sendiri (200).
   * Dosen coba buat tugas pada MK dosen lain (403).
   * Mahasiswa coba buat tugas baru (403).
5. **IDOR Submission:**
   * Mahasiswa melihat submission miliknya sendiri (200).
   * Mahasiswa coba melihat submission mahasiswa lain / IDOR Attack (403).
   * Dosen pengampu memeriksa submission mahasiswanya (200).
   * Admin memeriksa submission mahasiswa (200).
6. **Manajemen Pengguna & Privilege Escalation:**
   * Admin mengakses daftar pengguna (200).
   * Dosen dilarang membuka daftar pengguna (403).
   * Mahasiswa dilarang membuka daftar pengguna (403).

**Hasil Pengujian:**
```text
Total Pengujian  : 25
Lolos (Passed)   : 25
Gagal (Failed)   : 0
SELURUH PENGUJIAN OTORISASI LOLOS (100% SUKSES)!
```

---

## 2. Automated Feature Test: `tests/Feature/AuthzTest.php`
Menguji kernel aplikasi menggunakan PHPUnit dengan transaksi basis data (`DatabaseTransactions`).

Dijalankan menggunakan:
```bash
php artisan test --filter AuthzTest
```
Seluruh 32 method pengujian lulus (PASS) dalam waktu kurang dari 3 detik.

---

## 3. Integrasi GitHub Actions: `.github/workflows/ci.yml`
Pipeline CI memastikan kualitas kode secara otomatis pada setiap `push` dan `pull_request` ke cabang `main`:
1. Checkout repository & setup PHP 8.4 dengan ekstensi pdo_mysql.
2. Menyalakan service container MySQL 8.0.
3. Menyiapkan berkas konfigurasi `.env` secara aman tanpa mengekspos kredensial rahasia.
4. Menjalankan `php artisan migrate:fresh --seed`.
5. Mengeksekusi pengujian PHPUnit (`php artisan test`).
6. Menyalakan server background dan mengeksekusi `scripts/test-authz.sh`.
