# Catatan Praktikum Minggu 11: Optimisasi Performa

**Nama:** Armansyah  
**NIM:** 10241013  

---

## Bagian READ — Ukur Aplikasi Sendiri (40 Menit)

Pengukuran dilakukan pada basis data dengan seeder lengkap (`php artisan migrate:fresh --seed`) memuat 30 mahasiswa, 5 mata kuliah, 15 tugas, dan >100 submission.

Tabel hasil pengukuran langsung dari bilah Laravel Debugbar di browser:

| Halaman | Jumlah query | Waktu (ms) | Memori |
|---|:---:|:---:|:---:|
| Daftar mata kuliah | 5 query *(0 duplicate)* | 301 ms *(SQL: 6.53 ms)* | 3 MB |
| Detail mata kuliah | 16 query *(3 duplicate)* | 326 ms *(SQL: 50.82 ms)* | 4 MB |
| Daftar tugas | 9 query *(3 duplicate)* | 368 ms *(SQL: 35.98 ms)* | 3 MB |
| Daftar pengumpulan (dosen) | 8 query *(0 duplicate)* | 132 ms *(SQL: 22.38 ms)* | 4 MB |
| **Rekap nilai** | 8 query *(0 duplicate)* | 325 ms *(SQL: 21.38 ms)* | 4 MB |
| Dashboard mahasiswa | 12 query *(2 duplicate)* | 191 ms *(SQL: 42.79 ms)* | 3 MB |
| Dashboard dosen | 8 query *(0 duplicate)* | 300 ms *(SQL: 23.62 ms)* | 3 MB |

---

### 1. Urutkan dari yang Terburuk

**A. Berdasarkan Jumlah Kueri (Kondisi Riil Terbanyak di Laptop Saya):**
1. **Detail Mata Kuliah (`/courses/1`) — 16 query** *(3 duplicate query)*
2. **Dashboard Mahasiswa (`/dashboard`) — 12 query** *(2 duplicate query)*
3. **Daftar Tugas (`/courses/1/assignments`) — 9 query** *(3 duplicate query)*
4. **Rekap Nilai (`/courses/1/grades`) — 8 query** *(0 duplicate)*
5. **Dashboard Dosen (`/dashboard`) — 8 query** *(0 duplicate)*
6. **Daftar Pengumpulan Dosen (`/assignments/1/submissions`) — 8 query** *(0 duplicate)*
7. **Daftar Mata Kuliah (`/courses`) — 5 query** *(0 duplicate)*

**B. Berdasarkan Waktu Eksekusi Kueri Database (SQL Time Terlambat):**
1. **Detail Mata Kuliah (`/courses/1`) — SQL: 50.82 ms** *(Total: 326 ms)*
2. **Dashboard Mahasiswa (`/dashboard`) — SQL: 42.79 ms** *(Total: 191 ms)*
3. **Daftar Tugas (`/courses/1/assignments`) — SQL: 35.98 ms** *(Total: 368 ms)*
4. **Dashboard Dosen (`/dashboard`) — SQL: 23.62 ms** *(Total: 300 ms)*
5. **Daftar Pengumpulan Dosen (`/assignments/1/submissions`) — SQL: 22.38 ms** *(Total: 132 ms)*
6. **Rekap Nilai (`/courses/1/grades`) — SQL: 21.38 ms** *(Total: 325 ms)*
7. **Daftar Mata Kuliah (`/courses`) — SQL: 6.53 ms** *(Total: 301 ms)*

---

### 2. Temukan Kueri yang Berulang dengan Pola Sama (N+1 Query)

Ketika membuka **Debugbar tab Queries** dan mengaktifkan filter **"Show only duplicated"**, ditemukan pola kueri berulang yang menjadi biang kerok N+1:

#### Pola 1: Duplikasi Policy Otorisasi pada Daftar Tugas & Detail MK
Kueri mencari data mata kuliah yang sama terpanggil berulang kali:
```sql
SELECT * FROM `courses` WHERE `courses`.`id` = 1 LIMIT 1;
SELECT * FROM `courses` WHERE `courses`.`id` = 1 LIMIT 1;
SELECT * FROM `courses` WHERE `courses`.`id` = 1 LIMIT 1;
```
* **Penyebab:** Dipanggil saat pengecekan otorisasi policy tombol aksi materi dan tugas pada view Blade.

