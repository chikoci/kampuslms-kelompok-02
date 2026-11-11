### 1. Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa halaman itu?

* **Jawaban:**  
  Berdasarkan tabel pengukuran riil pada file `docs/performa.md`, halaman yang paling lambat dan memiliki jumlah query terbanyak sebelum optimisasi adalah **Halaman Detail Mata Kuliah (`/courses/1`)**.
  * Total Query: **18 query** (Debugbar menandai terdapat **3 duplicate queries**).
  * Waktu Render: **196.0 ms** (dengan waktu eksekusi database/SQL mencapai 45.27 ms).
  * Penggunaan Memori: **4 MB**.

* **Kenapa Halaman Ini Paling Buruk? (Bahasa Sederhana):**
  1. **Ada masalah N+1 saat menghitung jumlah pengumpulan tugas:**  
     Di dalam file tampilan `resources/views/courses/show.blade.php`, setiap baris tugas memanggil query hitung secara mandiri:
     ```blade
     {{ $assignment->submissions()->count() }} Pengumpulan
     ```
     Karena diletakkan di dalam perulangan `@foreach`, database MySQL dipaksa menjalankan query `SELECT COUNT(*)` berulang kali sebanyak tugas yang ada di kelas tersebut.
  2. **Ada query duplikat saat memeriksa izin tombol aksi (Policy):**  
     Ketika sistem memeriksa hak akses apakah tombol edit atau hapus materi dan tugas boleh tampil, Laravel mengambil data mata kuliah induk berulang kali (`select * from courses where id = '1'`) karena relasi induk belum dimuat lebih dulu (*lazy loading*).

