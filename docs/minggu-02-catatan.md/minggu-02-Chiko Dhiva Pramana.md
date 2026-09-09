# Catatan Praktikum Minggu 2

**Nama:** Chiko Dhiva Pramana
**NIM:** 10241017

## Bagian READ: Telusuri Satu Request Penuh

Pada bagian ini, saya menelusuri alur kerja rute `/tentang` yang telah dibuat pada tugas Minggu 1:

**1. Baris penangkap rute di `routes/web.php`**
Rute `/tentang` ditangkap di file `routes/web.php` pada **baris 8 sampai 10** (pada kode minggu lalu):

```php
Route::get('/tentang', function () {
    return view('tentang');
});
```

Baris ini bertugas menerima request HTTP dengan method `GET` pada URL `/tentang` dan langsung memanggil view yang bersangkutan.
_(Catatan: Di Minggu 1 rute ini belum diberi penamaan `->name('tentang')`, penamaan rute baru ditambahkan di Minggu 2 saat merapikan tautan navigasi)._

**2. Berkas dan method controller yang menangani**
Untuk rute `/tentang` **tidak ada berkas maupun method controller yang menangani**.
Alasannya:

- Pada Minggu 1, materi controller memang belum diajarkan.
- Penanganan rute ini langsung diselesaikan di file `routes/web.php` menggunakan fungsi anonim (_Closure_):
    ```php
    function () {
        return view('tentang');
    }
    ```
- Secara arsitektur, halaman statis sederhana seperti `/tentang` memang tidak wajib dibuatkan controller terpisah agar tidak _over-engineering_ (kecuali jika ke depannya data profil kelompok diambil dari database). Namun seandainya dialihkan ke controller, berkas yang dibuat adalah `app/Http/Controllers/AboutController.php` dengan method `index()`.

**3. View yang dikembalikan dan lokasi persisnya**
View yang dipanggil oleh perintah `return view('tentang');` adalah berkas bernama **`tentang`**.
Lokasi path persisnya berada di:
`resources/views/tentang.blade.php`

**4. Layout apa yang membungkusnya?**
Pada implementasi Minggu 1 lalu, **sama sekali belum ada layout yang membungkusnya**.
File `resources/views/tentang.blade.php` saat itu masih berupa halaman HTML mandiri yang berdiri sendiri (berisi tag `<!DOCTYPE html>`, `<html>`, tag `<style>` internal, dan daftar anggota kelompok).
Konsep layout bersama baru diperkenalkan di Minggu 2 ini menggunakan komponen Blade `<x-layout>`.

**5. Hasil eksekusi `php artisan route:list --path=tentang`**
Saat perintah `php artisan route:list --path=tentang` dijalankan di terminal, outputnya:

```text
GET|HEAD   tentang .................................... tentang › routes/web.php:9
```

Hasil terminal ini **sangat cocok** dengan analisis di atas:

- **Method:** `GET|HEAD` (karena didefinisikan lewat `Route::get`).
- **URI:** `tentang` (URL yang didaftarkan).
- **Action:** Menunjuk langsung ke `routes/web.php` (menegaskan bahwa penanganannya memang berupa fungsi _Closure_ langsung di dalam file rute, bukan melalui class controller terpisah).

## Bagian BREAK: Delapan Kerusakan

1. **Prediksi:** Akan error karena `Route::post` berbeda dengan `Route::get`.
   * **Sebenarnya:** Muncul error `405 | Method Not Allowed` (*The GET method is not supported for route courses. Supported methods: POST*).
   * **Alasan:** Browser secara otomatis selalu mengirim request bertipe `GET` saat kita membuka URL langsung atau mengklik tautan link (`<a>`). Karena rutenya diubah menjadi `POST`, Laravel menolak request tersebut karena tipe method HTTP yang diminta tidak cocok.

