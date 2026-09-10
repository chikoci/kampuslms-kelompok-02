# Catatan Praktikum Minggu 1
**Nama:** Armansyah  
**NIM:** 10241013

## Bagian READ: Bedah Instalasi 
Ambil route /tentang yang Anda buat minggu lalu. Tanpa AI, tulis di catatan Anda:  

**1. Baris mana di routes/web.php yang menangkapnya?**  
Req `/tentang` ditangkap di baris `Route::get('/tentang', [ControllerTentang::class, 'index']);`Route tersebut menggunakan method GET dan mengarahkan request ke `ControllerTentang` pada method `index()`.  

**2. Kalau ditangani controller, berkas dan method mana?**  
Berkas dan method controller yang menangani**
Untuk rute `/tentang` **tidak ada berkas maupun method controller yang menangani**.
Alasannya:  
Penanganan rute ini langsung diselesaikan di file `routes/web.php` menggunakan fungsi anonim   (_Closure_):  
```php
function () { 
    return view('tentang'); 
}
```
Secara arsitektur, halaman statis sederhana seperti `/tentang` memang tidak wajib dibuatkan controller terpisah agar tidak _over-engineering_ (kecuali jika ke depannya data profil kelompok diambil dari database). Namun seandainya dialihkan ke controller, berkas yang dibuat adalah `app/Http/Controllers/AboutController.php` dengan method `index()`.


**3. View mana yang dikembalikan? Di path apa persisnya?**  
View yang dikembalikan adalah `view('tentang')`. path file tersebut adalah `resources/views/tentang.blade.php` view ini berisi tampilan halaman TentangKami - kelompok 02 dan daftar anggota kelompok.

**4. Layout apa yang membungkusnya?**  
Sebelumnya belum ada layout yang digunakan, sehingga `resources/views/tentang.blade.php` masih berwujud satu halaman HTML penuh. Isinya mencakup tag dasar seperti <!DOCTYPE html>, <html>, CSS internal, dan daftar nama kelompok. Sistem layout gabungan menggunakan komponen Blade `<x-layout>`. baru kita pelajari di Minggu 2 ini.

**5. Jalankan php artisan route:list --path=tentang. Cocok dengan analisis Anda?**   
```text
GET|HEAD   tentang .................................... tentang › routes/web.php:9
```

Hasil terminal ini **sangat cocok** dengan analisis di atas:

- **Method:** `GET|HEAD` (karena didefinisikan lewat `Route::get`).
- **URI:** `tentang` (URL yang didaftarkan).
- **Action:** Menunjuk langsung ke `routes/web.php` (menegaskan bahwa penanganannya memang berupa fungsi _Closure_ langsung di dalam file rute, bukan melalui class controller terpisah).