#### Pola 2: Kueri N+1 pada Relasi Induk (Baseline Tanpa Eager Loading)
Ketika relasi `lecturer` dipanggil di dalam loop tanpa `with()`:
```sql
SELECT * FROM `users` WHERE `users`.`id` = 2 LIMIT 1;
SELECT * FROM `users` WHERE `users`.`id` = 3 LIMIT 1;
SELECT * FROM `users` WHERE `users`.`id` = 4 LIMIT 1;
... (berulang sebanyak baris data)
```

---

### 3. Salin Satu Kueri Terlambat dan Jalankan EXPLAIN di Database

Kueri penyaringan daftar tugas aktif per mata kuliah yang diurutkan berdasarkan tenggat waktu (`due_at`):
```sql
EXPLAIN SELECT * FROM `assignments` WHERE `course_id` = 1 AND `status` = 'published' ORDER BY `due_at` ASC;
```

**Hasil Pengujian EXPLAIN:**

| Kondisi | Kolom `type` | Kolom `rows` | Kolom `key` (Indeks Terpakai) | Kolom `Extra` | Penjelasan |
|---|:---:|:---:|---|---|---|
| **Sebelum Indexing** | **`ALL`** | **150** | `NULL` | `Using where; Using filesort` | Database melakukan **Full Table Scan** membaca seluruh 150 baris dan melakukan sortir memori lambat. |
| **Sesudah Indexing** | **`ref`** | **3** | `assignments_course_status_due_idx` | *None (Bersih)* | Database langsung melompat ke 3 baris data yang cocok menggunakan indeks komposit B-Tree tanpa sortir tambahan. |

---

## Bagian BREAK — Eksperimen Mandiri & Hal yang Diamati

Berikut 6 eksperimen BREAK yang dilakukan untuk menganalisis batas performa, mendeteksi kerentanan N+1, dan menguji keandalan sistem optimasi Laravel:

---

### 1. `Model::preventLazyLoading` (Deteksi Dini N+1 Tersembunyi Otomatis)
* **Tujuan Eksperimen:** Membuktikan bahwa Laravel dapat bertindak sebagai alarm otomatis untuk menangkap N+1 tersembunyi selama masa pengembangan.
* **Langkah Pengujian:**
  1. Buka file `kampuslms-kelompok-02/app/Providers/AppServiceProvider.php`.
  2. Tambahkan perintah pencegahan lazy loading di dalam method `boot()`:
     ```php
     Model::preventLazyLoading(! app()->isProduction());
     ```
  3. Buka halaman yang relasinya belum di-eager-load (misal Transkrip Nilai Mahasiswa `/my-grades` sebelum ditambah `with('lecturer')`).
* **Apa yang Diamati:**
  * **Saat `preventLazyLoading` Aktif:** Laravel langsung menghentikan proses rendering dan menampilkan layar merah error: `Illuminate\Database\LazyLoadingViolationException: Attempted to lazy load [lecturer] on model [App\Models\Course] but lazy loading is disabled.` Developer langsung tahu detik itu juga ada relasi yang lupa di-load.
  * **Saat Tidak Aktif (Default):** Halaman tetap terbuka normal tanpa pesan error apa pun, tetapi diam-diam mengeksekusi duplikasi query N+1 ke database yang membebani server produksi.

---

### 2. Mematikan Eager Loading `with()` (Mengamati Lonjakan Kueri N+1)
* **Tujuan Eksperimen:** Mengamati secara visual bagaimana kelalaian eager loading menyebabkan ledakan query N+1 di Debugbar.
* **Langkah Pengujian:**
  1. Buka file `kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php` pada baris 37.
  2. Matikan sementara relasi `with(['student', 'grade'])`:
     ```php
     // SEBELUM:
     $submissions = $assignment->submissions()->with(['student', 'grade'])->get()->keyBy('user_id');

     // DIUBAH SEMENTARA MENJADI:
     $submissions = $assignment->submissions()->get()->keyBy('user_id');
     ```
  3. Akses halaman Daftar Pengumpulan Tugas Dosen: `http://127.0.0.1:8000/assignments/1/submissions`.
