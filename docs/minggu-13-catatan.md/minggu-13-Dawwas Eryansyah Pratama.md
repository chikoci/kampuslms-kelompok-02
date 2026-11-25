# Catatan Praktikum Minggu 13: Kolaborasi, Version Control, dan Peer Review

**Nama:** Dawwas Eryansyah Pratama  
**NIM:** 10241019  
**Kelompok:** Kelompok 02  
**Peran Utama Proyek:** Otorisasi Tugas (Assignments), Pengumpulan Tugas (Submissions), Penilaian & Nilai (Grading), Indexing Database, serta UI/UX Portal Mahasiswa.  

---

## Bagian READ: Audit Riwayat Git Kelompok & Analisis Alur Kerja

Pada tahap READ ini, saya mengevaluasi kesehatan dan pola kerja kolaboratif repositori KampusLMS Kelompok 02 sepanjang semester hingga Minggu 13 secara mandiri melalui perintah terminal Git dan panduan analisis **Prompt Pack A**. Analisis ini meninjau kebiasaan branching, umur branch, kepatuhan Conventional Commits, serta keadilan persebaran kontribusi tim.

### 1. Data Riwayat Git Kelompok (Keluaran Terminal Asli)

#### A. Kontribusi Commit Tiap Anggota (`git shortlog -sn --all`)
Berdasarkan log repositori Kelompok 02, persebaran commit tercatat sebagai berikut:
- **chikoci (Chiko Dhiva Pramana):** 76 commit *(Project Lead, Integrasi Arsitektur, Docker/Deployment M4)*
- **Armansyah:** 47 commit *(DevOps, Keamanan Otorisasi User & Policy Auditor)*
- **BellaAlviana:** 39 commit *(Modul Materi Perkuliahan, Enrollment, & Validasi Form Request)*
- **Dawassss / DawwasErry (Dawwas Eryansyah Pratama):** **38 commit** *(Modul Tugas, Submissions, Otorisasi IDOR, Index Database, & UI/UX Portal)*
- **Akun alias / pengujian lokal:** 5 commit

> *Catatan:* Kontribusi saya tercatat pada dua alias terminal (`Dawassss` dengan 21 commit dan `DawwasErry` dengan 17 commit), dengan total **38 commit aktif**.

#### B. Ringkasan Grafik Percabangan (`git log --oneline --graph --all`)
Riwayat percabangan repositori menunjukkan struktur yang teratur dan konsisten:
- Alur percabangan menerapkan model *feature branch*: branch fitur (`chiko`, `arman`, `bella`, `dawwas`) dibuat untuk setiap modul pekerjaan.
- Setiap branch berumur pendek dan digabungkan kembali ke cabang utama `main` melalui Pull Request resmi (tercatat lebih dari 70 Pull Request telah berhasil di-merge).

---

### 2. Analisis Alur Kerja Tim Berdasarkan Prompt Pack A

#### 1. Apakah kami benar-benar memakai branch per fitur, atau sebagian besar commit langsung ke satu branch?
* **Hasil Analisis:** Tim kami secara disiplin menerapkan alur **branch per fitur/modul**.
* Tidak ada anggota yang melakukan *push* langsung ke branch `main`. Setiap fitur (autentikasi, manajemen materi, pengumpulan tugas, otorisasi IDOR, listener notifikasi antrean, hingga optimasi komposit index) selalu dikerjakan pada branch terpisah dan diintegrasikan melalui Pull Request resmi.

#### 2. Adakah branch yang hidup terlalu lama (*long-lived branches*)?
* **Hasil Analisis:** **Tidak ada branch yang hidup terlalu lama.**
* Rata-rata masa aktif branch fitur berkisar antara **1 hingga 3 hari**. Begitu fitur selesai diuji di lingkungan lokal dan pipeline CI GitHub Actions berstatus hijau (*passing*), branch segera diajukan ke Pull Request dan di-merge ke `main`. Pola branch berumur pendek ini berhasil menghindarkan tim dari konflik merge (*merge conflict*) yang rumit.

