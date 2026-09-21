# Catatan Praktikum Minggu 4

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Menelusuri Siklus Form Gagal

Pada tahap ini, saya mencoba mengisi form tambah mata kuliah (`/courses/create`) dengan data yang sengaja dibuat tidak valid, yaitu mengisi **SKS = 99** (melebihi batas maksimal 6 SKS), lalu mengklik tombol Simpan.

Berikut hasil penelusuran dan jawaban dari 6 pertanyaan pengamatan alur:

### 1. Method apa yang menerima request? Di controller mana?
Request POST dari form dikirim ke URL `/courses` dan diterima oleh method **`store()`** di dalam controller **`App\Http\Controllers\CourseController`**.

### 2. Di titik mana persisnya validasi terjadi: sebelum atau sesudah baris pertama method controller?
Validasi terjadi **sebelum** baris pertama kode di dalam method controller dijalankan.  
*Alasannya:* Parameter pada method `store(StoreCourseRequest $request)` menggunakan type-hinting kelas Form Request. Service container Laravel secara otomatis memvalidasi data request saat proses dependency injection sebelum memanggil method `store()`. Karena SKS = 99 melanggar aturan `between:1,6`, Laravel langsung membatalkan eksekusi controller dan melempar `ValidationException` untuk me-redirect balik. Jadi, baris kode pertama di dalam `store()` sama sekali tidak sempat dieksekusi.

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
Laravel me-redirect kembali ke halaman asal form, yaitu **`/courses/create`**.  
Yang menentukan tujuannya adalah handler exception validasi bawaan Laravel (`ValidationException`). Laravel membaca header HTTP `Referer` dari request browser atau membaca riwayat URL sebelumnya (`url()->previous()`), lalu otomatis mengeksekusi `redirect()->back()`.

### 4. Dari mana `@error('sks')` mengambil pesannya?
Direktif `@error('sks')` mengambil pesan error dari variabel global **`$errors`** (View Error Bag).  
Variabel `$errors` ini disediakan oleh middleware bawaan Laravel (`ShareErrorsFromSession`) yang membaca data error dari **Flash Session** hasil redirect validasi gagal tadi. Variabel `$message` di dalam blok `@error` berisi pesan kustom yang sudah kita buat: *"Beban SKS harus antara 1 sampai 6."*

### 5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?
Helper `old('sks')` mengambil nilainya dari **Flash Session** pada key internal `_old_input`.  
Ketika validasi gagal, Laravel otomatis memanggil method `withInput()` yang mem-flash input form ke session. Nilai ini hanya bertahan selama **1 request berikutnya saja**. Begitu halaman selesai dirender atau pengguna me-refresh halaman (F5), data flash session tersebut langsung terhapus otomatis dari server.

### 6. Cookie Session di DevTools
Ketika saya membuka browser, klik kanan **Inspect Element -> tab Application -> Storage -> Cookies -> http://127.0.0.1:8000**, cookie session yang ditemukan bernama:
* **`laravel_session`** : Cookie yang menyimpan ID sesi terenkripsi milik pengguna agar server mengenali request antar-halaman (contoh nilai saat diuji di DevTools: `eyJpdGls...`).
* *(Tambahan)* **`XSRF-TOKEN`** : Cookie yang menyimpan token CSRF terenkripsi untuk keamanan form.
