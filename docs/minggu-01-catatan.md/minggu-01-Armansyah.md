# Catatan Praktikum Minggu 1
**Nama:** Armansyah  
**NIM:** 10241013

## Bagian READ: Bedah Instalasi  
**1. Analisis `public/index.php`**  
File ini bertindak sebagai pintu gerbang utama untuk semua interaksi web pada aplikasi, yang diawali dengan mencatat waktu mulai eksekusi dan memeriksa apakah sistem sedang dalam mode pemeliharaan.

Selanjutnya, sistem memuat autoloader composer untuk mengaktifkan seluruh library pendukung, lalu memanggil berkas `bootstrap/app.php` guna menginisialisasi instance aplikasi utama. 

Pada langkah terkahir, instance aplikasi tersebut langsung menangkap permintaan data (HTTP request) yang masuk dari pengguna, memprosesnya melalui alur aplikasi, dan mengelola pengiriman respons kembalinya.

#

**2. Analisis `bootstrap/app.php`**  
File ini adalah tempat Laravel mengonfigurasi dan membuat aplikasi utama. Pada Laravel 12, konfigurasi yang dahulu banyak berada di `Kernel.php` kini diarahkan melalui file bootstrap ini.

**1. Bagian route**

Bagian yang mengurus route adalah konfigurasi withRouting.

Fungsinya menentukan lokasi file route yang digunakan aplikasi:

- Route web diambil dari `web.php.`
- Route untuk perintah Artisan diambil dari `console.php.`
- Endpoint /up digunakan untuk pemeriksaan kesehatan aplikasi.  

**2. Bagian middleware**

Bagian yang mengurus middleware adalah konfigurasi withMiddleware.

Middleware berfungsi sebagai penyaring request sebelum mencapai tujuan dan response sebelum dikirim kembali kepada pengguna. Contohnya autentikasi, pemeriksaan CSRF, dan pengaturan akses.

Pada file ini, bagian middleware masih kosong. Artinya, belum ada middleware tambahan yang dikonfigurasi secara khusus di app.php. Laravel tetap menggunakan middleware bawaan yang telah disediakan framework.

**3. Bagian exception**

Bagian yang mengurus exception adalah konfigurasi withExceptions.

Exception adalah kondisi error atau masalah yang terjadi ketika aplikasi berjalan. Bagian ini dapat digunakan untuk mengatur cara error dilaporkan, dicatat, atau ditampilkan kepada pengguna.

Pada file ini, konfigurasi exception juga masih kosong. Karena itu, Laravel masih menggunakan perilaku penanganan error bawaan.

#
**3. Eksperimen `routes/web.php`**  
Tanda `/` adalah rute untuk halaman utama (root URL). Agar tampilannya berubah menjadi tulisan 'Selamat datang', saya cukup membuka file `routes/web.php`, lalu mengganti bagian `return view('welcome');` menjadi `return 'Selamat datang';`. Setelah file disimpan dan browser di-refresh, halaman utama akan langsung menampilkan teks tersebut.

#
**4. Cek `php artisan route:list`**  
Berdasarkan keluaran `php artisan route:list`, route `GET|HEAD /` sangat cocok dengan isi file `routes/web.php` pada baris ke-5, yang bertugas menangani akses ke halaman utama website. Adapun route lain seperti up dan `storage/{path}` merupakan route otomatis yang disediakan oleh sistem framework Laravel 12