* **Apa yang Diamati:**
  * **Badge Debugbar:** Jumlah kueri langsung melonjak drastis dari **8 kueri menjadi 23 kueri!** Lencana indikator query di Debugbar berubah warna menjadi **MERAH**.
  * **Tab Queries:** Muncul peringatan *"Duplicated Queries"* di mana kueri mencari nilai mahasiswa (`select * from grades where submission_id = ?`) dijalankan berulang kali satu per satu untuk setiap baris mahasiswa di Blade.
  * Begitu eager loading `with(['student', 'grade'])` dikembalikan, kueri langsung kembali turun bersih ke **8 kueri**.

---

### 3. Agregasi Database `withCount()` vs `count()` PHP di RAM
* **Tujuan Eksperimen:** Membuktikan perbedaan efisiensi antara perhitungan di tingkat SQL database versus pemuatan seluruh model ke RAM server.
* **Langkah Pengujian:**
  1. Buka file `kampuslms-kelompok-02/app/Http/Controllers/DashboardController.php` pada method `dosenDashboard()`.
  2. Bandingkan dua cara perhitungan jumlah mahasiswa:
     * **Cara A (Database SQL):** `$myCourses->withCount('students')`
     * **Cara B (PHP RAM):** Menghapus `withCount`, lalu di view memanggil `count($course->students)`
* **Apa yang Diamati:**
  * **Pada Cara A (`withCount`):** MySQL menjalankan query `SELECT count(*)` dan hanya mengirim satu angka integer kecil ke PHP. Konsumsi memori RAM tetap sangat rendah (3 MB) dan waktu eksekusi sangat cepat.
  * **Pada Cara B (`count($course->students)`):** Laravel terpaksa menarik puluhan record data mahasiswa beserta seluruh kolomnya (nama, email, hash password, timestamp) dan membuat puluhan objek model di RAM PHP, baru kemudian PHP menghitung isi array-nya. Penggunaan memori meningkat dan waktu CPU server terbuang sia-sia.

---

### 4. Pengujian Efektivitas Composite Index (`EXPLAIN`)
* **Tujuan Eksperimen:** Membuktikan secara nyata bahwa indeks komposit `['course_id', 'status', 'due_at']` berhasil mencegah Full Table Scan.
* **Langkah Pengujian:**
  1. Jalankan kueri pengujian menggunakan klausa normal:
     ```sql
     EXPLAIN SELECT * FROM assignments WHERE course_id = 1 AND status = 'published' ORDER BY due_at ASC;
     ```
  2. Bandingkan dengan memaksa MySQL mengabaikan indeks yang kita buat (`IGNORE INDEX`):
     ```sql
     EXPLAIN SELECT * FROM assignments IGNORE INDEX (assignments_course_status_due_idx) WHERE course_id = 1 AND status = 'published' ORDER BY due_at ASC;
     ```
* **Apa yang Diamati:**
  * **Dengan Indeks:** Kolom `key` terisi `assignments_course_status_due_idx`, kolom `type` bernilai `ref`, dan kolom `rows` hanya bernilai `3` baris data. Kolom `Extra` bersih tanpa `Using filesort`.
  * **Tanpa Indeks (`IGNORE INDEX`):** Kolom `key` bernilai `NULL`, kolom `type` langsung berubah menjadi **`ALL`** (Full Table Scan), memeriksa seluruh baris tabel (150 rows), dan kolom `Extra` menampilkan peringatan berat `Using where; Using filesort`.

---

### 5. Caching Dashboard & Pembersihan Otomatis (*Hit Cache & Invalidation*)
* **Tujuan Eksperimen:** Membuktikan penghematan query via `Cache::remember` dan keandalan event pembersihan cache otomatis di model.
* **Langkah Pengujian:**
  1. Buka halaman `/dashboard` login sebagai Dosen.
  2. Amati Debugbar saat pertama kali dibuka (kondisi *Cache Miss*): tertera **10 query**.
  3. Lakukan Refresh halaman (F5) sekali lagi (kondisi *Cache Hit*).
  4. Buka menu tugas, tambahkan 1 tugas baru di salah satu mata kuliah (`/courses/1/assignments/create`), lalu buka kembali `/dashboard`.
