# Catatan Praktikum Minggu 13: Kolaborasi, Version Control, dan Peer Review

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Audit Riwayat Git Kelompok & Analisis Alur Kerja

Pada tahap READ ini, saya mengevaluasi kesehatan alur kerja Git repositori KampusLMS Kelompok 02 dari awal semester hingga Minggu 13 secara mandiri melalui perintah terminal dan menggunakan bantuan **Prompt Pack A** untuk menganalisis pola commit, masa hidup branch, serta persebaran kontribusi anggota tim.

### 1. Data Riwayat Git Kelompok (Keluaran Terminal Asli)

#### A. Kontribusi Commit Tiap Anggota (`git shortlog -sn --all`)
- **chikoci (Chiko Dhiva Pramana):** 74 commit (Project Lead & System Architect)
- **Armansyah:** 44 commit (DevOps & Security Auditor)
- **BellaAlviana:** 34 commit (Frontend & Otorisasi Materi)
- **DawwasErry / Dawassss:** 30 commit (Otorisasi Tugas & UI/UX Portal)
- **Akun pengujian/alias lokal:** 5 commit

#### B. Ringkasan Grafik Percabangan (`git log --oneline --graph --all`)
Riwayat Git menunjukkan struktur percabangan yang teratur di mana branch fitur (`chiko`, `arman`, `bella`, `dawwas`) dibuat untuk setiap modul dan selalu digabungkan kembali ke `main` melalui Pull Request resmi (tercatat PR #1 hingga PR #73).

---

### 2. Analisis Alur Kerja Tim Berdasarkan Prompt Pack A

Berikut hasil telaah objektif terhadap alur kerja tim menggunakan template Prompt Pack A:

#### 1. Apakah kami benar-benar memakai branch per fitur, atau sebagian besar commit langsung ke satu branch?
* **Hasil Analisis:** Tim kami secara konsisten menerapkan alur *branch per fitur/modul*. 
* Seluruh fitur utama (autentikasi, otorisasi materi, alur pengumpulan tugas, pengujian otomatis CI, hingga perbaikan performa N+1) dikerjakan di branch terpisah (`chikoci/chiko`, `chikoci/arman`, `chikoci/bella`, `chikoci/dawwas`) dan selalu digabungkan ke cabang utama `main` melalui Pull Request resmi (tercatat sebanyak 73 Pull Request telah berhasil di-merge).

#### 2. Adakah branch yang hidup terlalu lama (*long-lived branches*)?
* **Hasil Analisis:** Sebagian besar branch berumur pendek (antara 1 hingga 3 hari). 
* Begitu satu fitur atau perbaikan selesai diuji di lokal dan CI hijau, branch segera diajukan ke PR dan di-merge ke `main`. Tidak ada branch fitur yang mengendap berminggu-minggu, sehingga tim terhindar dari konflik merge yang parah.

#### 3. Apakah pesan commit mengikuti Conventional Commits?
* **Hasil Analisis:** 
  * Pada **Minggu 1–2 (Awal Semester):** Pesan commit masih beragam dan belum terstandarisasi (contoh: *"done"*, *"all done materi 2"*, *"penambahan sesuai modul"*).
  * Pada **Minggu 3 hingga Minggu 13:** Tim melakukan perbaikan disiplin secara drastis dengan mengadopsi format **Conventional Commits** (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `style:`, `chore:`). Tingkat kepatuhan mencapai **~85%**.

#### 4. Dari riwayat ini, apakah kontribusi anggota terlihat merata?
* **Hasil Analisis:** Kontribusi tersebar aktif ke seluruh 4 anggota kelompok.
* Perbedaan jumlah commit mencerminkan pembagian peran arsitektur:
  - Chiko memiliki jumlah commit tertinggi karena mengemban tugas integrasi modul, konfigurasi Docker/Deployment M4, dan koordinasi merge repositori.
  - Armansyah, Bella, dan Dawwas memiliki puluhan commit aktif yang fokus pada implementasi fitur inti modul masing-masing.

#### Saran Perbaikan untuk Sisa Semester:
1. Menjaga ukuran commit agar lebih kecil dan atomik (*atomic commits*) sehingga setiap commit hanya menangani satu tanggung jawab tunggal.
2. Membiasakan `git add -p` sebelum commit agar tidak ada kode eksperimen atau file sementara yang ikut terbawa.
3. Menghindari commit merge yang berulang dengan selalu menyinkronkan branch lokal via `git switch main && git pull` sebelum membuat branch baru.

---

### 3. Jawaban 5 Pertanyaan Wajib Refleksi Mandiri (Tahap READ)

#### 1. Berapa commit atas nama Anda? Bandingkan dengan anggota lain.
* **Jawaban:** Atas nama saya (`chikoci`), tercatat sebanyak **74 commit** (sekitar 39% dari total commit repositori). Dibandingkan anggota lain: Armansyah (44 commit), Bella Alviana (34 commit), dan Dawwas Eryansyah (30 commit). Jumlah ini lebih tinggi karena peran saya sebagai Project Lead yang bertanggung jawab atas inisialisasi arsitektur, integrasi sistem, dan deployment server produksi di Milestone M4.

#### 2. Adakah minggu di mana Anda tidak punya commit sama sekali?
* **Jawaban:** **Tidak ada.** Berdasarkan rekaman `git log --author="chikoci" --date=short`, saya memiliki aktivitas commit secara konsisten di setiap minggu perkuliahan (Minggu 1 hingga Minggu 13) tanpa ada jeda minggu yang kosong.

#### 3. Berapa persen pesan commit yang mengikuti Conventional Commits?
* **Jawaban:** Sekitar **85%**. 
  * Pada Minggu 1–2, sekitar 15% commit awal masih menggunakan format bebas non-standar.
  * Sejak Minggu 3 ke atas, seluruh commit baru saya telah mematuhi format Conventional Commits (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`).

#### 4. Temukan satu commit Anda sendiri yang pesannya buruk. Tulis ulang seharusnya bagaimana.
* **Commit Buruk:**
  - Hash/Tanggal: `2026-09-09 done`
* **Analisis Kelemahan:** Pesan *"done"* sama sekali tidak menjelaskan apa yang dikerjakan, berkas apa yang dimodifikasi, dan fitur apa yang ditambahkan.
* **Penulisan Ulang yang Seharusnya:**
  ```text
  feat(views): implementasi struktur layout blade dan sistem navigasi dinamis materi 2
  ```

#### 5. `git log --stat` — Temukan commit terbesar Anda. Apakah seharusnya dipecah?
* **Commit Terbesar:**
  - Hash: `fc6c0be`
  - Pesan: `feat(auth): implementasi autentikasi web penuh, reset password, dan dashboard multi-peran`
  - Statistik perubahan: **9 files changed, 888 insertions(+), 34 deletions(-)** (mencakup `AuthController`, `DashboardController`, `PasswordResetController`, dan 6 berkas Blade tampilan auth).
* **Evaluasi:** **Ya, commit ini seharusnya dipecah.**
* **Rekomendasi Pemecahan yang Lebih Baik:**
  1. `feat(auth): implementasi logika autentikasi, registrasi, dan reset password pada controller` (khusus berkas Controller & Form Request).
  2. `feat(views): perancangan antarmuka blade login, register, dan dashboard multi-peran` (khusus berkas Blade & styling CSS).
  *Alasan:* Memisahkan logika backend dan antarmuka frontend mempermudah rekan tim saat mereview dan mempermudah rollback jika terjadi bug pada salah satu bagian.


---

## Bagian BREAK: Eksperimen Risiko Git (6 Percobaan)

Seluruh percobaan BREAK ini dijalankan pada branch latihan terpisah (`test/git-break`) untuk mengamati secara langsung bagaimana Git menangani kesalahan alur kerja, proteksi cabang, dan risiko kebocoran data rahasia:

---

### 1. Commit File .env Palsu dan Push ke Remote
- **Prediksi:** Skrip keamanan atau pipeline CI (GitHub Actions) harus mendeteksi dan menolak keberadaan berkas `.env` agar kredensial tidak bocor ke publik.
- **Langkah Pengujian (Step-by-Step):**
  1. Buat branch latihan baru:
     ```bash
     git switch -c test/break-env
     ```
  2. Cadangkan berkas `.env` asli Anda ke tempat aman di luar Git:
     ```bash
     cp .env .env.cadangan.aman
     ```
  3. Buat file `.env` tiruan dengan data palsu:
     ```bash
     echo "APP_KEY=base64:KUNCI_PALSU_UNTUK_UJI_COBA" > .env
     echo "DB_PASSWORD=password_palsu_123" >> .env
     ```
  4. Paksa Git melacak berkas ini (karena normalnya diabaikan oleh `.gitignore`):
     ```bash
     git add -f .env
     git commit -m "test: coba commit env palsu"
     ```
  5. Coba dorong ke repositori remote:
     ```bash
     git push origin test/break-env
     ```
  6. Setelah percobaan selesai, pulihkan kembali berkas asli dan hapus branch latihan:
     ```bash
     mv .env.cadangan.aman .env
     git switch chiko
     git branch -D test/break-env
     ```
- **Sebenarnya:**
  * File `.env` langsung ditandai berbahaya. Di platform GitHub publik, bot pemindai rahasia (*Secret Scanning*) akan langsung mengirimkan peringatan otomatis bahwa ada token/kunci yang terdeteksi di commit.
  * **Pelajaran penting:** Aturan `.gitignore` wajib selalu menyaring `.env` sejak commit pertama proyek.

---

### 2. Menghapus .env dari Riwayat dengan git rm --cached
- **Prediksi:** Menjalankan perintah `git rm --cached .env` hanya menghapus berkas dari commit terbaru (HEAD), tetapi seluruh isi kredensial rahasia tetap tersimpan selamanya di dalam riwayat commit masa lalu Git.
- **Langkah Pengujian (Step-by-Step):**
  1. Pada branch yang memuat commit berkas `.env`, jalankan perintah:
     ```bash
     git rm --cached .env
     ```
  2. Simpan perubahan ke commit baru:
     ```bash
     git commit -m "fix: hapus env dari staging"
     ```
  3. Periksa status berkas di commit saat ini (`git ls-files .env`) -> berkas tampak sudah hilang.
  4. Periksa riwayat commit sebelumnya menggunakan perintah log:
     ```bash
     git log -p -- .env
     ```
- **Sebenarnya:**
  * Meskipun berkas `.env` sudah tidak ada di folder kerja saat ini, perintah `git log -p -- .env` tetap menampilkan seluruh isi baris teks dan kata sandi yang pernah di-commit sebelumnya secara utuh.
  * Siapa pun yang mengkloning repositori dapat melihat isi kredensial tersebut hanya dengan berpindah ke commit lama.
  * **Pelajaran penting:** Jika data rahasia terlanjur ter-push ke GitHub, menghapusnya lewat commit baru **TIDAK CUKUP**. Rahasia itu harus dianggap sudah bocor dan wajib segera diganti (*revoke & rotate password*).

---

### 3. Simulasi dan Penyelesaian Merge Conflict di routes/web.php
- **Prediksi:** Ketika dua anggota mengubah baris kode yang sama pada berkas yang sama dan mencoba menggabungkannya (*merge*), Git akan berhenti dan memunculkan tanda konflik `<<<<<<< HEAD` dan `>>>>>>>` karena Git tidak bisa menebak versi mana yang benar.
- **Langkah Pengujian (Step-by-Step):**
  1. Buat branch latihan A dan ubah baris rute di `routes/web.php`:
     ```php
     Route::get('/tentang', [TentangController::class, 'index'])->name('tentang');
     ```
     Commit perubahan: `git commit -am "feat: rute tentang versi A"`.
  2. Buat branch latihan B dari titik yang sama, lalu ubah baris yang sama menjadi:
     ```php
     Route::get('/about', [TentangController::class, 'index'])->name('about.us');
     ```
     Commit perubahan: `git commit -am "feat: rute tentang versi B"`.
  3. Gabungkan branch B ke branch A:
     ```bash
     git merge branch-b
     ```
  4. Amati pesan terminal: `CONFLICT (content): Merge conflict in routes/web.php`.
  5. Buka berkas `routes/web.php` di editor. Tampil penanda konflik:
     ```text
     <<<<<<< HEAD
     Route::get('/tentang', [TentangController::class, 'index'])->name('tentang');
     =======
     Route::get('/about', [TentangController::class, 'index'])->name('about.us');
     >>>>>>> branch-b
     ```
  6. Selesaikan konflik dengan menghapus penanda `<<<<<<<`, `=======`, `>>>>>>>` dan putuskan versi yang disepakati bersama.
  7. Simpan berkas, lalu tandai selesai dengan:
     ```bash
     git add routes/web.php
     git commit -m "fix: selesaikan konflik merge rute tentang"
     ```
- **Sebenarnya:** Konflik merge bukanlah kerusakan sistem (*bukan bencana*), melainkan cara Git meminta keputusan manusia saat ada dua perubahan yang bertabrakan. Cara menyelesaikannya adalah membaca maksud kedua kode dan menyatukannya dengan bijak.

---

### 4. Push Langsung ke Branch main Tanpa Melalui Pull Request
- **Prediksi:** Perintah push langsung ke branch `main` akan ditolak mentah-mentah oleh server GitHub karena branch `main` dilindungi oleh *Branch Protection / Rulesets*.
- **Langkah Pengujian (Step-by-Step):**
  1. Pindah ke branch `main`:
     ```bash
     git switch main
     ```
  2. Buat perubahan kecil sembarang (misal menambahkan baris spasi di file teks), lalu buat commit lokal:
     ```bash
     git commit -am "test: coba bypass aturan pull request"
     ```
  3. Coba dorong langsung ke GitHub:
     ```bash
     git push origin main
     ```
- **Sebenarnya:**
  * Terminal langsung memunculkan pesan penolakan dari GitHub:
    ```text
    remote: error: GH013: Repository rule violations found for refs/heads/main.
    remote: - Changes must be made through a pull request.
    To https://github.com/chikoci/kampuslms-kelompok-02.git
     ! [remote rejected] main -> main (push declined due to repository rule violations)
    ```
  * **Pelajaran penting:** Aturan *Rulesets* yang kita pasang di Fase 1 berhasil 100% memaksa seluruh anggota tim bekerja secara tertib melalui alur Pull Request dan code review.

---

### 5. Membuka Pull Request yang Gagal CI dan Mencoba Merge
- **Prediksi:** Tombol hijau *Merge pull request* di web GitHub akan terkunci / mati jika status pemeriksaan otomatis CI (*GitHub Actions*) mengalami kegagalan (berstatus merah).
- **Langkah Pengujian (Step-by-Step):**
  1. Buat branch baru, lalu sengaja buat satu baris test yang gagal pada file pengujian:
     ```php
     $this->assertTrue(false); // Sengaja dibuat gagal
     ```
  2. Commit dan push branch tersebut ke GitHub.
  3. Buka halaman Pull Request baru menuju branch `main`.
  4. Tunggu beberapa detik hingga proses GitHub Actions berjalan.
- **Sebenarnya:**
  * Status check `laravel-tests` menampilkan tanda silang merah (*failed*).
  * Tombol **Merge pull request** otomatis dinonaktifkan (*disabled*) dengan keterangan: *"Required status check 'laravel-tests' is failing"*.
  * **Pelajaran penting:** Aturan ini melindungi kode produksi di `main` agar tidak pernah kemasukan bug fatal yang merusak sistem.

---

### 6. Membuat Commit dengan Identitas Penulis Orang Lain (--author)
- **Prediksi:** Git mengizinkan siapa pun menuliskan nama dan alamat email sembarang pada saat membuat commit menggunakan flag `--author`, membuktikan bahwa nama pembuat commit bisa dipalsukan.
- **Langkah Pengujian (Step-by-Step):**
  1. Di branch latihan, jalankan perintah commit dengan nama tokoh lain:
     ```bash
     git commit --allow-empty -m "test: commit uji identitas palsu" --author="Linus Torvalds <torvalds@linux-foundation.org>"
     ```
  2. Periksa riwayat commit terakhir di terminal:
     ```bash
     git log -n 1
     ```
- **Sebenarnya:**
  * Git mencatat nama *Linus Torvalds* beserta email resminya sebagai penulis commit tersebut, padahal yang mengetik dan menjalankannya adalah kita sendiri di komputer lokal.
  * **Pelajaran penting:** Dosen tidak boleh menilai kontribusi mahasiswa hanya berdasarkan hitungan jumlah commit di `git shortlog`, karena identitas commit sangat mudah dimanipulasi. Penilaian yang adil dan valid wajib dilakukan melalui **sesi wawancara / interview pemahaman kode**.

---

## Bagian CHECKPOINT: Gerbang Wawancara Materi 13 (13.5)

Berikut panduan jawaban to the point dan langkah peragaan praktis saat sesi evaluasi wawancara bersama dosen penguji:

---

### 1. Berapa commit atas nama Anda? Tunjukkan git shortlog -sn.
* **Jawabannya:**  
  *"Atas nama saya (akun `chikoci`), tercatat sebanyak 74 commit dari total sekitar 198 commit repositori kelompok kami."*
* **Langkah Peragaan ke Dosen (Step-by-Step):**  
  1. Buka terminal Git Bash di folder proyek.
  2. Ketik perintah:
     ```bash
     git shortlog -sn --all
     ```
  3. Tunjukkan baris teratas pada keluaran terminal:
     ```text
     74  chikoci
     44  Armansyah
     34  BellaAlviana
     30  DawwasErry / Dawassss
     ```

---

### 2. Kenapa git rm --cached .env tidak cukup kalau .env sudah pernah ter-push?
* **Jawabannya:**  
  *"Karena perintah `git rm --cached` hanya menghapus file dari commit saat ini (HEAD), tetapi seluruh isi berkas dan password rahasianya tetap tersimpan abadi di riwayat commit masa lalu Git. Siapa pun bisa melihat password tersebut cukup dengan mengetik `git log -p -- .env`. Jadi jika rahasia sudah terlanjur ter-push ke internet publik, rahasia itu wajib dianggap sudah bocor dan kata sandi database serta APP_KEY wajib segera diganti."*
* **Langkah Peragaan ke Dosen (Step-by-Step):**  
  Tunjukkan hasil perintah pembuktian di terminal:
  ```bash
  git log -p -- .env
  ```
  Jelaskan bahwa Git menyimpan rekaman sejarah perubahan secara permanen di dalam folder `.git/`.

---

### 3. Tunjukkan satu temuan review yang Anda tulis. Jelaskan risikonya tanpa membaca catatan!
* **Jawabannya:**  
  *"Saya menemukan celah keamanan ketiadaan pembatasan login (Rate Limiting) pada PR #93 Kelompok 03 (implementasi awal PR #62):*
  * **Lokasi:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php` baris 25-38 dan `routes/web.php` baris 27-30.
  * **Risikonya:** Method `store()` langsung memvalidasi input dan memproses autentikasi `Auth::guard('web')->attempt()` tanpa memanfaatkan `RateLimiter` maupun middleware pembatas percobaan login (`throttle`). Celah ini memungkinkan penyerang melancarkan serangan otomatis berupa *credential stuffing* atau *brute force* (mencoba ribuan kombinasi password otomatis per menit) tanpa diblokir sementara oleh sistem.
  * **Sarannya:** Wajib menambahkan middleware `throttle:5,1` pada rute `POST /login` di `routes/web.php` atau memasang facade `Illuminate\Support\Facades\RateLimiter` di controller untuk mengunci akun selama 60 detik bila terjadi kegagalan berulang.*"

---

### 4. Tunjukkan satu temuan review yang masuk ke kelompok Anda. Anda terima atau tolak? Kenapa?
* **Jawabannya:**  
  *"Kelompok 01 memberikan 3 temuan review pada kelompok kami terkait `CourseController.php`, yaitu:*
  1. **Celah Otorisasi Lintas Dosen (IDOR)** pada `app/Http/Controllers/CourseController.php:189-195`.
  2. **Pengambilan Data Koleksi Mahasiswa dan Query Berulang (Performa)** pada `app/Http/Controllers/CourseController.php:96-101 & 220-222`.
  3. **Pelanggaran Single Responsibility Principle (Struktur)** pada `app/Http/Controllers/CourseController.php:188-233`.

  *Dari temuan tersebut, temuan nomor 1 (Keamanan / IDOR) kami **TERIMA** secara penuh.*
  * **Alasannya:** Temuan tersebut sangat tepat dan merupakan celah keamanan nyata. Method `submissions()` sebelumnya hanya membatasi mahasiswa (`if (auth()->user()?->role === 'mahasiswa') abort(403)`), namun otorisasi view pada `CoursePolicy` mengizinkan seluruh dosen. Akibatnya, Dosen B (yang bukan pengampu kelas) bisa membuka URL `/courses/{id}/submissions` milik Dosen A dan melihat seluruh daftar pengumpulan serta nilai mahasiswa kelas tersebut.
  * **Langkah Tindak Lanjut:** Kami menerimanya dan memperbaikinya dengan menerapkan otorisasi berbasis kepemilikan kelas menggunakan `Gate::authorize('update', $course)` pada controller agar hanya dosen pengampu sah yang memiliki hak akses.*"

---

### 5. Kenapa Pull Request (PR) sebaiknya kecil dan hanya untuk satu fitur?
* **Jawabannya:**  
  *"Ada 4 alasan utama:*
  1. **Mudah dan cepat direview:** Rekan tim tidak kewalahan membaca ribuan baris kode sekaligus.
  2. **Bug cepat terdeteksi:** Celah error lebih mudah ditemukan sebelum kode masuk ke produksi.
  3. **Mencegah konflik merge parah:** Branch berumur pendek jarang mengalami tabrakan kode dengan branch lain.
  4. **Mudah dibatalkan (rollback):** Jika fitur tersebut bermasalah di kemudian hari, kita bisa me-revert PR itu tanpa membatalkan fitur-fitur lain yang sudah stabil."*

---

### 6. Kenapa penilaian kontribusi tidak bisa hanya berdasarkan jumlah commit?
* **Jawabannya:**  
  *"Karena jumlah commit tidak mencerminkan bobot dan kualitas pekerjaan nyata:*
  1. **Identitas commit bisa dipalsukan:** Melalui perintah `git commit --author="..."`, siapa pun bisa membuat commit atas nama orang lain.
  2. **Jumlah tidak sama dengan usaha:** Seseorang bisa saja membuat 30 commit hanya untuk mengubah titik-koma atau merapikan spasi (*commit padding*), sementara rekan lain membuat 2 commit tetapi isinya arsitektur logika sistem dan perbaikan celah keamanan krusial.
  3. Oleh karena itu, wawancara pemahaman kode secara langsung adalah cara paling adil dan objektif untuk membuktikan siapa yang benar-benar mengerjakan dan memahami sistem."*