* **Langkah Peragaan di Depan Penguji:**
  1. Buka file [**`docs/performa.md`**](file:///d:/kampuslms-kelompok-02/docs/performa.md) di editor VS Code, lalu tunjukkan baris nomor 1 pada tabel baseline: halaman Detail Mata Kuliah mencatat 18 query dengan 3 query duplikat.
  2. Buka browser dan akses halaman Detail Mata Kuliah (`http://127.0.0.1:8000/courses/1`) dengan akun Dosen.
  3. Tunjukkan ke penguji panel Laravel Debugbar di bawah layar: perlihatkan bahwa setelah diperbaiki menggunakan `withCount('submissions')` dan eager loading `materials.course`, jumlah query turun drastis dan bebas dari query duplikat.
---

### 2. Peragakan N+1 di aplikasi Anda: Matikan `with()` pada `SubmissionController` dan tunjukkan lonjakan query di Debugbar.
* **Jawaban:**  
  Masalah N+1 adalah kondisi ketika Laravel menjalankan query database secara berulang untuk mengambil data relasi.
* **Langkah Peragaan pada `SubmissionController.php`:**
  1. Buka file `app/Http/Controllers/SubmissionController.php` pada baris 37 di method `index()`:
     ```php
     // Kondisi Asal (Aman dengan Eager Loading):
     $submissions = $assignment->submissions()->with(['student', 'grade'])->get()->keyBy('user_id');
     ```
  2. Buka halaman daftar pengumpulan tugas di browser sebagai Dosen. Tunjukkan ke penguji panel Debugbar di bawah layar: query hanya berjumlah sedikit (**sekitar 3–4 query**).
  3. Ubah sementara kode di `SubmissionController.php` dengan mematikan `with(['student', 'grade'])`:
     ```php
     // Kondisi N+1 (with dimatikan):
     $submissions = $assignment->submissions()->get()->keyBy('user_id');
     ```
   4. matikan lazy di "app\Providers\AppServiceProvider.php" 
  4. Refresh halaman di browser.
  5. Tunjukkan ke penguji: **Jumlah query di Debugbar langsung melonjak drastis**, dan tab *Queries* dipenuhi query berulang untuk mengambil data siswa dan nilai di setiap barisnya.
  6. Kembalikan kodenya ke kondisi semula.

Alasan lonjakan query terjadi adalah karena `with()` berfungsi untuk mengambil data relasi sekaligus (eager loading), sehingga Laravel tidak perlu menjalankan query berulang kali untuk setiap data.

Ketika `with('lecturer')` dimatikan, Laravel mengambil data dosen secara terpisah setiap kali relasi dosen diakses pada masing-masing mata kuliah. Akibatnya, jumlah query bertambah dan beberapa query yang sama muncul berulang kali.
---

### 3. Apa beda `withCount('students')` dan `count($course->students)`? Kenapa yang kedua boros?

* **Jawaban:**
  Perbedaannya terletak pada **tempat pemrosesan hitungan** dan **beban data yang dikirim**:

  1. **`withCount('students')` (Hemat & Cepat):**
     * Proses menghitung jumlah mahasiswa langsung dilakukan oleh database MySQL menggunakan fungsi agregasi SQL: `SELECT COUNT(*)`.
     * Misalnya dalam satu mata kuliah terdapat 30 mahasiswa, database cukup menghitung dan mengirimkan satu angka integer `30` ke Laravel tanpa perlu mengambil seluruh data mahasiswa.
     * Hasilnya otomatis disimpan ke dalam atribut `$course->students_count` tanpa membebani memori RAM server.

  2. **`count($course->students)` (Boros & Lambat):**
     * Laravel memerintahkan database untuk mengambil **seluruh baris dan seluruh kolom** data mahasiswa terlebih dahulu (`SELECT * FROM users...`), seperti `id`, `name`, `email`, password hash, hingga tanggal dibuat.
     * Seluruh data tersebut dikirim lewat jaringan dan dimuat ke dalam memori RAM PHP untuk diubah menjadi 30 objek model `User` lengkap (*hidrasi model*), baru kemudian dihitung jumlahnya menggunakan fungsi `count()` di PHP.

* **Kenapa yang Kedua (`count`) Sangat Boros?**
  * **Boros Memori (RAM):** Semakin banyak data mahasiswa (misalnya 300 atau 1.000 mahasiswa), semakin besar pula memori RAM server yang tersedot untuk menyimpan ratusan objek model lengkap, padahal yang dibutuhkan di layar hanyalah sebuah angka.
  * **Waktu Proses CPU Lebih Lama:** Mengubah ratusan data mentah dari database menjadi objek model di PHP memakan waktu proses komputasi yang tidak perlu.
  * **Risiko Terjadi Query N+1:** Jika `count($course->students)` dipanggil di dalam tabel perulangan `@foreach`, database akan berulang kali menarik seluruh data mahasiswa untuk setiap mata kuliah yang ditampilkan di halaman.

* **Analogi Sederhana untuk Pemula:**
  * *Bayangkan Anda ingin tahu berapa jumlah buku di dalam sebuah kardus tertutup:*
    * **Cara `withCount`:** Anda cukup bertanya kepada petugas gudang *"Ada berapa buku di dalam kardus itu?"*, petugas langsung menjawab *"Ada 30 buku"*. Sangat cepat dan tidak menghabiskan tempat di meja Anda.
    * **Cara `count(...)`:** Anda membuka kardus tersebut, mengeluarkan seluruh 30 buku ke atas meja satu per satu, menumpuknya hingga meja penuh sesak (boros RAM), baru kemudian menghitungnya dengan jari *"1, 2, 3... 30"*. Meja Anda menjadi penuh dan Anda kelelahan, padahal yang Anda butuhkan hanyalah angkanya saja.
---

### 4. Tunjukkan index yang Anda tambahkan. Jalankan `EXPLAIN` dan buktikan ia terpakai.
* **Jawaban:**  
  Index berfungsi seperti **daftar isi pada buku**. Tanpa index, database terpaksa membaca buku dari halaman 1 sampai halaman terakhir (*Full Table Scan* / tipe `ALL`). Dengan index, database langsung melompat ke baris yang dicari (*tipe `ref`*).
* **Langkah Peragaan:**
  1. Buka terminal dan masuk ke Tinker:
     ```powershell
     php artisan tinker
     ```
  2. Jalankan perintah analisis `EXPLAIN`:
     ```php
     DB::select("EXPLAIN SELECT * FROM assignments WHERE course_id = 1 ORDER BY due_at");
     ```
  3. Tunjukkan hasilnya ke dosen:
     * **Kolom `type` bertuliskan `ref`** (artinya database membaca lewat index, bukan memindai seluruh tabel).
     * **Kolom `key`** memuat nama index: `assignments_course_id_foreign`.

---

### 5. Sebutkan satu data di aplikasi Anda yang tidak boleh di-cache. Kenapa?

- **Data yang Tidak Boleh Di-Cache:**  
  Daftar tugas yang baru dikumpulkan mahasiswa (`myRecentSubmissions`) dan antrean tugas yang belum dinilai dosen (`pendingSubmissions`) di halaman Dashboard.

- **Lokasi Kode di Aplikasi:**  
  Di file `app/Http/Controllers/DashboardController.php` baris 78-83:
  ```php
  // Baris 78-83: Data ini SENGAJA dibiarkan query biasa dan tidak dibungkus Cache::remember
  $pendingSubmissions = Submission::whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))
      ->doesntHave('grade')
      ->with(['assignment.course', 'student'])
      ->latest()
      ->take(8)
      ->get();
  ```

- **Kenapa Tidak Boleh Di-Cache? (Contoh Kasus Riil):**
  - Data ini bersifat transaksi langsung (real-time).
  - Mahasiswa: Begitu mahasiswa mengunggah file tugas, mereka langsung me-refresh dashboard untuk memastikan statusnya berubah menjadi "Sudah Dikumpulkan". Jika data ini di-cache (misalnya 10 menit), statusnya akan tetap "Belum Mengumpulkan". Mahasiswa akan panik mengira file tugasnya gagal terkirim dan mencoba upload berulang kali.
  - Dosen: Begitu dosen selesai memberi nilai pada satu tugas, tugas itu harus langsung hilang dari antrean penilaian. Jika di-cache, tugas yang sudah dinilai akan tetap muncul di layar dan berisiko dinilai dua kali.
---

### 6. Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting minggu depan?

- **Apa yang Terjadi?**  
  Ketika perintah `php artisan config:cache` dijalankan di terminal:
  1. Laravel menggabungkan seluruh file di folder `config/` menjadi satu file cepat di `bootstrap/cache/config.php`.
  2. Setelah file cache dibuat, Laravel **sengaja mematikan pembacaan file `.env`**.
  3. Akibatnya, semua pemanggilan fungsi `env('NAMA_KEY')` di luar folder `config/` (seperti di Controller atau View Blade) akan **selalu menghasilkan nilai `null`**.

- **Kenapa Ini Penting Minggu Depan (Materi 12 - Deployment / Server Produksi)?**
  - Di server produksi asli, menjalankan perintah `php artisan config:cache` adalah kewajiban standar agar loading website menjadi cepat.
  - Jika kita masih menulis `env()` di dalam Controller atau View, aplikasi akan langsung rusak dan memunculkan error saat di server produksi karena nilainya berubah menjadi `null` (misalnya link error, email gagal, nama aplikasi kosong).
  - Oleh karena itu, seluruh nilai `.env` wajib dibungkus di dalam folder `config/*.php` (misalnya `config/app.php`), lalu dipanggil di kode menggunakan fungsi `config('app.name')`.