* **Apa yang Diamati:**
  * **Saat Refresh (Hit Cache):** Jumlah query langsung terpangkas dari **10 menjadi 6 query**, dan waktu SQL turun menjadi ~10 ms karena angka ringkasan statistik dibaca langsung dari memori tanpa menyentuh MySQL.
  * **Saat Tugas Baru Ditambahkan:** Angka "Total Tugas" di dashboard langsung bertambah seketika dan data terbaru langsung tampil. Event model `Assignment::saved()` di `app/Models/Assignment.php` terbukti sukses mengeksekusi `Cache::forget()` sehingga dashboard tidak menampilkan data basi.

---

### 6. Pengujian `config:cache` dan Efek `null` pada Fungsi `env()`
* **Tujuan Eksperimen:** Memahami dampak penguncian konfigurasi saat deployment server produksi.
* **Langkah Pengujian:**
  1. Di terminal PowerShell, jalankan: `php artisan config:cache`
  2. Uji fungsi `env()` dan `config()` via tinker:
     ```powershell
     php artisan tinker --execute="dump(env('APP_NAME'));"
     php artisan tinker --execute="dump(config('app.name'));"
     ```
  3. Bersihkan kembali cache: `php artisan config:clear`
* **Apa yang Diamati:**
  * Nilai `env('APP_NAME')` menghasilkan **`null`** karena Laravel menonaktifkan pembacaan file `.env` setelah dicache.
  * Nilai `config('app.name')` tetap terbaca dengan benar (**`'Laravel'`**).
  * Ini membuktikan fungsi `env()` dilarang keras ditulis di Controller/View karena akan menyebabkan error fatal di server produksi.

---

## Bagian CHECKPOINT: Gerbang Evaluasi Minggu 11 (11.4)

Berikut panduan 6 butir evaluasi Checkpoint 11.4 yang diselaraskan dengan hasil pengukuran dokumen `docs/performa.md` dan kondisi sistem nyata aplikasi kita:

---

### 1. Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa halaman itu?

* **Jawaban Berdasarkan Dokumen `docs/performa.md`:**  
  Halaman yang paling lambat dan memiliki kueri terbanyak sebelum optimisasi adalah **Detail Mata Kuliah (`/courses/1`)** dengan **18 query**, total waktu muat **196.0 ms**, waktu SQL **45.27 ms**, dan memori **4 MB**. Debugbar menandai adanya **3 duplicate queries**.

* **Kenapa Halaman Itu Paling Buruk?**
  1. **N+1 Query pada Perhitungan Tugas di Blade:** Di file `resources/views/courses/show.blade.php`, setiap baris tugas memanggil perhitungan submissions secara terpisah: `{{ $assignment->submissions()->count() }}`. Akibatnya MySQL dipaksa menjalankan query `SELECT COUNT(*)` berulang kali sebanyak tugas yang terdaftar.
  2. **Lazy Loading Relasi Induk:** Terjadi kueri berulang ke tabel `courses` (`select * from courses where id = 1`) saat memeriksa izin tombol aksi materi dan tugas via Policy.
  3. **Waktu Database (SQL) Tertinggi:** Menghabiskan waktu 45.27 ms, menjadikannya titik hambatan (*bottleneck*) utama aplikasi.

* **Korelasi dengan Pengukuran Mandiri Saya:**  
  Pada laptop saya sendiri, halaman **Detail Mata Kuliah** juga terbukti menjadi yang paling lambat dengan **16 kueri** dan waktu SQL **50.82 ms**. Keduanya sama-sama membuktikan halaman ini adalah yang paling kritis sebelum dioptimasi.

* **Hasil Setelah Dioptimasi (Berdasarkan `docs/performa.md`):**  
  Setelah dipasangi eager load agregat `withCount('submissions')` dan eliminasi lazy loading, kueri halaman Detail Mata Kuliah **turun dari 18 menjadi 14 kueri**, dan waktu database (SQL) terpangkas hingga 45% menjadi **24.89 ms**.