2. **Prediksi:** Akan error karena view tidak ditemukan.
   * **Sebenarnya:** Muncul error `InvalidArgumentException: View [courses.index_salah] not found`.
   * **Alasan:** Fungsi `view()` di controller mencari berkas `.blade.php` di dalam folder `resources/views/`. Jika nama file-nya salah atau tidak ada, Laravel tidak bisa merender halaman dan langsung memunculkan error *view not found*.

3. **Prediksi:** Akan error karena nama rutenya tidak ada.
   * **Sebenarnya:** Muncul error `RouteNotFoundException: Route [courses.show] not defined` *(atau syntax error jika tanda `;` ikut terhapus)*.
   * **Alasan:** Di view `courses/index.blade.php`, tombol Detail memanggil link via `route('courses.show', ...)`. Fungsi `route()` mencari rute berdasarkan namanya. Ketika namanya dihapus dari `web.php`, Laravel gagal mengenali rute tersebut dan membatalkan pembuatan URL.

4. **Prediksi:** Akan error not found karena Laravel membaca rute dari atas ke bawah.
   * **Sebenarnya:** Muncul respon `404 | Not Found`.
   * **Alasan:** Laravel membaca rute berurutan dari atas ke bawah. Saat rute dinamis `/courses/{course}` berada di atas `/courses/create`, kata `"create"` dianggap sebagai parameter ID mata kuliah (`{course}` = "create") dan diarahkan ke method `show()`. Karena tidak ada mata kuliah ber-ID "create", Laravel mengembalikan error 404.

5. **Prediksi:** Tulisan yang dirubah akan memicu eksekusi kode berbahaya di layar.
   * **Sebenarnya:** Muncul kotak dialog pop-up *alert* bertuliskan `XSS`.
   * **Alasan:** Sintaks `{{ }}` otomatis menyaring (*escape*) kode HTML/JS menjadi teks biasa yang aman. Sedangkan sintaks `{!! !!}` mencetak data mentah tanpa penyaringan, sehingga kode `<script>` JavaScript langsung dieksekusi oleh browser (terjadi celah keamanan XSS).

6. **Prediksi:** Seluruh desain akan hilang karena CSS gagal dimuat.
   * **Sebenarnya:** Tampilan halaman web menjadi polos/rusak tanpa gaya (*unstyled HTML*).
   * **Alasan:** Direktif `@vite(...)` bertugas menyuntikkan file `app.css` ke halaman. Saat baris ini dihapus, browser tidak memuat file CSS sama sekali, sehingga halaman hanya menampilkan teks dan tabel mentah bawaan browser.

7. **Prediksi:** Akan error jika Vite dev server tidak berjalan.
   * **Sebenarnya:** Muncul error `Vite manifest not found at: .../public/build/manifest.json`.
   * **Alasan:** Dalam tahap pengembangan (*development*), aset disuplai secara langsung oleh server `npm run dev`. Saat server dimatikan dan proyek belum pernah dikompilasi permanen dengan `npm run build`, Laravel tidak menemukan sumber file CSS/JS sehingga melempar error manifest not found.

8. **Prediksi:** Akan error karena parameter rute yang dibutuhkan tidak dikirim.
   * **Sebenarnya:** Muncul error `UrlGenerationException: Missing required parameter for [Route: courses.show] [URI: courses/{course}] [Missing parameter: course]`.
   * **Alasan:** Rute `courses.show` memiliki parameter wajib di URL-nya yaitu `{course}` (misalnya ID mata kuliah). Jika dipanggil tanpa parameter, helper `route()` tidak tahu harus membuat link ke ID nomor berapa, sehingga Laravel membatalkan proses pembuatan URL.

## Checkpoint Minggu 2:

