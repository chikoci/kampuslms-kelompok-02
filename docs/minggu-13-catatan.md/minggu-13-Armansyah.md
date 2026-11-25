# Catatan Praktikum Minggu 13: Kolaborasi, Version Control, dan Peer Review

**Nama:** Armansyah  
**NIM:** 10241013  

---

## Bagian READ: Audit Riwayat Git Kelompok & Analisis Alur Kerja

Pada tahap READ ini, saya mengevaluasi kesehatan alur kerja Git repositori KampusLMS Kelompok 02 dari awal semester hingga Minggu 13 secara mandiri melalui perintah terminal dan menggunakan panduan **Prompt Pack A** untuk menganalisis pola commit, masa hidup branch, serta persebaran kontribusi anggota tim dari sudut pandang peran saya sebagai Backend Developer & Security Auditor.

### 1. Data Riwayat Git Kelompok (Keluaran Terminal Asli)

#### A. Kontribusi Commit Tiap Anggota (`git shortlog -sn --all`)
- **chikoci (Chiko Dhiva Pramana):** 76 commit (Project Lead & System Architect)
- **Armansyah:** 46 commit (DevOps, Security Auditor & Backend Developer)
- **BellaAlviana:** 34 commit (Frontend & Otorisasi Materi)
- **DawwasErry / Dawassss:** 32 commit (Otorisasi Tugas & UI/UX Portal)
- **Akun pengujian/alias lokal:** 5 commit

#### B. Ringkasan Grafik Percabangan (`git log --oneline --graph --all`)
Riwayat Git repositori memperlihatkan pola percabangan yang terisolasi dan tertib. Setiap anggota bekerja pada branch masing-masing (`arman`, `chiko`, `bella`, `dawwas`) dan selalu digabungkan ke cabang utama `main` melalui Pull Request resmi yang melalui proses review dan pengujian CI GitHub Actions (tercatat sebanyak 75 Pull Request telah berhasil digabungkan).

---

### 2. Analisis Alur Kerja Tim Berdasarkan Prompt Pack A

Berikut hasil analisis objektif terhadap alur kerja tim menggunakan instrumen Prompt Pack A:

