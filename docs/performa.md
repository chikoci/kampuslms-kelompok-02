# Laporan Pengukuran & Optimisasi Performa KampusLMS

Dokumen ini mencatat hasil pengukuran riil performa aplikasi web KampusLMS sebelum dan sesudah optimisasi pada Materi 11. Seluruh data di bawah diambil langsung dari **Laravel Debugbar** di browser lokal dengan data seeder penuh (30 mahasiswa, 5 mata kuliah, 15 tugas, dan ratusan pengumpulan tugas).

---

## 1. Tabel Pengukuran Baseline Riil Browser (Sebelum Optimisasi)

Berikut adalah data pengukuran asli yang dicatat dari Laravel Debugbar untuk akun Dosen dan Mahasiswa:

| No | Halaman yang Diuji | Route / URL | Role Akun | Jumlah Query | Total Waktu (ms) | Waktu SQL (ms) | Memori (MB) | Status & Catatan |
|:---:|---|---|:---:|:---:|:---:|:---:|:---:|---|
| 1 | **Detail Mata Kuliah** | `/courses/1` | Dosen | **18 query** | **196.0 ms** | 45.27 ms | 4 MB | **Terburuk** (Ada 3 duplicate queries) |
| 2 | **Rekap Nilai Kelas** | `/courses/1/grades` | Dosen | **10 query** | **183.0 ms** | 19.77 ms | 4 MB | Lambat (Waktu render tinggi) |
| 3 | **Transkrip Nilai Saya** | `/my-grades` | Mahasiswa | **12 query** | **165.0 ms** | 43.71 ms | 3 MB | Ada 2 duplicate queries (Dosen MK) |
| 4 | **Dashboard Mahasiswa** | `/dashboard` | Mahasiswa | **14 query** | **104.0 ms** | 21.16 ms | 3 MB | Query banyak (Metrik relasi) |
| 5 | **Daftar Mata Kuliah** | `/courses` | Dosen | **7 query** | **103.0 ms** | 15.29 ms | 3 MB | Moderat |
| 6 | **Daftar Pengumpulan** | `/assignments/1/submissions` | Dosen | **10 query** | **100.0 ms** | 16.96 ms | 4 MB | Moderat |
| 7 | **Dashboard Dosen** | `/dashboard` | Dosen | **10 query** | **92.6 ms** | 19.50 ms | 3 MB | Moderat |

---

## 2. Analisis Halaman Terburuk

Dari hasil pengukuran riil browser di atas, halaman dengan performa paling lambat dan memiliki query terbanyak adalah:
**Halaman Detail Mata Kuliah (`/courses/1`) dengan 18 query dan total waktu muat 196 ms.**

Debugbar secara gamblang menandai:  
*"18 statements were executed, 3 of which were duplicates, 15 unique."*

### Mengapa Halaman Detail Mata Kuliah Paling Lambat?
1. **Query Duplikat / Berulang di Looping Blade (Masalah N+1):**
   Di dalam view `resources/views/courses/show.blade.php`, setiap baris tugas memanggil perhitungan submissions secara terpisah:
   ```blade
   {{ $assignment->submissions()->count() }} Pengumpulan
   ```
   Akibatnya, MySQL dipaksa mengeksekusi query `SELECT COUNT(*)` berulang kali sebanyak tugas yang terdaftar.
2. **Lazy Loading Relasi Induk:**
   Terdapat query duplikat ke tabel `courses` (`select * from courses where id = '1' limit 1`) yang dipicu saat memeriksa izin tombol aksi materi dan tugas.
3. **Beban Waktu SQL Tinggi:**
   Waktu pemrosesan database mencapai 45.27 ms, menjadikannya titik hambatan (*bottleneck*) utama aplikasi.

---

## 3. Masalah Serupa pada Halaman Mahasiswa (`/my-grades`)

Pada halaman **Transkrip Nilai Saya (`/my-grades`)**, Debugbar juga menemukan masalah N+1:  
*"12 statements were executed, 2 of which were duplicates, 10 unique."*

Penyebabnya adalah pemanggilan dosen pengampu pada view `resources/views/grades/student.blade.php` baris 40:
```sql
select * from users where users.id = 2 and users.deleted_at is null limit 1
```
Query ini dieksekusi berulang kali untuk setiap mata kuliah yang ditampilkan karena controller lupa melakukan eager loading `with('lecturer')`.

---

## 4. Target & Rencana Optimisasi (Tahap BUILD)

1. **Eager Loading Aggregates (`withCount`):**
   - Mengganti pemanggilan `$assignment->submissions()->count()` di Blade dengan `withCount('submissions')` di controller agar dihitung sekaligus dalam 1 query SQL.
2. **Eliminasi Lazy Loading & Duplicate Queries:**
   - Menambahkan eager load relasi dosen pada halaman transkrip mahasiswa (`courses.lecturer`).
   - Melengkapi relasi induk pada materi dan tugas (`materials.course`, `assignments.course`) dan mengaktifkan `Model::preventLazyLoading()`.
3. **Penambahan Index Komposit:**
   - Menambahkan index pada kombinasi kolom pencarian `['course_id', 'due_at']`.
4. **Target Hasil:**
   - Menurunkan query halaman Detail Mata Kuliah dari **18 query menjadi di bawah 6 query**.
   - Menurunkan query halaman Transkrip Mahasiswa dari **12 query menjadi di bawah 5 query**.
   - Menurunkan waktu muat halaman dari **196 ms menjadi di bawah 70 ms**.