#### 3. Apakah pesan commit mengikuti Conventional Commits?
* **Hasil Analisis:**
  - **Minggu 1–2 (Awal Semester):** Format commit masih belum seragam dan cenderung deskriptif bebas (contoh: *"md dawas"*, *"md2"*, *"TUGAS MD"*).
  - **Minggu 3 hingga Minggu 13:** Tim melakukan perbaikan disiplin secara drastis dengan mengadopsi standar **Conventional Commits** (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`). Tingkat kepatuhan pesan commit tim mencapai **~85%**.

#### 4. Dari riwayat ini, apakah kontribusi anggota terlihat merata?
* **Hasil Analisis:** **Kontribusi tersebar merata dan seimbang ke seluruh 4 anggota tim.**
* Perbedaan kuantitas commit mencerminkan perbedaan cakupan tanggung jawab teknis:
  - Chiko mengoordinasikan penggabungan PR, arsitektur dasar, dan deployment produksi.
  - Armansyah, Bella, dan saya (Dawwas) memegang kepemilikan penuh atas modul-modul fungsional spesifik sesuai pembagian peran semester.

#### Saran Perbaikan Alur Kerja untuk Sisa Semester:
1. **Membiasakan `git add -p`:** Menghindari kebiasaan `git add .` agar tidak ada file sampah, file konfigurasi lokal, atau artefak debug yang tidak sengaja masuk ke *staging area*.
2. **Atomic Commits:** Menjaga agar satu commit hanya menangani satu tanggung jawab logis tunggal, sehingga riwayat perubahan mudah dilacak (*traceable*) dan mudah di-*revert* jika terjadi kesalahan.
3. **Sinkronisasi Berkala:** Selalu menjalankan `git switch main && git pull` sebelum membuat branch baru untuk mencegah divergensi cabang lokal.

---

### 3. Jawaban 5 Pertanyaan Wajib Refleksi Mandiri (Tahap READ)

#### 1. Berapa commit atas nama Anda? Bandingkan dengan anggota lain.
* **Jawaban:** Atas nama saya (`Dawassss` dan `DawwasErry`), tercatat sebanyak **38 commit** (sekitar 19% dari total commit tim). Dibandingkan anggota lain: Chiko (76 commit), Armansyah (47 commit), dan Bella Alviana (39 commit). Jumlah ini mencerminkan fokus tugas saya yang terkonsentrasi pada modul-modul kritis: perancangan alur pengumpulan tugas privat, pengamanan unduhan IDOR, indexing performa database, dan antarmuka portal mahasiswa.

#### 2. Adakah minggu di mana Anda tidak punya commit sama sekali?
* **Jawaban:** **Tidak ada.** Berdasarkan log commit mingguan, saya memiliki riwayat commit aktif secara berkelanjutan dari Minggu 1 hingga Minggu 13 tanpa ada minggu yang kosong.

#### 3. Berapa persen pesan commit yang mengikuti Conventional Commits?
* **Jawaban:** Sekitar **82%**. 
  - Pada 2 minggu awal semester, sebagian commit dokumentasi awal masih memakai format bebas (~18%).
  - Sejak Minggu 3 ke atas, seluruh commit pekerjaan saya konsisten menggunakan prefix standar seperti `feat(submissions):`, `feat(grades):`, `feat(security):`, `feat(ui):`, dan `docs:`.

#### 4. Temukan satu commit Anda sendiri yang pesannya buruk. Tulis ulang seharusnya bagaimana.
* **Commit Buruk Saya:**
  - Hash/Pesan Asli: `8d97d8a TUGAS MD` (atau `d9d489b md dawas`)
* **Analisis Kelemahan:** Pesan tersebut tidak menjelaskan konteks materi, tidak memiliki jenis commit (*type/scope*), dan tidak menjelaskan berkas apa yang ditambahkan.
* **Penulisan Ulang yang Benar:**
  ```text
  docs(catatan): tambahkan catatan praktikum minggu 01 pengenalan git dan konfigurasi laravel
  ```

#### 5. `git log --stat` — Temukan commit terbesar Anda. Apakah seharusnya dipecah?
* **Commit Terbesar Saya:**
  - Hash: `55f1d33`
  - Pesan: `feat(ui): redesign UI/UX portal mahasiswa, halaman login, dan integrasi maskot KAVI`
  - Statistik Perubahan: **29 files changed, 5050 insertions(+), 1027 deletions(-)** (mencakup berkas CSS global, puluhan gambar ilustrasi PNG, dokumen markdown, serta 10 berkas tampilan Blade).
* **Evaluasi:** **Ya, commit ini seharusnya dipecah.**
* **Rekomendasi Pemecahan yang Lebih Baik:**
  1. `feat(assets): tambahkan aset grafis maskot KAVI dan ilustrasi portal mahasiswa` *(khusus berkas gambar/PNG)*.
  2. `feat(css): implementasi tema styling global dan sistem palet warna UI` *(khusus `app.css` & `style.css`)*.
  3. `feat(views): perbarui tata letak antarmuka dashboard, halaman login, dan detail tugas` *(khusus file Blade)*.
  *Alasan:* Pemecahan ini membuat ukuran commit atomik, mempermudah tim saat mereview diff kode antarmuka tanpa terganggu oleh ribuan baris CSS dan aset biner, serta mempermudah rollback parsial jika ada regresi tampilan.

---

## Bagian BUILD: Pelaksanaan Peer Review Kelompok 03 (Bobot 10%)

Sesuai penugasan dosen pada modul BUILD Minggu 13, saya ditugaskan secara individu untuk melakukan audit kode (*peer review*) terhadap repositori **Kelompok 03 (`Devvrp/kampuslms-kelompok-03`)**.

### Profil Penugasan Review Saya:
* **Fokus Modul Review:** **Tugas, Pengumpulan (Submissions), & Nilai**
* **Target Pull Request yang Dinilai:** **PR #80 (`feat/week-10-grading-notifications`)** dan **PR #75 (`feat/week-09-submission-status`)**
* **Fokus Audit Khusus:** Celah IDOR unduhan tugas & query N+1 pada rekap pengumpulan/nilai.

---

### Ringkasan 3 Temuan Nyata yang Saya Kirimkan ke PR Kelompok 03:

#### 1. Temuan Keamanan: Bypass Policy & Inkonsistensi Otorisasi RBAC
* **Lokasi Berkas:** `app/Http/Controllers/Api/GradeController.php:75` (PR #80)
* **Risiko Teknis:** Pada method `courseRecap`, controller memanggil `Gate::authorize('viewAny', [Grade::class, $course])` di mana pada `GradePolicy` peran **Admin** secara sah diizinkan mengakses rekap nilai (`if ($user->role === 'admin') return true;`). Namun, tepat pada baris berikutnya terdapat pengecekan manual:
  ```php
  abort_unless($request->user()->role === 'dosen', 403, 'Endpoint ini hanya untuk dosen.');
  ```
  Pengecekan hardcoded ini menabrak Policy dan menyebabkan Admin ditolak dengan status `403 Forbidden`. Ini melanggar sentralisasi otorisasi berbasis Policy di Laravel.
* **Saran:** Hapus baris `abort_unless(...)` tersebut dan serahkan kendali akses sepenuhnya kepada `Gate::authorize()`.

#### 2. Temuan Performa: Masalah Hidden N+1 Query pada Loop Direktif Blade
* **Lokasi Berkas:** `app/Livewire/Dosen/SubmissionStatusList.php:84` & `resources/views/livewire/dosen/submission-status-list.blade.php:167` (PR #75)
* **Risiko Teknis:** Eager loading pada Livewire hanya memuat `grade:id,submission_id,score`, tanpa memuat relasi `assignment.course`. Di dalam file Blade, perulangan mahasiswa memanggil `@can('view', $submission)` yang mengeksekusi `SubmissionPolicy::view`. Karena relasi `assignment` dan `course` belum dimuat ke memori, Laravel mengeksekusi 2 query SQL lazy-loading baru per baris mahasiswa. Untuk kelas dengan 30 mahasiswa, halaman ini memicu **60 query SQL tambahan secara tersembunyi**.
* **Saran:** Tambahkan relasi bertingkat `'assignment.course'` pada klausa `with` eager loading di komponen Livewire `SubmissionStatusList.php`.

#### 3. Temuan Struktur & Git Hygiene: Berkas Sampah Terminal Lolos ke Repositori
* **Lokasi Berkas:** Berkas `tatus --short` (194 baris) di root repositori PR #80
* **Risiko Teknis:** Terdapat berkas artefak terminal sebesar 194 baris bernama `tatus --short` yang tidak sengaja ter-commit (akibat salah ketik perintah `git status --short` yang ter-redirect lalu di-push via `git add .`). Berkas ini mengotori pohon Git branch utama dan menunjukkan ketiadaan proses *self-diff review* sebelum PR diajukan.
* **Saran:** Hapus berkas sampah via `git rm "tatus --short"`, bersihkan baris komentar kode mati pada `Api/GradeController.php:24`, dan terapkan kebiasaan `git add -p`.

---

### Bukti Komentar Ulasan yang Telah Dikirimkan ke GitHub Kelompok 03:
Ulasan telah resmi diserahkan melalui akun GitHub saya pada PR #80 Kelompok 03 dengan format baku:

```text
Halo Fabyo, Devin, dan rekan-rekan Kelompok 03, terima kasih atas implementasi Pull Request fitur rekap nilai dan notifikasi ini.

Secara umum alur penilaian dan rekap nilainya sudah berjalan dengan baik. Namun ada 3 catatan yang saya temukan untuk penyempurnaan kualitas kode:

1. [Keamanan] Penyelarasan otorisasi RBAC pada endpoint rekap nilai API agar akun Admin tidak terblokir oleh pengecekan manual abort_unless() dan konsisten dengan GradePolicy.
2. [Performa] Penambahan eager loading relasi assignment.course pada query Livewire untuk mencegah masalah Hidden N+1 query saat perulangan direktif @can dieksekusi di Blade.
3. [Struktur] Pembersihan berkas sampah artefak terminal tatus --short dan sisa komentar kode mati agar pohon repositori branch utama tetap bersih dan rapi.

Rincian baris berkas dan saran perbaikannya sudah saya cantumkan langsung pada masing-masing baris kode di atas. Semoga bermanfaat untuk proyek Kelompok 03!
```

---

## Bagian Tanggapan: Review yang Masuk ke Repositori Kelompok 02

Kelompok penilai lain (melalui akun reviewer `@kh0oi`) telah memberikan review pada Pull Request repositori kami (PR #53 terkait implementasi listener notifikasi tugas dan lonceng navbar).

Berikut tanggapan dan tindak lanjut tim kami:

| No | Catatan Reviewer Masuk (`@kh0oi`) | Sikap Tim Kelompok 02 | Argumen Teknis & Tindak Lanjut |
|:---:|:---|:---:|:---|
| **1** | **[Performa]** Penggunaan kueri agregat `unreadNotifications()->count()` pada navbar agar tidak memuat seluruh model notifikasi ke memori RAM. | **DITERIMA** | Masukan sangat tepat. Memanggil relasi notifikasi langsung tanpa agregat `count()` dapat memuat puluhan instans objek notifikasi ke RAM server. Kami perbaiki dengan memanggil method agregat SQL `count()`. |
| **2** | **[Keamanan/Integritas]** Penambahan validasi status tugas (`published`) di listener sebelum antrean mengirim notifikasi ke mahasiswa. | **DITERIMA** | Masukan sangat penting. Tanpa validasi status, tugas yang masih bertaraf `draft` berpotensi memicu notifikasi prematur ke mahasiswa. Kami tambahkan pengondisian `if ($assignment->status !== 'published') return;` di dalam listener `SendAssignmentNotification`. |
| **3** | **[Struktur]** Pemisahan tombol lonceng notifikasi ke komponen Blade tersendiri agar berkas layout utama tetap bersih. | **DITERIMA** | Kami pisahkan markup lonceng notifikasi dan *dropdown counter* ke dalam komponen terpisah `resources/views/components/notification-bell.blade.php`. |

*Bukti Commit Perbaikan Tim:* Tim Kelompok 02 menindaklanjuti temuan tersebut melalui commit perbaikan bertajuk `fix: optimasi kueri count notifikasi dan validasi status tugas sesuai peer review`.

---

## Jawaban Checkpoint Evaluasi Minggu 13

1. **Berapa commit atas nama Anda? Tunjukkan `git shortlog -sn`.**  
   *Jawaban:* Atas nama saya tercatat sebanyak **38 commit** (kombinasi alias `Dawassss` dan `DawwasErry`), dengan fokus kontribusi pada sistem pengumpulan tugas privat, otorisasi IDOR, composite index query database, serta perancangan visual portal mahasiswa.
2. **Kenapa `git rm --cached .env` tidak cukup kalau `.env` sudah pernah ter-push?**  
   *Jawaban:* Karena `git rm --cached` hanya menghapus berkas dari indeks staging/commit paling depan (*HEAD*). Riwayat commit terdahulu yang sudah ter-push ke GitHub masih menyimpan berkas `.env` tersebut secara utuh. Siapa pun dapat mengunduh riwayat commit lama untuk membaca rahasia/kredensial database. Oleh karena itu, rahasia harus dianggap bocor, `APP_KEY` dan password wajib di-regenerasi, serta riwayat commit harus dibersihkan menggunakan *git-filter-repo*.
3. **Tunjukkan satu temuan review yang Anda tulis. Jelaskan risikonya tanpa membaca catatan!**  
   *Jawaban:* Temuan keamanan pada `Api/GradeController.php:75`. Policy sudah mengizinkan Admin melihat rekap nilai, tetapi di controller terdapat hardcoded `abort_unless(role === 'dosen')`. Risikonya adalah *Broken Access Control*: akun Admin sah terblokir dengan `403 Forbidden`, melanggar prinsip pemusatan otorisasi pada Policy Laravel.
4. **Tunjukkan satu temuan yang masuk ke kelompok Anda. Anda terima atau tolak? Kenapa?**  
   *Jawaban:* Temuan performa dari reviewer `@kh0oi` mengenai penggunaan `unreadNotifications()->count()`. Temuan ini **kami terima** karena memanggil `count()` langsung mengeksekusi perintah SQL `SELECT COUNT(*)` yang ringan di database, dibandingkan memuat seluruh koleksi model notifikasi ke memori RAM server PHP.
5. **Kenapa PR sebaiknya berukuran kecil dan hanya menangani satu fitur?**  
   *Jawaban:* PR kecil mempercepat proses peer review, memudahkan reviewer menangkap potensi celah keamanan/bug logika, serta menjaga branch berumur pendek sehingga meminimalkan risiko konflik merge (*merge conflict*). Jika terjadi kesalahan di produksi, PR satu fitur sangat mudah di-*revert* secara aman.
6. **Kenapa penilaian kontribusi tidak bisa hanya berdasarkan jumlah commit?**  
   *Jawaban:* Karena metadata kepenulisan commit Git dapat dimanipulasi dengan mudah (misalnya via `git commit --author`), dan frekuensi commit bisa diperbanyak secara artifisial melalui perubahan-perubahan sepele (seperti hanya mengubah spasi). Penilaian kontribusi yang adil harus mengukur dampak arsitektur kode, kepatuhan alur kerja Git, serta pemahaman teknis saat wawancara.

---
*Dokumen ini disusun secara mandiri dan jujur oleh **Dawwas Eryansyah Pratama (10241019)** sebagai pemenuhan komponen tugas Minggu 13 Pemrograman Web.*

