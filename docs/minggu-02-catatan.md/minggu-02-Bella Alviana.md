# Minggu 2
**NAMA:** Bella Alviana
**NIM:** 10241015

## READ

**1. Baris mana di `routes/web.php yang` menangkapnya?**

Jawab: Route */tentang* ditangkap oleh route yang mengarah ke halaman tentang di kode ini: `Route::get('/tentang', [TentangController::class, 'index']) ->name('tentang');`

**2. Kalau ditangani controller, berkas dan method mana?**

Jawab: jika route menggunakan controller, request */tentang* akan diteruskan ke controller yang berada di folder `app\Http\Controllers\TentangController.php**`,method yang dipanggil adalah method yang ditentukan pada route yaitu index() dan didalam method tersebut terdapat: `return view('tentang');`

**3. View mana yang dikembalikan? Di path apa persisnya?**

Jawab: View yang dikembalikan adalah:`"resources/views/tentang.blade.php"` Hal ini ditunjukkan oleh kode: `return view('tentang');` Laravel akan mencari file tentang.blade. php di dalam folder resources/views.

**4. Layout apa yang membungkusnya?** 

Jawab: View tentang.blade.php menggunakan komponen: `<x-layout title="Tentang Kami - Kelompok 02">` sehingga halaman tersebut dibungkus oleh layout: `resources/views/components/layout.blade.php` Isi halaman `tentang.blade.php` ditempatkan pada bagian *{{ $slot }}* di dalam layout tersebut.

**5. Jalankan `php artisan route:list --path=tentang`. Cocok dengan analisis Anda?**

Jawab: jika menjalankan perintah tersebut di tampilan terminal akan menampilkan informasi `GET|HEAD       tentang ............................................................................... routes/web.php:9` Hasil tersebut sesuai dengan analisis saya karena menunjukkan bahwa route */tentang* menggunakan method GET dan didefinisikan pada file `routes/web.php` baris ke-9. Output tersebut bukan error, melainkan informasi bahwa route */tentang* berhasil terdaftar.

## BREAK

**1. Ubah `Route::get` menjadi `Route::post` pada route daftar mata kuliah.** 

*Prediksi saya*: menurut saya, tampilan website gak bisa dibuka atau erorr.

Jawab: Setelah `Route::get` diubah menjadi `Route::post`, halaman browser menampilkan `500 server eror` karena browser mengirimkan GET request bukan POST.

**2. Ubah nama view di `return view(...)` menjadi yang tidak ada.**
*Prediksi saya*: Menurut saya tampilan website mungkin eror karena laravel gak menemukan file view yang dipanggil.

Jawab: jika kita mengubah `return view (..)` di `app\Http\Controllers\CourseController.php` laravel akan menampilkan erorr `View [bella] not found.` karena controller mencoba memanggil view yang tidak tersedia di folder `resources/views`. Jadi nama pada `return view ()` itu harus sesuai dengan lokasi file blade yang sebenarnya.

**3. Hapus `->name('courses.show')`, lalu muat halaman yang memakai `route('courses.show')`.**

*Prediksi Saya*: mungkin akan eror karena menghapus `->name('courses.show')` jadinya laravel gatau rute yang mana yang dimaksud.

Jawab: Jika memakai contoh yang sudah menjadi mata kuliah maka kita akan menghapus `->name('mata-kuliah.show');` di folder `routes/we.php` akan memunculkan eror di browser yaitu `Route [mata-kuliah.show] not defined.` Hal ini terjadi karena view masih memanggil nama route yang sudah tidak terdaftar dan percobaan ini menunjukkan bahwa `route()` membutuhkan nama route yang sesuai dengan route yang telah didaftarkan.

**4. Pindahkan `/courses/{course}` ke ATAS `/courses/create`, lalu buka `/courses/create`**

*Prediksi saya*: mungkin file gak bisa dibaca oleh laravel jadinya gak keliatan outputnya di tampilannya>

Jawab: aku memakai susunan mata kuliah yang dimana aku menambahkan fungsi *create* di folder `app\Http\Controllers\CourseController.php` dan juga di folder `routes\web.php` nah setelah kutukar susunanya `/mata-kuliah/{mata_kuliah}` ke ATAS `/mata-kuliah/create` maka akan menampilkan `404 Not found`

**5. Ganti `{{ $nama }}` menjadi `{!! $nama !!}`, isi `$nama` dengan `<script>alert('XSS')</script>`**

*Prediksi Saya*: jika dijalankan menurut saya akan muncul tampilan XSS.

jawab: Setelah `{{ $course['name'] }}` diganti menjadi `{!! $course['name'] !!}` dan *nilai nama mata kuliah* diisi dengan `<script>alert('XSS')</script>`, muncul pop-up bertuliskan `XSS` pada browser. Hal ini menunjukkan bahwa `{!! !!}` Menjalankan kode JavaScript secara langsung di halaman web tanpa diblokir oleh Laravel. Sebaliknya, `{{ }}` mengubah script menjadi teks biasa agar tidak bisa berjalan.

**6. Hapus `@vite(...)` dari layout**

*Prediksi saya*: menurut saya kalau itu dihapus tampilannya masih ada tapi untuk css nya berubah, karena @vite digunakan untuk menghubungkan file aset dengan laravel.

jawab: Sebelum `@vite(...)` dihapus, halaman */tentang* menampilkan styling yang lebih lengkap, seperti ukuran dan ketebalan judul yang lebih besar. Setelah `@vite(...)` dihapus, halaman tetap dapat diakses, tetapi sebagian styling berubah karena file CSS yang dikelola Vite tidak lagi dimuat. Hal ini menunjukkan bahwa `@vite` berfungsi untuk menghubungkan halaman Laravel dengan asset CSS dan JavaScript yang dikelola oleh Vite.

**7. Hentikan npm run dev lalu muat ulang halaman.**

*Prediksi saya*: menurut saya tampilannya gak akan muncul karena `npm run dev` dimatikan.

jawab: `Vite manifest not found at: D:\kampuslms-kelompok-02\public\build/manifest.json` pesan *vite manifest not found* terjadi karena laravel mencari file aset seperti CSS yang sudah dikomplikasi, tetapi file tersebut belum dibuat atau hilang. Saat perintah `npm run dev` dimatikan, server pengembangan lokal vite yang bertugas menyuplai file css secara otomatis ikut mati.

**8. Panggil `route('courses.show')` tanpa mengirim parameter**

*Prediksi saya*: menurut saya laravel akan memberikan eror.

Jawab: Saat `href="{{ route('mata-kuliah.show', $course['id']) }}` dihapus parameternya yaitu `$course['id']` , Laravel menampilkan error Missing yaitu `Missing required parameter for [Route: mata-kuliah.show] [URI: mata-kuliah/{mata_kuliah}] [Missing parameter: mata_kuliah`. `Route mata-kuliah.show` memiliki URI `mata-kuliah/{mata_kuliah}`, sehingga parameter *mata_kuliah* wajib diberikan ketika membuat URL. Percobaan ini menunjukkan bahwa route yang memiliki parameter wajib tidak dapat dipanggil tanpa parameter tersebut.