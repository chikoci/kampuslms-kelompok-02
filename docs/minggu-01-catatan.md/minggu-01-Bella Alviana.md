# Minggu 1
**Nama:** Bella Alviana
**NIM:** 10231014

## Read

**1.Buka `public/index.php`. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.**

Jawab:Berkas `public/index.php` ini berfungsi sebagai pintu masuk tunggal yang menerima seluruh permintaan pengunjung web untuk diarahkan ke dalam sistem framework Laravel. Melalui kode di dalamnya, berkas ini secara otomatis memeriksa status pemeliharaan situs, memuat semua dependensi kode via Composer, dan mengaktifkan mesin utama Laravel.

**2.Buka `bootstrap/app.php`. Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.** 

Jawab:
- **`->withRouting(...)`**: *Pengatur Alamat* Berfungsi seperti peta jalan yang mengarahkan ke mana halaman web akan berpindah saat sebuah URL diklik. (contohnya tersambung ke `routes/web.php`).
- **`->withMiddleware(...)`**: Satpam Penjaga. Berfungsi memeriksa pengunjung yang masuk untuk memastikan mereka punya izin akses dan aman bagi aplikasi.
- **`->withExceptions(...)`**: Penangan *Error*. Berfungsi menyelamatkan tampilan web saat terjadi kesalahan agar pengguna tidak melihat pesan *error* yang membingungkan.

**3. Buka `routes/web.php.` Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang browser, pastikan berubah.**
Jawab: 
Isi file terdapat `Route::get('/', function () { return view('welcome'); });` Baris tersebut memerintahkan Laravel: Jika pengguna membuka halaman utama, tampilkan halaman bernama `welcome`. Ketika fungsi view `('welcome')` diganti dengan teks biasa seperti return `Halo bella`, tampilan di browser otomatis berubah karena perintah lama telah digantikan oleh teks baru tersebut.

**4. Jalankan `php artisan route:list`. Cocokkan keluarannya dengan isi `routes/web.php.`**

Jawab: Perintah `php artisan route:list` berfungsi untuk menampilkan seluruh route atau alamat URL yang terdaftar dan aktif dalam aplikasi Laravel. Hasilnya dapat digunakan untuk mencocokkan route yang terdapat pada `routes/web.php` dengan route yang benar-benar dikenali oleh Laravel.

Pada output tersebut, `GET|HEAD /` menunjukkan route halaman utama yang berasal dari `routes/web.php.` Sementara `storage/{path}` dan `up` merupakan route bawaan Laravel yang berasal dari framework. Jadi, tidak semua route yang muncul harus ditulis secara langsung di `routes/web.php.`
contoh outputnya `php artisan route:list`: 
` GET|HEAD  / .... routes/web.php:5
  GET|HEAD  storage/{path} ......storage.local 
  › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServiceProvider.php:111
  PUT       storage/{path} .. storage.local.upload › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServiceProvider.php:119
  GET|HEAD  up .............................. vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:224`

## Break 

**1. Ganti nama `.env` menjadi `.env.bak`**

*Prediksi saya*: menurut saya akan menampilkan pesan erorr.

Jawab: Laravel kehilangan seluruh konfigurasi dasarnya. Karena tidak menemukan file `.env`, Laravel menganggap aplikasi belum diatur dan tidak memiliki kunci enkripsi.
Outputnya: `500: Server Error`

**2. Kosongkan nilai `APP_KEY` di `.env`**		

*Prediksi saya*: menurut saya akan muncul eror di web karena key nya tidak ada.

Jawab:Aplikasi menolak berjalan karena Laravel wajib memiliki kunci enkripsi untuk mengamankan data sesi (session) dan enkripsi lainnya.
Outputnya: `No application encryption key has been specified`

**3	Ubah `DB_DATABASE` menjadi nama yang tidak ada**	

*Prediksi saya*: menurut saya akan eror karena databasenya gaada.

Jawab: Laravel mencoba membaca tabel sessions untuk memeriksa sesi pengguna aktif di halaman utama, namun proses tersebut langsung terhenti (Internal Server Error) karena database dengan nama kelompoklms memang belum dibuat di server `MySQL/phpMyAdmin`
Outputnya: `SQLSTATE[HY000] [1049] Unknown database 'kelompoklms' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: kelompoklms, SQL: select * from sessionswhereid = E9TXmA5N2K1E1nx4NrCrpLrKYfJXxLKXkiOpmCRS limit 1)`

**4. Ubah `APP_DEBUG=false`, lalu ulangi nomor 3**

*Prediksi saya*: web akan memunculkan eror karena databasenya tidak ada.

Jawab: Mode perbaikan (debug mode) dimatikan, sehingga Laravel menyembunyikan seluruh detail kode, query SQL, dan informasi internal database dari layar demi keamanan pengguna.
Outputnya: `500: Server Error`