# KampusLMS - Kelompok 02

## Anggota Kelompok
1. Armansyah (10241013)
2. Chiko Dhiva Pramana (10241017)
3. Dawwas Eryansyah Pratama (10241019)
4. Bella Alviana (10241015)

## Cara Menjalankan Proyek
Ikuti langkah-langkah berikut untuk menjalankan proyek ini di komputer lokal:

1. Clone repositori ini: 
   ```bash
   git clone <url-repo-github>
   ```
2. Masuk ke folder proyek:
   ```bash
   cd kampuslms-kelompok-02
   ```
3. Install semua dependensi PHP menggunakan Composer:
   ```bash
   composer install
   ```
4. Salin file konfigurasi *environment*:
   ```bash
   cp .env.example .env
   ```
5. Buka file `.env` dan atur konfigurasi database (sesuaikan nama database, username, dan password).
6. Hasilkan *Application Key* yang baru:
   ```bash
   php artisan key:generate
   ```
7. Jalankan migrasi untuk membuat tabel database:
   ```bash
   php artisan migrate
   ```
8. Nyalakan server lokal:
   ```bash
   php artisan serve
   ```
9. Buka browser dan akses web di `http://127.0.0.1:8000`

## Pembagian Peran

| Nama Lengkap | NIM | Peran / Tugas |
| :--- | :--- | :--- |
| **Chiko Dhiva Pramana** | 10241017 |  |
| **Armansyah** | 10241013 |  |
| **Dawwas Eryansyah Pratama** | 10241019 |  |
| **Bella Alviana** | 10241015 |  |

## Cara Kolaborasi (Git Workflow Kelompok)
Karena branch `main` dilindungi (*Branch Protection*), berikut adalah alur kerja yang **WAJIB** diikuti oleh semua anggota kelompok:

1. **Sinkronisasi Kode Terbaru (Lakukan Sebelum Mulai Mengerjakan Apapun)**
   ```bash
   git checkout main
   git pull origin main
   ```
2. **Buat Branch Baru (Bercabang)**
   ```bash
   git checkout -b nama-branch-tugasmu
   ```
3. **Simpan dan Kirim Pekerjaan (Commit & Push)**
   ```bash
   git add .
   git commit -m "Deskripsikan apa yang baru dikerjakan"
   git push -u origin nama-branch-tugasmu
   ```
4. **Compare & Pull Request di GitHub**
   - Buka halaman repositori di web GitHub.
   - Klik tombol hijau **Compare & pull request** yang muncul.
   - Pastikan cabang tujuan adalah `main`, lalu klik **Create pull request**.
5. **Approve dan Merge (Harus Dilakukan Anggota Lain)**
   - Karena Anda tidak bisa me-*merge* PR Anda sendiri, mintalah teman kelompok Anda untuk membuka PR tersebut.
   - Teman Anda harus mengklik tab **Files changed**, lalu klik **Review changes**, pilih **Approve**, dan klik **Submit review**.
   - Setelah muncul tanda centang hijau, klik tombol **Merge pull request**.
6. **Tarik Kembali Kode yang Sudah Digabungkan**
   - Setelah di-merge di GitHub, setiap anggota kembali ke terminal komputer masing-masing.
   - Lakukan langkah 1 lagi (`git checkout main` dan `git pull origin main`) untuk mendapatkan kodingan yang baru saja digabungkan tersebut!