* **Cara Memperagakannya ke Dosen:**  
  1. Buka file `kampuslms-kelompok-02/docs/performa.md` di VS Code, tunjukkan tabel perbandingan sebelum vs sesudah optimisasi pada baris nomor 1.
  2. Buka browser pada URL `http://127.0.0.1:8000/courses/1` (login Dosen).
  3. Tunjukkan panel Debugbar di bagian bawah layar: tertera waktu SQL sudah turun cepat dan perhitungan tugas sudah terintegrasi rapi.

* **Kalimat ke Penguji:**  
  *"Berdasarkan dokumen performa tim kami Pak/Bu, halaman terburuk sebelum optimisasi adalah Detail Mata Kuliah (/courses/1) dengan 18 query dan waktu SQL 45.27 ms. Penyebabnya adalah N+1 query hitungan submission di loop Blade dan duplikasi relasi parent saat cek policy. Di sistem kita sekarang, halaman tersebut sudah berhasil dioptimasi menggunakan withCount sehingga query turun menjadi 14 dan waktu SQL terpangkas 45% menjadi 24.89 ms."*

---

### 2. Peragakan N+1 di aplikasi Anda: matikan `with()` pada satu halaman dan tunjukkan lonjakan query di Debugbar.

* **Jawaban Berdasarkan Sistem:**  
  Masalah N+1 terjadi saat kita mengambil data induk, lalu di dalam perulangan kita memanggil data relasinya satu per satu sehingga mengeksekusi $N$ kueri tambahan. Kita peragakan pada halaman **Daftar Pengumpulan Tugas Dosen (`/assignments/1/submissions`)**.

* **Langkah Nyata Peragaan ke Dosen:**  
  1. Buka browser pada: `http://127.0.0.1:8000/assignments/1/submissions` (login Dosen).  
     *Tunjukkan ke dosen di Debugbar: kueri normal saat ini adalah **8 kueri**.*
  2. Buka file `kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php` (baris 37) di VS Code.
  3. Hapus sementara bagian `->with(['student', 'grade'])` sehingga kodenya menjadi:
     ```php
     $submissions = $assignment->submissions()->get()->keyBy('user_id');
     ```
  4. Simpan file (`Ctrl + S`), lalu kembali ke browser dan tekan **F5 (Refresh)**.
  5. **Tunjukkan lonjakan kueri di Debugbar:** Jumlah kueri seketika **melonjak dari 8 kueri menjadi 23 kueri!** Badge query berubah warna menjadi merah karena Laravel mengeksekusi kueri nilai satu per satu untuk 15 mahasiswa di Blade.
  6. Kembalikan kode semula dengan menekan `Ctrl + Z` di VS Code, simpan (`Ctrl + S`), lalu refresh browser: kueri kembali turun dan bersih ke **8 kueri**.

* **Kalimat ke Penguji:**  
  *"Bisa dilihat Pak/Bu, saat with(['student', 'grade']) dimatikan, halaman pengumpulan tugas mengeksekusi kueri nilai satu per satu untuk setiap mahasiswa sehingga kueri melonjak dari 8 menjadi 23 kueri. Begitu eager loading with() diaktifkan kembali, seluruh data ditarik sekaligus via klausa IN sehingga kembali turun menjadi 8 kueri bersih."*

---

### 3. Apa beda `withCount('students')` dan `count($course->students)`? Kenapa yang kedua boros?

* **Jawaban Sederhana & Tepat:**  
  * **`withCount('students')` (Hemat & Efisien):** Perhitungan dilakukan langsung oleh mesin database MySQL menggunakan subquery `SELECT COUNT(*)`. Yang dikirim ke server PHP hanyalah sebuah angka integer kecil (misal: `30`). Penggunaan memori RAM PHP tetap sangat rendah (~3 MB).
  * **`count($course->students)` (Sangat Boros):** Perintah ini mengakses relasi model Eloquent secara langsung. Akibatnya, Laravel mengeksekusi kueri `SELECT * FROM users ...` dan memuat seluruh 30 baris objek model mahasiswa lengkap beserta semua kolomnya (nama, email, password hash, timestamp) ke dalam memori RAM PHP, baru kemudian PHP menghitung panjang array-nya secara manual.
  * **Kenapa yang kedua boros:** Membebani memori RAM server, membuang bandwidth koneksi database, dan membuang siklus CPU server untuk menginstansiasi puluhan objek model yang tidak pernah dipakai atributnya.

