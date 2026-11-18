# KampusLMS - Kelompok 02

> **Tautan Live Deployment Produksi (Milestone M4 - Materi 12):**  
> **URL Produksi:** [https://kampuslms-kelompok-02.up.railway.app](https://kampuslms-kelompok-02.up.railway.app)  
> *Infrastruktur: Railway Cloud Platform (PaaS) | Containerized Docker PHP 8.4 Apache | MySQL 8.0 Managed Database*

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

## Alur Kolaborasi Tim (Git Workflow & Conventional Commits)

Branch `main` dilindungi oleh *Branch Protection*. Seluruh kontribusi fitur wajib masuk melalui Pull Request dengan alur berikut:

1. **Sinkronisasi Kode Utama:**
   ```bash
   git switch main
   git pull origin main
   ```

2. **Buat Branch Fitur Baru (Gunakan Prefiks yang Sesuai):**
   ```bash
   git switch -c feat/nama-fitur
   ```
   *Format penamaan branch:* `feat/*` (fitur), `fix/*` (perbaikan bug), `docs/*` (dokumentasi), `test/*` (pengujian).

3. **Tinjau Perubahan & Commit (Conventional Commits):**
   Hindari `git add .` secara buta untuk mencegah kebocoran `.env` atau `dd()`. Gunakan staging terpilih:
   ```bash
   git add -p
   git commit -m "feat: deskripsi perubahan singkat dan jelas"
   ```
   *Contoh prefix commit:*
   - `feat:` penambahan fitur baru
   - `fix:` perbaikan bug atau celah keamanan
   - `refactor:` perubahan struktur kode tanpa mengubah fungsi
   - `docs:` pembaruan dokumentasi
   - `test:` penambahan atau pembaruan test

4. **Kirim Branch ke Remote Repository:**
   ```bash
   git push -u origin feat/nama-fitur
   ```

5. **Buka Pull Request (PR) di GitHub:**
   - Masuk ke GitHub, klik **Compare & pull request** menuju branch `main`.
   - Isi deskripsi PR mengikuti template otomatis yang telah disediakan (`.github/pull_request_template.md`).

6. **Peer Review & Merge:**
   - Minimal satu rekan tim wajib meninjau perubahan di tab **Files changed**.
   - Reviewer memeriksa aspek keamanan, performa (N+1), dan keterbacaan kode sebelum memberikan **Approve**.
   - Setelah status CI (GitHub Actions) hijau dan review disetujui, branch dapat di-merge ke `main`.