#### 1. Apakah kami benar-benar memakai branch per fitur, atau sebagian besar commit langsung ke satu branch?
* **Hasil Analisis:** Tim kami secara konsisten menerapkan alur kerja berbasis branch per anggota/fitur.
* Tidak ada anggota yang melakukan *direct push* secara liar ke branch `main`. Semua modul besar (UserPolicy, mitigasi privilege escalation, pengujian otomatis file upload, integrasi caching Redis/database, hingga fitur diferensiasi Rubrik Penilaian Bertingkat) dikerjakan di branch terpisah (`arman`) dan diajukan lewat mekanisme Pull Request resmi (PR #72, PR #75, dll).

#### 2. Adakah branch yang hidup terlalu lama (*long-lived branches*)?
* **Hasil Analisis:** Tidak ada branch yang mengendap terlalu lama.
* Siklus hidup branch berkisar antara 1 hingga 3 hari kerja. Segera setelah modul selesai dikembangkan dan seluruh pengujian otomatis lokal lulus, branch langsung diajukan ke Pull Request untuk di-review oleh rekan kelompok dan di-merge ke `main`. Hal ini meminimalkan terjadinya konflik merge yang rumit.

#### 3. Apakah pesan commit mengikuti Conventional Commits?
* **Hasil Analisis:**
  * Pada **Minggu 3–6 (Fase Awal):** Sebagian commit masih menggunakan bahasa Indonesia informal (contoh: *"menambah catatan praktikum week3"*, *"memperbarui catatan week 4"*).
  * Pada **Minggu 7 hingga Minggu 13:** Disiplin commit meningkat tajam dengan adopsi penuh standar **Conventional Commits** (`feat:`, `fix:`, `test:`, `docs:`, `chore:`, `refactor:`). Kepatuhan pada branch saya mencapai **~85%**.

#### 4. Dari riwayat ini, apakah kontribusi anggota terlihat merata?
* **Hasil Analisis:** Distribusi kontribusi terbagi sangat jelas berdasarkan spesialisasi modul:
  * Chiko fokus pada fondasi sistem, routing auth, layout, dan arsitektur deployment.
  * Saya (Armansyah) fokus pada pengamanan sistem (`UserPolicy`, anti-privilege escalation, matriks IDOR), skrip otomatisasi CI (`test-authz.sh`), performa caching dashboard, serta backend fitur diferensiasi Rubrik Penilaian Bertingkat.
  * Bella dan Dawwas fokus pada materi, tugas, dan penyempurnaan tampilan UI.

#### Saran Perbaikan untuk Sisa Semester:
1. Membiasakan perintah `git add -p` daripada `git add .` untuk memastikan tidak ada file konfigurasi lokal atau berkas sampah yang tidak sengaja ter-commit.
2. Memastikan branch selalu diperbarui dengan `git switch main && git pull` sebelum mulai mengerjakan fitur baru agar histori commit tetap linear dan bebas konflik.
3. Menjaga ukuran commit agar lebih kecil dan terfokus pada satu fungsionalitas (*atomic commits*).

---

### 3. Jawaban 5 Pertanyaan Wajib Refleksi Mandiri (Tahap READ)

#### 1. Berapa commit atas nama Anda? Bandingkan dengan anggota lain.
* **Jawaban:** Atas nama saya (**Armansyah**), tercatat sebanyak **46 commit** (sekitar 24% dari total commit di repositori). 
* Sebagai perbandingan dengan rekan satu tim:
  - Chiko Dhiva Pramana: 76 commit (Project Lead)
  - Bella Alviana: 34 commit
  - Dawwas Eryansyah: 32 commit
* Jumlah ini mencerminkan tanggung jawab saya yang berfokus pada sisi backend security, otomatisasi test suite, implementasi caching, serta backend fitur diferensiasi.

#### 2. Adakah minggu di mana Anda tidak punya commit sama sekali?
* **Jawaban:** **Tidak ada.** Berdasarkan rekaman riwayat `git log --author="Arman"`, aktivitas commit saya tercatat secara berkesinambungan di setiap rentang minggu perkuliahan (Minggu 3 hingga Minggu 13) seiring penambahan materi dan milestone tugas.

#### 3. Berapa persen pesan commit yang mengikuti Conventional Commits?
* **Jawaban:** Sekitar **85%**.
  * Pada awal semester (Minggu 3–6), sekitar 15% commit catatan masih menggunakan teks bebas deskriptif non-standar.
  * Sejak implementasi Milestone M2 (Minggu 7) hingga Minggu 13, seluruh commit saya telah mematuhi aturan Conventional Commits dengan awalan standar seperti `feat(security):`, `test:`, `fix(seeders):`, `chore(ci):`, dan `feat(rubric):`.

#### 4. Temukan satu commit Anda sendiri yang pesannya buruk. Tulis ulang seharusnya bagaimana.
* **Commit Buruk:**
  - Hash: `2faf8a6`
  - Pesan Asli: `memperbarui catatan week 4 dan menambah catatan week 5`
* **Analisis Kelemahan:** Pesan commit ini bersifat pasif, menggunakan bahasa non-standar, menggabungkan dua aktivitas dokumentasi tanpa scope yang jelas, dan tidak mencantumkan identitas subjek.
* **Penulisan Ulang yang Seharusnya:**
  ```text
  docs(notes): perbarui refleksi praktikum minggu 4 dan buat catatan minggu 5 Armansyah
  ```

#### 5. `git log --stat` — Temukan commit terbesar Anda. Apakah seharusnya dipecah?
* **Commit Terbesar:**
  - Hash: `2133b85`
  - Pesan: `feat(rubric): implementasi backend rubrik penilaian bertingkat dan auto-calculation`
  - Statistik Perubahan: **16 files changed, 1581 insertions(+), 29 deletions(-)** (mencakup migrasi 4 tabel baru, 4 model Eloquent, `RubricPolicy`, `RubricController`, modifikasi controller tugas & submission, tampilan blade manage rubrik, serta 7 automated feature tests).
* **Evaluasi:** **Ya, commit ini seharusnya dipecah.**
* **Rekomendasi Pemecahan yang Lebih Baik:**
  1. `feat(rubric-db): migrasi skema database dan model relasi rubrik penilaian bertingkat` (fokus pada migration dan models).
  2. `feat(rubric-core): implementasi RubricController, otorisasi RubricPolicy, dan auto-calculation pada submission` (fokus pada controller & logika bisnis).
  3. `test(rubric): tambah pengujian otomatis komprehensif RubricTieredGradingTest` (fokus pada automated testing).
  4. `feat(rubric-views): penyediaan antarmuka blade fungsional kelola rubrik dan matriks penilaian` (fokus pada antarmuka Blade).
  *Alasan:* Pemecahan ini membuat Pull Request jauh lebih mudah ditinjau baris demi baris oleh rekan tim, mempercepat proses review, dan mempermudah pelacakan sumber masalah jika ditemukan bug.

---

## Bagian BREAK: Enam Percobaan Keamanan dan Alur Kerja Git (40 Menit)

Pada sesi BREAK ini, saya melakukan 6 eksperimen kontrol alur kerja Git pada branch percobaan khusus (`experiment/break-test`) untuk menguji batas ketahanan sistem version control dan keamanan CI/CD repositori:

| # | Yang Dicoba | Yang Diamati & Hasil Nyata | Analisis Keamanan & Rekayasa Perangkat Lunak |
|---|---|---|---|
| **1** | Commit file `.env` palsu lalu `git push` ke remote | Runner CI memeriksa integritas repo. Pada CI yang aman, deteksi berkas kredensial wajib **menggagalkan build** | Repositori publik rentan bot scraper. Kredensial tidak boleh masuk riwayat Git sama sekali |
| **2** | Menghapus `.env` dari riwayat dengan `git rm --cached .env` lalu commit ulang | Berkas `.env` hilang dari commit terbaru (HEAD), tetapi **tetap tersimpan utuh di riwayat commit masa lalu (`git log -p`)** | `git rm --cached` **TIDAK mencabut rahasia**. Sekali ter-push, rahasia harus dianggap bocor dan wajib di-*rotate* (ganti `APP_KEY` dan password DB) |
| **3** | Dua anggota mengubah baris yang sama di `routes/web.php` lalu merge | Terjadi konflik sungguhan (`<<<<<<< HEAD`, `=======`, `>>>>>>>`) dan Git menghentikan merge otomatis | Resolusi konflik yang benar adalah membaca maksud kedua belah pihak dan menuliskan versi integrasi yang benar, bukan memilih buta |
| **4** | Mencoba *direct push* langsung ke branch `main` (`git push origin main`) | GitHub menolak push dengan pesan: `remote: error: GH006: Protected branch hook declined` | Membuktikan aturan *Branch Protection* di GitHub aktif melindungi stabilitas kode produksi |
| **5** | Membuka Pull Request dengan CI berstatus merah (gagal), lalu coba merge | Tombol **Merge Pull Request** di GitHub berstatus terkunci (*disabled*) | Memastikan kode yang rusak tidak dapat digabungkan sebelum seluruh unit test dan linting lolos |
| **6** | Membuat commit dengan author palsu (`git commit --author="Dosen Pengampu <dosen@kampuslms.test>"`) | Git berhasil mencatat commit atas nama author tersebut tanpa autentikasi identitas | **Kepenulisan Git sangat mudah dipalsukan**. Inilah alasan mengapa penilaian kontribusi diuji lewat wawancara kode langsung |

### Refleksi Mendalam dari Percobaan BREAK:
1. **Pelajaran Kritis Percobaan 2 (Secret Leakage):**
   Banyak pengembang pemula mengira `git rm --cached .env` menyelesaikan masalah kebocoran file rahasia. Faktanya, riwayat Git bersifat *append-only immutability*. Siapa pun yang melakukan `git clone` dapat membuka commit lama dan membaca `APP_KEY` serta password database. Mitigasi yang wajib dilakukan adalah:
   - Mengubah kata sandi database produksi seketika.
   - Menjalankan `php artisan key:generate` untuk meregenerasi `APP_KEY`.
   - Menggunakan alat seperti `git-filter-repo` atau BFG Repo-Cleaner jika ingin membersihkan riwayat git secara tuntas.
2. **Pelajaran Kritis Percobaan 6 (Git Author Spoofing):**
   Metadata author pada Git (`user.name` dan `user.email`) hanya berupa string teks biasa yang tidak diverifikasi secara kriptografis kecuali menggunakan *GPG Signed Commits*. Oleh karena itu, jumlah commit tidak pernah menjadi tolok ukur tunggal kontribusi individu dalam tim.

---

## Bagian Checkpoint (CP 13.5): Persiapan Wawancara Evaluasi

Berikut adalah bank jawaban komprehensif untuk 6 pertanyaan evaluasi Checkpoint Minggu 13:

### 1. Berapa commit atas nama Anda? Tunjukkan `git shortlog -sn`.
* **Jawaban:** 
  Berdasarkan keluaran `git shortlog -sn --all`, tercatat sebanyak **46 commit** atas nama **Armansyah**. 
  Commit ini mencakup modul keamanan otorisasi (`UserPolicy`), penanganan anti-privilege escalation, matriks IDOR, skrip pengujian otomatis `test-authz.sh`, integrasi caching metrik dashboard, serta implementasi backend fitur diferensiasi Rubrik Penilaian Bertingkat (Fitur 9).

### 2. Kenapa `git rm --cached .env` tidak cukup kalau `.env` sudah pernah ter-push?
* **Jawaban Inti:**
  Karena perintah `git rm --cached .env` **hanya menghapus file dari halaman depan (commit terbaru/HEAD)**, tetapi **SAMA SEKALI TIDAK menghapus file tersebut dari rekaman riwayat masa lalu Git**.

* **Alasan Teknis & Cara Kerjanya:**
  1. **Git Merekam Sejarah Abadi (*Immutable History*):** Git bekerja seperti rekaman kamera pengawas. Meskipun di rekaman hari ini file `.env` sudah dihapus, pada rekaman commit kemarin berkas tersebut masih tersimpan permanen di dalam basis data Git.
  2. **Kredensial Masih Bisa Diintip:** Siapapun yang meng-clone repositori (atau bot otomatis peretas) cukup menjalankan perintah:
     ```bash
     git show <hash-commit-kemarin>:.env
     # atau
     git log -p -- .env
     ```
     Maka seluruh isi rahasia (kata sandi database, `APP_KEY`, token API, password email) akan langsung terbuka dan terbaca jelas.
  3. **Analogi Sederhana untuk Wawancara:**
     Ibarat kita pernah mengunggah foto KTP dan nomor PIN ATM ke status media sosial kemarin, lalu kita menghapus postingan tersebut hari ini. Meskipun di profil hari ini fotonya sudah hilang, foto kemarin sudah terlanjur terekam di server internet dan kemungkinan besar sudah dilihat atau disimpan oleh orang lain/bot peretas.

* **Tindakan Mitigasi yang Seharusnya Dilakukan (Bukan Cuma Hapus File):**
  1. **Secret Rotation (Langkah Utama & Wajib):** Anggap semua rahasia tersebut **sudah bocor (*compromised*)**. Segera ubah kata sandi database di server MySQL/Railway dan jalankan `php artisan key:generate` untuk meregenerasi `APP_KEY`.
  2. **Bersihkan Riwayat Git:** Gunakan alat khusus seperti `git-filter-repo` atau BFG Repo-Cleaner untuk memusnahkan jejak `.env` dari seluruh rantai commit masa lalu sebelum melakukan *force push*.

### 3. Tunjukkan satu temuan review yang Anda tulis. Jelaskan risikonya tanpa membaca catatan!
* **Temuan yang Saya Tulis:**
  Saya mereview repositori **Kelompok 03** pada **Pull Request #64** (`feat/week-07-users-management` / PR #63) dengan fokus pada **Keamanan Otorisasi dan Manajemen Pengguna**.
  - **Lokasi Masalah:** `app/Policies/` (ketiadaan berkas `UserPolicy.php`) dan `app/Http/Controllers/UserController.php` baris 77–116 (method `update`) serta baris 147–176 (method `destroy`).
  - **Masalah Nyata:** 
    1. Aplikasi Kelompok 03 sama sekali tidak memiliki `UserPolicy`. Otorisasi pengelolaan pengguna 100% hanya mengandalkan route middleware `Route::middleware('role:admin')`. Di dalam controller tidak ada pemanggilan `Gate::authorize()` atau `$this->authorize()`.
    2. Terdapat celah *Self-Demotion* dan *Self-Deletion*: Pada method `update()`, pengecekan admin terakhir (`$activeAdminIds->count() <= 1`) membiarkan seorang Admin yang sedang aktif login untuk mengubah rolenya sendiri menjadi `mahasiswa` atau `dosen` selama ada minimal 2 admin di database. Pada method `destroy()`, admin bahkan bisa menghapus akun miliknya sendiri.

* **Penjelasan Risiko Nyata (Bahan Hafalan Wawancara Tanpa Catatan):**
  1. **Ketiadaan *Defense-in-Depth* & Ancaman *Privilege Escalation*:** 
     Mengandalkan middleware route saja sangat rapuh. Jika kelak ada fitur edit profil pengguna mandiri (misal dosen/mahasiswa memperbarui data pribadi) atau jika model `User` dipanggil melalui API/Livewire tanpa middleware admin, ketiadaan Policy di level model memungkinkan penyerang menyuntikkan parameter `'role' => 'admin'` dalam request untuk menaikkan hak aksesnya secara sepihak (*Mass Assignment / Insecure Privilege Escalation*).
  2. **Accidental Admin Lockout & Kerusakan Integritas Sesi:** 
     Seorang admin yang sedang bertugas dapat secara tidak sengaja menurunkan pangkat akunnya sendiri (*self-demotion*) atau menghapus akunnya sendiri (*self-deletion*). Hal ini seketika mematikan hak akses admin miliknya, merusak sesi login aktif, dan dapat memicu kondisi *lockout* administratif tanpa mekanisme pemulihan darurat.

* **Saran Konkret yang Diberikan:**
  1. Membuat `app/Policies/UserPolicy.php` yang secara tegas menerapkan aturan proteksi diri (contoh: pada method `delete`, wajib memeriksa `$currentUser->id !== $target->id`).
  2. Di `UserController::update`, tambahkan pemeriksaan eksplisit: jika `$user->id === auth()->id() && $validated['role'] !== 'admin'`, sistem wajib menolak request dengan pesan error bahwa admin tidak boleh menurunkan hak akses akunnya sendiri.

### 4. Tunjukkan satu temuan yang masuk ke kelompok Anda. Anda terima atau tolak? Kenapa?
* **Temuan yang Masuk:**
  Temuan dari reviewer **aanmardiah2306j** pada **Pull Request #74** terkait modul fitur diferensiasi Rubrik Penilaian Bertingkat:
  - *Kategori:* **Keamanan & Integritas Data**
  - *Lokasi:* `app/Http/Controllers/RubricController.php` (method `store` dan method `destroy`).
  - *Inti Masalah:* Sistem belum memiliki guard check status `isAssessed` pada method `store()` dan `destroy()`. Karena relasi basis data antar tabel kriteria dan penilaian memakai `cascadeOnDelete()`, jika dosen mengubah atau menghapus rubrik yang sudah pernah digunakan, seluruh rekaman nilai kriteria mahasiswa (`rubric_assessments`) akan terhapus otomatis dari database.
* **Keputusan Kami:** **DITERIMA PENUH.**
* **Alasan Penerimaan:**
  Argumen teknis reviewer sangat tepat dan menyentuh aspek integritas data akademik yang kritis. Membiarkan rubrik yang sudah dinilai dapat dimodifikasi atau dihapus secara bebas dapat memusnahkan bukti evaluasi mahasiswa dan memicu inkonsistensi nilai akhir.
* **Tindakan Perbaikan yang Dilakukan:**
  1. Menambahkan method helper `isAssessed(): bool` pada model `Rubric`.
  2. Menambahkan guard check di awal `RubricController::store()` dan `destroy()` untuk menolak aksi dan mengembalikan flash error jika rubrik telah memiliki relasi riwayat penilaian.
  3. Memperbaiki otorisasi penghapusan rubrik dengan `Gate::authorize('delete', $rubric)`.
  4. Sekaligus mengimplementasikan dua saran teknis lainnya: memadatkan perulangan `updateOrCreate` menjadi batch `RubricAssessment::upsert()` (optimasi N+1 write queries) serta mengenkapsulasi rumus bobot nilai ke method `RubricCriterion::calculateScore()`. Seluruh perbaikan telah diverifikasi dengan 83 automated tests berstatus hijau (*green build*).

### 5. Kenapa PR sebaiknya kecil dan satu fitur?
* **Jawaban:**
  1. **Kemudahan Review:** PR yang kecil (di bawah 200–300 baris) memungkinkan rekan tim mereview secara teliti baris demi baris, sehingga bug logika dan celah keamanan tidak terlewat.
  2. **Isolasi Risiko:** Jika terjadi kegagalan atau regresi di produksi, tim dapat melakukan *revert* pada satu fitur spesifik tanpa membatalkan fitur-fitur lain yang sudah stabil.
  3. **Mencegah Konflik Merge:** PR yang kecil dan berumur pendek cepat digabungkan ke `main`, sehingga tidak terjadi *divergence* cabang yang memicu merge conflict menyakitkan.

### 6. Kenapa penilaian kontribusi tidak bisa hanya berdasarkan jumlah commit?
* **Jawaban:**
  1. **Kuantitas Bukan Kualitas:** Satu commit besar yang menyelesaikan fitur kritis (seperti optimasi N+1 atau integrasi queue) jauh lebih bernilai daripada 20 commit kecil yang hanya mengubah spasi atau typo teks dokumentasi.
  2. **Mudah Dimanipulasi (*Spoofing*):** Seperti yang dibuktikan pada Percobaan BREAK 6, identitas author Git sangat mudah dipalsukan dengan perintah `git commit --author="..."`. Selain itu, commit juga mudah digelembungkan dengan commit berulang-ulang (*commit inflation*).
  3. **Pemahaman Kode:** Penilaian sejati diuji melalui **interview pemahaman kode**, di mana mahasiswa harus mampu menjelaskan arsitektur, alur data, dan penanganan celah keamanan dari kode yang ia tulis sendiri tanpa membaca catatan.