1. **Kenapa menghapus data lewat `GET` berbahaya?**
   Karena method `GET` itu cuma buat nampilin atau ngambil data, bukan buat ngubah atau ngehapus isi database. 
   **Skenario bahayanya:** Kalau kita bikin tombol hapus pakai link `GET /courses/5/delete`, bot mesin pencari (kayak Google) atau fitur auto-preload browser bakal otomatis ngebuka semua link yang ada di halaman web. Akibatnya, data mata kuliah bisa terhapus sendiri tanpa ada orang yang beneran ngeklik tombol hapus. Makanya operasi hapus wajib pakai method `DELETE` atau `POST` yang dibungkus form dan token CSRF.

2. **Apa yang terjadi kalau `/courses/{course}` ditaruh sebelum `/courses/create`?**
   Halaman buat nambah mata kuliah (`/courses/create`) nggak bakal pernah bisa kebuka dan malah muncul error `404 Not Found`.
   **Alasannya:** Laravel ngebaca rute dari atas ke bawah. Karena rute dinamis `{course}` ditaruh duluan di atas, Laravel bakal ngira kata `"create"` itu adalah ID mata kuliah (bukan halaman form tambah). Request-nya malah dilempar ke fungsi detail (`show`), dan karena nggak ada mata kuliah yang ID-nya bernama "create", web kita langsung ngeluarin error 404.

3. **Contoh pemakaian `route()` di kode dan keuntungannya dibanding URL hardcode:**
   Contoh di file `resources/views/courses/index.blade.php`:
   ```blade
   <a href="{{ route('courses.show', $course['id']) }}">Detail</a>
   ```
   **Keuntungannya:** Kalau suatu saat kita mau ganti URL di `routes/web.php` (misalnya dari `/courses` diganti jadi `/mata-kuliah`), kita cukup ubah 1 baris saja di file `web.php`. Semua tombol dan link di halaman web bakal otomatis ikut berubah sendiri tanpa perlu kita edit manual satu per satu di file view.

4. **Beda `{{ }}` dan `{!! !!}`, serta bukti XSS di bagian BREAK:**
   - **`{{ }}` (Aman):** Laravel otomatis ngamanin teks yang kita tampilin. Karakter berbahaya kayak `<` dan `>` bakal diubah jadi teks biasa, jadi nggak bakal dieksekusi sebagai kode.
   - **`{!! !!}` (Bahaya):** Menampilkan data mentah apa adanya ke layar tanpa disaring.
   - **Bukti di BREAK 5:** Pas nama mata kuliah diisi `<script>alert('XSS')</script>`, kalau dicetak pakai `{{ }}` cuma muncul tulisan teks biasa di layar. Tapi pas diganti pakai `{!! !!}`, script-nya beneran jalan di browser dan langsung muncul pop-up alert bertuliskan "XSS".

5. **Fungsi `@vite` dan beda `npm run dev` vs `npm run build`:**
   - **Fungsi `@vite`:** Buat nyambungin tampilan web kita ke file CSS dan JavaScript, sekaligus bikin halaman bisa auto-refresh pas kita lagi ngoding.
   - **Beda dev vs build:**
     - `npm run dev`: Dipakai pas kita lagi ngoding (tahap development). CSS dilayani langsung dari memori komputer dan terminalnya harus tetap dibiarin menyala.
     - `npm run build`: Dipakai kalau web kita sudah jadi dan siap di-onlinekan (tahap produksi). File CSS dan JS bakal dikompres jadi file permanen di folder `public/build`, jadi web kita tetap jalan rapi tanpa perlu terminal menyala lagi.

6. **Kenapa data dari `Request` tidak boleh dipercaya?**
   Karena data dari `Request` itu asalnya dari browser pengguna. Kita nggak bisa ngontrol apa yang dilakukan pengguna di layarnya. Pengguna bisa aja iseng ngubah isi form, ngotak-ngatik ID di URL lewat inspect element, atau ngirim data aneh lewat Postman buat ngerusak database. Makanya backend server wajib selalu ngecek dan memvalidasi ulang semua data yang masuk sebelum diproses.


