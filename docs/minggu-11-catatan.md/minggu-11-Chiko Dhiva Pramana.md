# Catatan Praktikum Minggu 11: Optimisasi Performa

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Pengukuran Mandiri & Temuan N+1

Pada tahap READ ini, saya mengukur performa aplikasi KampusLMS secara langsung melalui **Laravel Debugbar** di browser menggunakan akun Dosen dan Mahasiswa dengan data seeder lengkap (30 mahasiswa, 5 mata kuliah, 15 tugas, dan ratusan pengumpulan tugas) untuk menemukan halaman yang paling lambat dan boros query.

Laporan tabel data lengkap dicatat pada file [docs/performa.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/docs/performa.md).

### 1. Ringkasan Pengukuran Asli dari Browser (Debugbar)
Berdasarkan pengujian langsung di browser lokal:
* **Halaman terburuk:** **Detail Mata Kuliah (`/courses/1`)** dengan **18 query**, waktu muat **196.0 ms**, dan memori **4 MB**. Debugbar secara langsung menandai adanya **3 duplicate queries**.
* **Halaman terlama kedua:** **Rekap Nilai Kelas (`/courses/1/grades`)** dengan waktu **183.0 ms** dan 10 query.
* **Halaman terlama ketiga:** **Transkrip Nilai Mahasiswa (`/my-grades`)** dengan waktu **165.0 ms**, 12 query, dan **2 duplicate queries**.
* **Dashboard Mahasiswa (`/dashboard`):** Menjalankan **14 query** dengan total waktu muat **104.0 ms** dan memori 3 MB.
* Halaman lainnya (Dashboard Dosen, Daftar Pengumpulan, Daftar Mata Kuliah) berkisar antara 7 hingga 10 query dengan memori 3-4 MB.

### 2. Mengapa Halaman Detail Mata Kuliah Menjadi yang Terburuk?
Setelah memeriksa tab query pada Debugbar, penyebab utamanya adalah **masalah N+1 dan duplikasi query di perulangan tampilan**:
* Di file view `resources/views/courses/show.blade.php`, setiap baris tugas memanggil perhitungan submissions secara terpisah:
  `{{ $assignment->submissions()->count() }}`
* Karena diletakkan di dalam perulangan foreach tugas, database dipaksa menjalankan query `SELECT COUNT(*)` berulang kali sebanyak tugas yang ada.
* Selain itu, terdapat query berulang ke tabel `courses` (`select * from courses where id = '1' limit 1`) yang dipicu saat memeriksa hak aksi materi dan tugas (lazy loading).

### 3. Temuan N+1 pada Halaman Mahasiswa (`/my-grades`)
Pada halaman transkrip nilai mahasiswa, Debugbar mendeteksi 2 query duplikat:
```sql
select * from users where users.id = 2 and users.deleted_at is null limit 1
```
Query ini terpanggil di file view `student.blade.php` baris 40 untuk mengambil nama dosen pengampu di setiap kotak mata kuliah, karena controller lupa melakukan eager loading relasi `lecturer`.

---

## Bagian BREAK: Eksperimen Mandiri (7 Percobaan)

*(Bagian ini akan dicoba dan diisi sendiri saat sesi eksperimen perusakan).*

- [ ] 1. Pasang `Model::preventLazyLoading()`, jalankan seluruh halaman. Amati berapa N+1 tersembunyi yang muncul.
- [ ] 2. Hapus semua `with()` dari satu controller, ukur ulang dan bandingkan lonjakan query-nya.
- [ ] 3. Ganti `withCount('students')` menjadi `count($course->students)`, ukur perbedaan pemakaian memorinya.
- [ ] 4. Hapus index dari `assignments.course_id`, jalankan `EXPLAIN` dan amati perubahan `type` menjadi `ALL`.
- [ ] 5. Naikkan seeder menjadi 300 mahasiswa dan 1.000 submission, buka rekap nilai dan amati kelambatannya.
- [ ] 6. Jalankan `config:cache`, lalu panggil `env('APP_NAME')` dari controller untuk melihat nilainya berubah menjadi `null`.
- [ ] 7. Pasang cache 10 menit pada nilai mahasiswa, ubah nilainya di dosen, lalu amati data basi yang muncul di mahasiswa.

---

## Bagian CHECKPOINT: Gerbang Interview Materi 11 (11.4)

*(Akan diisi setelah sesi BREAK dan optimisasi BUILD selesai).*

- [ ] 1. Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa halaman itu?
- [ ] 2. Peragakan N+1 di aplikasi Anda: matikan `with()` pada satu halaman dan tunjukkan lonjakan query di Debugbar.
- [ ] 3. Apa beda `withCount('students')` dan `count($course->students)`? Kenapa yang kedua boros?
- [ ] 4. Tunjukkan index yang Anda tambahkan. Jalankan `EXPLAIN` dan buktikan ia terpakai.
- [ ] 5. Sebutkan satu data di aplikasi Anda yang tidak boleh di-cache. Kenapa?
- [ ] 6. Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting minggu depan?
