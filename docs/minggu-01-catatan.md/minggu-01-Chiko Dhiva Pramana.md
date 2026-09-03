# Catatan Praktikum Minggu 1
**Nama:** Chiko Dhiva Pramana
**NIM:** 10241017

## Bagian READ: Bedah Instalasi

**1. Analisis `public/index.php`**
File ini ibarat pintu gerbang utama atau terminal kedatangan aplikasi Laravel kita. Waktu kita buka webnya di browser, file ini yang pertama kali dijalanin buat nyiapin semua settingan awal. Setelah semuanya siap, dia bakal ngirim *request* kita ke dalam aplikasi dan balikin hasilnya (response) ke layar kita.

**2. Analisis `bootstrap/app.php`**
File ini tugasnya sebagai pusat pengaturan proyek kita.
- **`->withRouting(...)`**: Bagian ini ngatur di mana kita naruh daftar alamat URL web kita (contohnya nyambungin ke `routes/web.php`).
- **`->withMiddleware(...)`**: Bagian ini jadi satpam/penengah. Dia ngecek siapa yang boleh masuk dan mencegah akses yang dilarang sebelum beneran masuk ke dalam aplikasi.
- **`->withExceptions(...)`**: Bagian ini khusus nangani kalau web kita lagi *error* biar tampilannya nggak hancur lebur atau nampilin pesan aneh ke *user*.

**3. Eksperimen `routes/web.php`**
Di file ini ada baris kode: `Route::get('/', function () { return view('welcome'); });`.
Alasannya: Baris ini tuh yang ngasih perintah "kalau ada orang buka halaman paling depan (`/`), tolong panggilin tampilan yang namanya `welcome`". Waktu kata `view('welcome')` diganti pakai teks asal kayak `return 'Tes doang';`, tampilan di browser ikut berubah karena perintah balikan (return) lamanya ditimpa perintah baru.

**4. Cek `php artisan route:list`**
Hasil dari terminal cocok dengan isi file `routes/web.php`. Buktinya, di `web.php` kita punya kode `Route::get('/')`, dan di terminal juga beneran muncul rute dengan URI `/` dan Method `GET`. 

**Fungsi pengecekan ini:** Buat mastiin kalau kode rute yang kita ketik di `web.php` bener-bener udah sukses terbaca dan terdaftar di sistem Laravel.

*(Catatan: Kalau di terminal kelihatan ada 3 rute tambahan seperti `storage` dan `up`, itu wajar. Itu cuma rute sistem bawaan otomatis dari Laravelnya, makanya jumlah rutenya beda tapi rute utama kita tetap klop).*

## Bagian BREAK: Rusak Dengan Sengaja

1. Prediksi saya sebelum mencoba yaitu akan error, dan pesan error sebenarnya yang muncul yaitu "500 | Server Error

2. prediksi saya sebelum mencoba yaitu akan error di web karena keynya tidak ada, dan pesan error sebenarnya yang muncul yaitu "No application encryption key has been specified."

3. prediksi saya sebelum mencoba yaitu akan error di web saat ditest karena tidak ada databasenya, dan pesan error sebenarnya yang muncul yaitu "SQLSTATE[3D000]: Invalid catalog name: 1046 No database selected (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: , SQL: select * from `sessions` where `id` = RtLosNLfRIZA0Ok0lc7Y0FcXfLbdXDCz2POwtw3T limit 1)"

4. prediksi saya sebelum mencoba yaitu akan error juga karena databasenya tidak ada, dan pesan error sebenarnya yang muncul yaitu "500 | Server Error" yang mana kredensial databasenya aman tidak terlihat

## Checkpoint Minggu 1:

1. **Urutan file yang dilewati request:**
   - **Browser:** Mengirim permintaan (*request*) pas kita ngetik URL.
   - **`public/index.php`:** File pertama yang nerima *request* tersebut (sebagai pintu masuk utama).
   - **`bootstrap/app.php`:** File yang bertugas nyiapin konfigurasi dan nyalain mesin Laravel-nya.
   - **`routes/web.php`:** File yang bertugas ngecek apakah URL yang kita minta tadi rutenya ada atau nggak.
   - **Controller & Model:** Kalau rutenya ketemu, *Controller* bakal memproses permintaannya. Kalau butuh narik data dari *database* (misalnya daftar nama), *Controller* bakal minta tolong ke **Model** dulu.
   - **View:** Setelah datanya siap, *Controller* ngirim datanya ke *View* untuk disusun menjadi tampilan HTML. Hasil akhir berupa file HTML dikirim balik buat ditampilin di layar kita.
2. **Kenapa cuma folder `public/` yang boleh diakses?** 
   Biar kodingan inti dan file rahasia kita aman. Kalau semua diekspos, orang iseng/hacker bisa ngeliat isi daleman sistem kita, bahkan bisa ngebaca password *database* langsung dari file `.env`.

3. **Beda `.env` sama `.env.example`:** 
   `.env` itu nyimpen settingan rahasia yang asli (kayak password), sedangkan `.env.example` itu cuma contoh/templat kosongannya aja. Makanya cuma `.env.example` yang boleh di-*commit* (masuk GitHub) biar rahasia asli kita nggak bocor ke publik.

4. **Letak Middleware di Laravel 12:** 
   Didaftarin di file `bootstrap/app.php` (di bagian `->withMiddleware()`). Kenapa beda dari kebanyakan tutorial di internet? Karena tutorial jadul (Laravel 10 ke bawah) daftarinnya lewat file `app/Http/Kernel.php`. Di Laravel 11 dan 12, file `Kernel.php` sengaja dihapus biar struktur proyeknya jauh lebih bersih dan nggak bikin pusing. Sekarang, kita cukup nulis pengaturan di `app.php` kalau memang butuh saja, sisanya diurus otomatis sama Laravel di balik layar.

5. **Risiko `APP_DEBUG=true` di server produksi:** 
   Sangat bahaya, karena kalau web kita tiba-tiba *error*, layar bakal nampilin semua jejak kodingan kita, termasuk ngebocorin *password database* mentah-mentah ke semua orang yang kebetulan buka web kita.
