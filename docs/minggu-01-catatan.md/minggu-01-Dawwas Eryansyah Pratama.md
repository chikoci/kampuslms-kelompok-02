# Minggu 01 

**Nama:** Dawwas Eryansyah Pratama  
**NIM:** 10241019 

---

## 1. READ

1. Buka public/index.php. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.

= public/index.php adalah file yang menjadi pintu masuk utama ketika aplikasi Laravel menerima request dari browser. File ini mengecek apakah aplikasi sedang dalam mode maintenance, memuat file autoload dari Composer, lalu menjalankan aplikasi melalui bootstrap/app.php. Setelah itu, request dari browser ditangkap dan diproses oleh Laravel sampai menghasilkan response.

2. Buka bootstrap/app.php. Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.

= Pada file bootstrap/app.php, terdapat pengaturan utama untuk menjalankan aplikasi Laravel. Bagian withRouting() digunakan untuk mengatur route, seperti routes/web.php, sedangkan withMiddleware() digunakan untuk mengatur middleware dan withExceptions() digunakan untuk mengatur penanganan error atau exception. Jadi, file ini bisa dibilang sebagai tempat konfigurasi awal aplikasi Laravel sebelum aplikasi dijalankan.

3. Buka routes/web.php. Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang browser, pastikan berubah.

= Pada file routes/web.php, terdapat route untuk halaman utama website. Route tersebut menggunakan Route::get('/'), artinya ketika alamat utama website dibuka, Laravel akan menjalankan kode tersebut dan menampilkan view welcome. Saya kemudian mengubah teks pada halaman tersebut dan melakukan reload pada browser untuk memastikan perubahan berhasil.

4. Jalankan php artisan route:list. Cocokkan keluarannya dengan isi routes/web.php.

= Perintah php artisan route:list digunakan untuk melihat daftar route yang terdapat pada aplikasi Laravel. Setelah dijalankan, route / yang terdapat pada routes/web.php muncul dalam daftar sehingga dapat diketahui bahwa route tersebut sudah terdaftar dan dapat digunakan oleh aplikasi.

