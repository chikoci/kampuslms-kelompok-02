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

**Fungsi pengecekan ini:** Buat mastiin kalau kode rute yang kita ketik di `web.php` bener-bener udah sukses terbaca dan terdaftar di sistem Laravel. Ibaratnya, `web.php` itu buku catatannya, dan `route:list` di terminal itu "fakta lapangan" apa saja yang benar-benar aktif.

*(Catatan: Kalau di terminal kelihatan ada 3 rute tambahan seperti `storage` dan `up`, itu wajar. Itu cuma rute sistem bawaan otomatis dari Laravelnya, makanya jumlah rutenya beda tapi rute utama kita tetap klop).*
