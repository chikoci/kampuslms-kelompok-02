# Penelusuran Siklus Form Gagal (Minggu 4 - Tahap READ)

**Pencatat:** Chiko Dhiva Pramana (10241017)  
**Metode:** Pengujian manual langsung pada form tambah mata kuliah dengan input SKS = 99.

---

## 6 Pertanyaan & Temuan Pengujian Manual

### 1. Method apa yang menerima request? Di controller mana?
Request POST dari form dikirim ke route `/courses` dan diterima oleh method **`store()`** pada controller **`App\Http\Controllers\CourseController`**.

### 2. Di titik mana persisnya validasi terjadi: sebelum atau sesudah baris pertama method controller?
Validasi terjadi **sebelum** baris pertama kode di dalam method controller dijalankan.  
Pada deklarasi method `public function store(StoreCourseRequest $request)`, Laravel menggunakan dependency injection untuk menginisialisasi `StoreCourseRequest`. Begitu diinisialisasi, method `validateResolved()` langsung memverifikasi input terhadap rules. Karena nilai SKS = 99 melanggar aturan `between:1,6`, Laravel langsung memicu `ValidationException` dan me-redirect request kembali ke form. Akibatnya, baris kode pertama di dalam `store()` sama sekali tidak sempat dieksekusi.

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
Laravel me-redirect pengguna kembali ke halaman form sebelumnya, yaitu **`/courses/create`**.  
Yang menentukan tujuan redirect ini adalah mekanisme exception handler bawaan Laravel pada `ValidationException`. Laravel membaca header HTTP `Referer` dari request atau membaca riwayat sesi URL sebelumnya (`url()->previous()`), lalu otomatis memicu `redirect()->back()`.

### 4. Dari mana `@error('sks')` mengambil pesannya?
Direktif `@error('sks')` mengambil pesan kesalahan dari variabel **`$errors`** (instance `ViewErrorBag`).  
Variabel `$errors` ini dibagikan secara otomatis ke seluruh view Blade oleh middleware bawaan `Illuminate\View\Middleware\ShareErrorsFromSession`. Sumber pesannya berasal dari **Flash Session** (key default `errors`) yang dikirimkan saat redirect form gagal.

### 5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?
Fungsi helper `old('sks')` mengambil nilai input dari **Flash Session** pada key `_old_input`.  
Ketika validasi gagal, Laravel secara otomatis memanggil method `withInput()` yang mem-flash seluruh data isian form ke session. Nilai ini hanya bertahan selama **1 request berikutnya**. Begitu halaman selesai dimuat atau pengguna melakukan refresh (F5), data flash session `_old_input` langsung dihapus otomatis dari server.

### 6. Nama Cookie Session di DevTools
Melalui inspect element browser pada menu **Application -> Cookies -> http://127.0.0.1:8000**:
* **`laravel_session`** : Cookie yang menyimpan ID session unik pengguna di server (contoh nilai saat diuji di DevTools: `eyJpdGls...`).
* **`XSRF-TOKEN`** : Cookie token CSRF untuk pengamanan request form.
