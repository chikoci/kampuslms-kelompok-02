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