* **Bukti Kode di Aplikasi Kita:**  
  Di file `kampuslms-kelompok-02/app/Http/Controllers/DashboardController.php` baris 71-74:
  ```php
  $myCourses = $user->taughtCourses()
      ->withCount(['materials', 'assignments', 'students'])
      ->latest()
      ->get();
  ```
  Tiga perhitungan sekaligus diselesaikan langsung di level database dalam satu kueri tunggal yang sangat ringan.

* **Kalimat ke Penguji:**  
  *"Perbedaannya terletak pada tempat komputasinya Pak/Bu. withCount() menghitung langsung di dalam database SQL dan hanya mengirim satu angka integer ke PHP, sedangkan count($course->students) memuat seluruh data objek mahasiswa ke RAM server baru dihitung manual di PHP, sehingga sangat boros memori."*

---

### 4. Tunjukkan index yang Anda tambahkan. Jalankan `EXPLAIN` dan buktikan ia terpakai.

* **Jawaban Berdasarkan Sistem:**  
  Indeks komposit ditambahkan pada tabel `assignments` dan `courses` melalui berkas migrasi:  
  `kampuslms-kelompok-02/database/migrations/2026_11_04_000001_add_performance_composite_indexes.php`.
  
  Kode indeks komposit pada tabel `assignments` (baris 23-25):
  ```php
  $table->index(['course_id', 'status', 'due_at'], 'assignments_course_status_due_idx');
  ```

* **Langkah Peragaan EXPLAIN ke Dosen:**  
  1. Buka database query console di VS Code (Database Client) atau jalankan tinker di terminal.
  2. Eksekusi query pengujian:
     ```sql
     EXPLAIN SELECT * FROM assignments WHERE course_id = 1 AND status = 'published' ORDER BY due_at ASC;
     ```
  3. Tunjukkan tabel hasilnya ke dosen:
     * Kolom **`key`**: Tertera `assignments_course_status_due_idx` (bukti valid bahwa indeks kita yang dipilih oleh query planner MySQL).
     * Kolom **`type`**: Bernilai **`ref`** (bukan `ALL`, membuktikan tidak ada pemindaian seluruh isi tabel / Full Table Scan).
     * Kolom **`rows`**: Bernilai **`3`** (database hanya memeriksa 3 baris data yang cocok).
     * Kolom **`Extra`**: Bersih dari `Using filesort` karena urutan kolom indeks sudah selaras dengan klausa `ORDER BY due_at`.

* **Kalimat ke Penguji:**  
  *"Ini indeks komposit course_id, status, dan due_at pada tabel assignments Pak/Bu. Saat kita uji dengan EXPLAIN, kolom key menampilkan indeks buatan kita dan kolom type bernilai ref dengan rows hanya 3, membuktikan bahwa database langsung menuju baris data yang dicari tanpa memindai seluruh tabel."*

---

### 5. Sebutkan satu data di aplikasi Anda yang tidak boleh di-cache. Kenapa?

* **Jawaban Berdasarkan Sistem:**  
  Data yang **DILARANG KERAS di-cache** adalah **Antrean Pengumpulan Tugas Menunggu Dinilai Dosen (`pendingSubmissions`)** dan **Status Pengumpulan Tugas Baru Mahasiswa (`myRecentSubmissions`)** pada halaman Dashboard.  
  *(Data transaksional lainnya yang juga haram di-cache adalah **Nilai Mahasiswa / Grade**).*

* **Alasan (Kenapa Tidak Boleh Di-Cache?):**  
  Data ini bersifat **transaksional langsung (*real-time*)** dan memiliki toleransi data basi nol (*zero stale tolerance*):
  1. **Bagi Dosen:** Ketika dosen selesai memberi nilai pada satu tugas mahasiswa, tugas tersebut harus **detik itu juga langsung hilang dari antrean**. Jika di-cache (misal 10 menit), tugas yang sudah dinilai akan tetap muncul di layar dosen dan berisiko dinilai dua kali (*double grading*).
  2. **Bagi Mahasiswa:** Mahasiswa yang baru saja mengunggah berkas tugas butuh kepastian instan bahwa tugasnya sudah terkirim. Jika di-cache, status di layar mahasiswa masih bertuliskan *"Belum Mengumpulkan"*, sehingga mahasiswa panik mengira sistem gagal dan mengunggah berkas berulang-ulang.

