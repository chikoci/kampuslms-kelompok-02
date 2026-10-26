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
7. Jalankan migrasi dan seeder untuk menyiapkan struktur tabel serta data demo:
   ```bash
   php artisan migrate --seed
   ```
8. Jalankan worker antrean (*Queue Worker*) di terminal terpisah untuk memproses pengiriman notifikasi latar belakang:
   ```bash
   php artisan queue:work
   ```
9. Nyalakan server lokal:
   ```bash
   php artisan serve
   ```
10. Buka browser dan akses web di `http://127.0.0.1:8000`

## Akun Demo Standar (Hasil Seeder)

Untuk mempermudah pengujian seluruh alur aplikasi dan otorisasi tiga peran civitas kampus, sistem menyediakan 3 akun demo bawaan:

| Peran (Role) | Nama Pengguna | Alamat Email | Kata Sandi (Password) | Hak Akses Utama |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | Administrator Demo | `admin@kampuslms.test` | `password` | Mengelola seluruh pengguna (`/users`), membuat mata kuliah (`/courses/create`), mendaftarkan mahasiswa, dan mengaudit sistem. |
| **Dosen Pengampu** | Dosen Demo | `dosen@kampuslms.test` | `password` | Mengelola materi dan tugas pada kelas yang diampu (`/courses/1`), serta memeriksa dan menilai pengumpulan mahasiswa (`/submissions`). |
| **Mahasiswa** | Mahasiswa Demo | `mahasiswa@kampuslms.test` | `password` | Mengakses materi kelas yang diikuti (Course 1-4), mengumpulkan tugas, dan melihat riwayat nilai pribadi. Ditolak dari Course 5 (IDOR check). |

## Pembagian Peran dan Modul Tim

| Nama Lengkap | NIM | Fitur Utama & Tanggung Jawab Modul |
| :--- | :---: | :--- |
| **Chiko Dhiva Pramana** | 10241017 | **Project Lead & Fitur 1**: Autentikasi Web Resmi, Sesi & Dashboard Multi-Peran, Proteksi Brute Force & Session Fixation, Komponen Layout, API Sanctum Auth. |
| **Bella Alviana** | 10241015 | **Fitur 2**: Otorisasi Mata Kuliah & CRUD Materi Perkuliahan, `CoursePolicy`, `MaterialPolicy`, Form Request Validasi (`StoreCourseRequest`, `UpdateCourseRequest`), Views Courses & Materials. |
| **Dawwas Eryansyah Pratama** | 10241019 | **Fitur 3**: Otorisasi Tugas, Pengumpulan Tugas (Anti-IDOR) & Penilaian (Grade), `AssignmentPolicy`, `SubmissionPolicy`, `GradePolicy`, Private File Storage Pipeline. |
| **Armansyah** | 10241013 | **Fitur 4**: Otorisasi Pengguna, Anti Privilege Escalation (`UserPolicy`), Tabel IDOR (`docs/keamanan.md`), Skrip Pengujian Otomatis (`scripts/test-authz.sh`), `AuthzTest`, CI GitHub Actions. |

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
