# Catatan Praktikum Minggu 9: Upload File dan Data Dinamis

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Telusuri Satu Berkas

Pada tahap ini, saya menelusuri bagaimana berkas disimpan di sistem, dicatat di database, dan dilayani oleh Laravel 12.

### 1. Lokasi fisik berkas di sistem
Berkas materi yang diunggah ke disk privat tersimpan di:
* Path relatif: `storage/app/private/materials/{nama-acak}.pdf`
* Path lengkap di laptop saya: `D:\Semester 5\Pemweb\Week1\kampuslms-kelompok-02\storage\app\private\materials/{nama-acak}.pdf`

### 2. Perbandingan database vs fisik
Di tabel `materials` kolom `file_path`, yang tercatat hanya path relatif `materials/{nama-acak}.pdf`. Database tidak menyimpan path lengkap seperti `D:\...` supaya proyek tetap fleksibel saat dipindahkan ke laptop lain atau saat di-deploy ke server Linux.

### 3. Coba akses langsung di browser
Hasilnya gagal dan memunculkan error 404 Not Found. Hal ini terjadi karena berkas tersimpan di `storage/app/private/` yang posisinya berada di luar folder `public/`. Web server hanya membuka file yang berada di dalam folder `public/`, sehingga berkas privat tidak bisa dibuka sembarangan dan wajib dipanggil lewat Controller.

### 4. Beda disk `local` dan `public` di `config/filesystems.php`
* **Disk `local`**: Mengarah ke `storage/app/private`. Bersifat privat, tidak punya alamat URL publik, dan isinya hanya bisa diambil lewat Controller yang dilindungi Gate.
* **Disk `public`**: Mengarah ke `storage/app/public` dan punya alamat URL publik `/storage`. Dibuat khusus untuk file yang boleh dilihat siapa saja secara bebas tanpa login (seperti foto profil).

### 5. Fungsi `php artisan storage:link`
Perintah ini membuat shortcut (*symlink*) dari folder `public/storage` yang mengarah langsung ke `storage/app/public`. 

Materi kuliah dan tugas dilarang disimpan di sini karena file di folder symlink akan langsung dikirim oleh web server sebagai file statis biasa. Akibatnya, siapa pun yang tahu linknya bisa langsung mengunduh berkas tanpa perlu login dan tanpa dicek oleh Policy.