* **Bukti Perbedaan Kodingan di Aplikasi Kita:**  
  Di file `kampuslms-kelompok-02/app/Http/Controllers/DashboardController.php` baris 78–92:
  * **Yang BOLEH di-cache:** Ringkasan metrik statistik (`metrics`) dibungkus `Cache::remember("dashboard.dosen.{$user->id}.metrics", now()->addMinutes(10), ...)` dan dipasangi pembersihan otomatis (`Cache::forget`) di model `Assignment.php`.
  * **Yang TIDAK BOLEH di-cache:** Baris 78–83, query `$pendingSubmissions` **sengaja dibiarkan query biasa langsung ke MySQL tanpa dibungkus fungsi Cache**:
    ```php
    // Baris 78-83: SENGAJA tidak di-cache agar antrean penilaian selalu real-time
    $pendingSubmissions = Submission::whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))
        ->doesntHave('grade')
        ->with(['assignment.course', 'student'])
        ->latest()
        ->take(8)
        ->get();
    ```

* **Kalimat ke Penguji:**  
  *"Data yang tidak boleh di-cache di aplikasi kami adalah antrean pengumpulan tugas menunggu dinilai dosen (pendingSubmissions) Pak/Bu. Alasannya karena data ini bersifat real-time transaksional. Begitu dosen selesai memberi nilai, tugas itu harus langsung hilang detik itu juga dari antrean agar tidak dinilai dua kali. Karena itu di DashboardController, ringkasan statistik kami cache, tetapi antrean tugas sengaja kami biarkan query fresh ke database."*

---

### 6. Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting minggu depan?

* **Jawaban Sederhana & Tepat:**  
  * **Apa yang Terjadi:** Setelah perintah `php artisan config:cache` dijalankan, Laravel memuat seluruh berkas konfigurasi ke dalam satu file cepat (`bootstrap/cache/config.php`), lalu **sengaja menonaktifkan pembacaan file `.env`**. Akibatnya, seluruh pemanggilan fungsi `env('NAMA_KEY')` di luar folder `config/` (seperti di Controller, Model, atau View Blade) akan **selalu menghasilkan nilai `null`**!
  * **Kenapa Penting untuk Minggu 12 (Deployment / Server Produksi):**  
    Di server produksi minggu depan, perintah `php artisan config:cache` adalah **prosedur wajib standar** untuk mempercepat loading server. Jika developer masih menulis `env()` di controller, aplikasinya akan berjalan normal di laptop lokal, tetapi akan **langsung crash fatal / error 500 di server produksi** karena konfigurasinya berubah menjadi `null`. Aturannya: fungsi `env()` hanya boleh ditulis di dalam folder `config/*.php`, sedangkan di controller dan view wajib menggunakan fungsi `config('app.name')`.

* **Cara Memperagakannya ke Dosen (Langkah Nyata di Terminal):**  
  1. Jalankan perintah cache konfigurasi di terminal:
     ```powershell
     php artisan config:cache
     ```
  2. Uji pemanggilan `env()`:
     ```powershell
     php artisan tinker --execute="dump(env('APP_NAME'));"
     ```
     *Tunjukkan ke dosen di layar: hasilnya pasti bernilai **`null`**!*
  3. Uji pemanggilan `config()`:
     ```powershell
     php artisan tinker --execute="dump(config('app.name'));"
     ```
     *Tunjukkan ke dosen: nilainya tetap terbaca dengan benar (**`'Laravel'`**).*
  4. Bersihkan kembali cache konfigurasi lokal:
     ```powershell
     php artisan config:clear
     ```

* **Kalimat ke Penguji:**  
  *"Setelah config:cache dijalankan, pemanggilan fungsi env() di luar folder config akan selalu menghasilkan null karena Laravel mengunci environment ke satu file cache. Ini materi kritis untuk deployment server produksi minggu depan agar aplikasi tidak rusak gara-gara controller yang memanggil env() secara langsung."*

---
*Catatan praktikum ini disusun oleh Armansyah (10241013) diselaraskan dengan Modul 11 Pemrograman Web dan implementasi riil KampusLMS Kelompok 02.*
