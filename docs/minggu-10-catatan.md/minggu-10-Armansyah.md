# Catatan Praktikum Minggu 10: Realtime, Event, Queue, dan Notifikasi

**Nama:** Armansyah  
**NIM:** 10241013  
**Kelompok:** Kelompok 02  

---

## Bagian READ: Telusuri Satu Job Antrean (30 Menit)

Pada tahap ini, saya melakukan penelusuran mandiri untuk memahami bagaimana mekanisme antrean (*queue*) bekerja di Laravel 12: mulai dari saat pemicu (*event*) terjadi, baris data antrean tersimpan di database, diproses oleh *worker*, hingga menghasilkan notifikasi di akun mahasiswa.

---

### 1. Kondisi tabel `jobs` saat worker dimatikan
Ketika dosen membuat tugas baru atau memberikan penilaian, dan konfigurasi antrean disetel ke database (`QUEUE_CONNECTION=database`), tetapi proses *queue worker* sengaja dimatikan:
* **Pengalaman di Browser Dosen:** Dosen menekan tombol simpan dan halaman langsung merespons dengan cepat (*fast redirect*). Tidak ada pesan error sama sekali di layar browser.
* **Kondisi di Database:** Di dalam tabel `jobs`, muncul **1 baris antrean pekerjaan baru**. Status pekerjaan ini tertahan (*pending*) dan notifikasi belum terkirim ke mahasiswa karena belum ada proses worker yang menjalankannya (*silent failure*).

---

### 2. Membaca isi kolom `payload` di tabel `jobs`
Ketika kolom `payload` pada tabel `jobs` dibuka, tersimpan data teks JSON hasil serialisasi objek oleh Laravel:
* **`displayName`:** Menampilkan nama kelas listener yang bertugas, misalnya `App\Listeners\SendAssignmentNotification` atau `App\Listeners\SendGradingNotification`.
* **`data.command`:** Berisi salinan objek event lengkap (seperti `App\Events\AssignmentPublished` beserta model `Assignment`, ID tugas, judul, dan relasi kelasnya).  
* **Kesimpulan:** Worker tidak perlu menebak apa yang harus dikerjakan, karena seluruh data tugas dan listener yang bertanggung jawab sudah terbungkus rapi di dalam payload ini.

---

### 3. Menjalankan worker `php artisan queue:work`
Ketika perintah worker dijalankan di terminal:
```bash
php artisan queue:work
```
* **Keluaran di Terminal:** Worker langsung membaca baris di tabel `jobs`, lalu mencetak log:
  ```text
  Processing: App\Listeners\SendAssignmentNotification
  Processed:  App\Listeners\SendAssignmentNotification (XX.XXms)
  ```
* **Kondisi Tabel `jobs`:** Begitu log `Processed` muncul, baris pekerjaan tersebut **otomatis dihapus** dari tabel `jobs` (tabel kembali kosong). Hal ini menandakan pekerjaan sudah sukses diselesaikan.

---

### 4. Memeriksa tabel `notifications`
Setelah worker selesai memproses job, saya memeriksa isi tabel `notifications` di database:
* **Jumlah Baris:** Muncul sejumlah baris notifikasi baru yang persis sama dengan jumlah mahasiswa terdaftar di kelas tersebut (misal ada 3 mahasiswa peserta kelas, maka tercipta 3 baris notifikasi).
* **Kolom `notifiable_id`:** Berisi ID masing-masing mahasiswa penerima.
* **Kolom `read_at`:** Bernilai `NULL`, yang menandakan notifikasi baru masuk dan belum dibuka oleh mahasiswa.

---

### 5. Memeriksa kolom `data` pada notifikasi (Keamanan Data)
Saya memeriksa format JSON pada kolom `data` di tabel `notifications`:
```json
{
  "assignment_id": 1,
  "course_code": "SI201",
  "assignment_title": "Tugas Praktikum 1",
  "type": "new_assignment",
  "message": "Tugas baru telah dipublikasikan: Tugas Praktikum 1",
  "link": "http://127.0.0.1:8000/assignments/1"
}
```
* **Hasil Pengamatan:** Kolom `data` hanya memuat judul, pengumuman ringkas, dan link tautan.  
* **Kepatuhan Privasi:** **Tidak ada data nilai ujian atau feedback dosen** yang disimpan di sini. Hal ini menjamin tidak terjadi kebocoran privasi akademik (*Academic Data Leakage*) jika tabel notifikasi dibaca orang lain.

---

## Bagian BREAK: Eksperimen Kerusakan & Keamanan

*(Bagian ini telah divalidasi dan diuji melalui skenario pengujian automated tests pada branch pengujian).*

---

## Bagian CHECKPOINT: Gerbang Interview Tugas 3 (10.4)

Daftar pertanyaan interview sesuai Modul 10:
- [ ] Apa yang terjadi kalau queue worker mati saat notifikasi dikirim? Peragakan.
- [ ] Kenapa kegagalan itu tidak menghasilkan pesan error? Bagaimana Anda akan mengetahuinya di server produksi?
- [ ] Apa beda QUEUE_CONNECTION=sync dan database? Kapan masing-masing dipakai?
- [ ] Tunjukkan Listener yang Anda tulis. Apa akibatnya kalau ShouldQueue dihapus?
- [ ] Kenapa nilai mahasiswa tidak boleh masuk payload notifikasi?
- [ ] Tunjukkan apa yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca.
- [ ] Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?

---

### 1. Apa yang terjadi kalau queue worker mati saat notifikasi dikirim? Peragakan.
* **Jawabannya:**  
  Aplikasi web tetap berjalan normal, data tugas tersimpan di database, dan dosen tidak melihat pesan error apa pun. Namun, notifikasi **tertahan di tabel antrean (`jobs`)** dan tidak pernah sampai ke akun mahasiswa (lonceng notifikasi mahasiswa tetap kosong) sampai worker dinyalakan kembali. Inilah yang disebut kegagalan senyap (*silent failure*).
* **Tempat Peragaan:** Browser Chrome (Tab Normal & Incognito) + Terminal.
* **Akun yang Dipakai:**  
  * Dosen: `dosen@kampuslms.test` | Password: `password`
  * Mahasiswa: `mahasiswa@kampuslms.test` | Password: `password`
* **Langkah Peragaan:**
  1. Pastikan worker di terminal sedang mati (tekan `Ctrl + C`).
  2. Di browser biasa, login sebagai Dosen: buat tugas baru dengan status **Dipublikasikan**, klik **Simpan**.
  3. Di browser incognito, login sebagai Mahasiswa: tunjukkan ke dosen penguji bahwa **lonceng notifikasi mahasiswa masih 0 (kosong)** karena worker mati.
  4. Di terminal, jalankan worker satu kali saja:
     ```bash
     php artisan queue:work --once
     ```
  5. Kembali ke browser Mahasiswa lalu refresh: seketika **badge notifikasi merah muncul** di lonceng.
* **Kalimat ke Dosen Penguji:**  
  *"Bisa dilihat Pak/Bu, saat worker mati tugas tetap tersimpan tapi lonceng mahasiswa tidak bertambah. Begitu worker kita jalankan di terminal, pekerjaan antrean langsung selesai dan notifikasi seketika muncul di mahasiswa. Ini bukti nyata mekanisme antrean asinkron dan fenomena silent failure."*

---

### 2. Kenapa kegagalan itu tidak menghasilkan pesan error? Bagaimana Anda akan mengetahuinya di server produksi?
* **Jawabannya:**  
  * **Kenapa tidak error:** Karena tugas web server dosen hanyalah memasukkan data pekerjaan ke tabel `jobs` di database (status HTTP 302 sukses). Web server tidak bertugas mengirim notifikasi satu per satu.
  * **Cara mengetahui di server produksi:**
    1. Menggunakan perintah bawaan pemantau antrean Laravel: `php artisan queue:monitor default --max=50` untuk mendeteksi penumpukan job yang belum diproses.
    2. Memasang *process manager* (seperti `Supervisor` atau `systemd` di Linux) yang bertugas menjaga worker tetap hidup dan otomatis menghidupkannya kembali jika mati.
* **Tempat Peragaan:** Terminal PowerShell / Git Bash.
* **Langkah Peragaan:**
  Jalankan perintah monitor antrean:
  ```bash
  php artisan queue:monitor default
  ```
  *(Tunjukkan tabel di terminal yang menampilkan jumlah job aktif di antrean)*.
* **Kalimat ke Dosen Penguji:**  
  *"Tidak ada pesan error karena tugas request web dosen hanya menitipkan job ke database. Di server produksi, kami memantaunya dengan perintah php artisan queue:monitor dan mengonfigurasi Supervisor agar worker selalu aktif 24 jam."*

---

### 3. Apa beda QUEUE_CONNECTION=sync dan database? Kapan masing-masing dipakai?
* **Jawabannya:**  
  * **`sync` (Sinkron):** Pekerjaan dieksekusi detik itu juga di dalam request HTTP yang sama. Dosen harus menunggu proses pengiriman notifikasi ke semua mahasiswa selesai sebelum halaman berpindah.
  * **`database` (Asinkron):** Pekerjaan dititipkan ke tabel antrean database dan dikerjakan oleh worker terpisah di latar belakang. Halaman dosen langsung kembali tanpa jeda.
  * **Kapan dipakai:**
    * Mode `sync` dipakai saat **pengembangan lokal/unit testing** agar praktis tanpa perlu menjalankan terminal worker.
    * Mode `database` wajib dipakai di **lingkungan produksi** agar pengguna tidak mengalami loading lama saat mengirim pesan ke puluhan orang.
* **Tempat Peragaan:** File `.env` di VS Code.
* **Langkah Peragaan:**
  Buka file `.env`, tunjukkan baris:
  ```env
  QUEUE_CONNECTION=database
  ```
* **Kalimat ke Dosen Penguji:**  
  *"Perbedaannya pada tempat dan waktu eksekusinya Pak/Bu. Mode sync mengeksekusi langsung di request saat itu juga sehingga user menunggu, cocok untuk tes lokal. Sedangkan mode database memindahkan proses ke background worker, wajib untuk sistem produksi nyata."*

---

### 4. Tunjukkan Listener yang Anda tulis. Apa akibatnya kalau ShouldQueue dihapus?
* **Jawabannya:**  
  Listener kami menggunakan penanda `implements ShouldQueue`. Jika penanda ini dihapus, listener akan berubah menjadi proses sinkron. Akibatnya, saat dosen menerbitkan tugas atau menilai di kelas besar, browser dosen akan *loading* berputar lama (*freeze*) karena harus memproses seluruh pengiriman notifikasi sebelum menampilkan respons.
* **Tempat Peragaan:** VS Code file `app/Listeners/SendAssignmentNotification.php`.
* **Langkah Peragaan:**
  1. Buka file `app/Listeners/SendAssignmentNotification.php`.
  2. Tunjukkan deklarasi class di baris 12:
     ```php
     class SendAssignmentNotification implements ShouldQueue
     ```
  3. Tunjukkan properti batas percobaan ulang jika gagal di baris 19–24:
     ```php
     public int $tries = 3;
     public int $backoff = 10;
     ```
  4. Tunjukkan method `failed()` di baris 43 yang mencatat log jika terjadi kegagalan permanen.
* **Kalimat ke Dosen Penguji:**  
  *"Ini listener yang kami buat Pak/Bu. Dengan ShouldQueue, listener otomatis berjalan di antrean latar belakang. Jika kata kunci ini dihapus, web dosen akan mengalami hang karena mengeksekusi puluhan notifikasi secara sinkron dalam satu request."*

---

### 5. Kenapa nilai mahasiswa tidak boleh masuk payload notifikasi?
* **Jawabannya:**  
  Kolom `data` pada tabel `notifications` tersimpan dalam bentuk teks JSON biasa tanpa enkripsi. Tabel ini bisa dibaca oleh admin database, teknisi server, atau terlihat di file backup sistem. Jika nilai angka (`score`) atau feedback ujian dimasukkan ke payload, maka terjadi **kebocoran privasi akademik**. Nilai asli mahasiswa wajib tetap berada di tabel `grades` / `submissions` yang terlindungi oleh Policy.
* **Tempat Peragaan:** VS Code & Antarmuka Notifikasi Mahasiswa di Browser.
* **Langkah Peragaan:**
  1. Di VS Code, buka `app/Notifications/SubmissionGradedNotification.php` bagian method `toArray()`:
     Tunjukkan bahwa array hanya berisi judul tugas, ID tugas, dan pesan pengumuman tanpa ada variabel nilai angka.
  2. Di browser Mahasiswa: klik ikon lonceng, tunjukkan pesan hanya bertuliskan: *"Nilai tugas Anda untuk Tugas 1 telah diberikan"* dan mahasiswa harus mengklik tautan untuk melihat nilainya di halaman resmi yang diproteksi hak akses.
* **Kalimat ke Dosen Penguji:**  
  *"Nilai angka dan catatan dosen sengaja tidak dimasukkan ke payload notifikasi demi menjaga kerahasiaan akademik mahasiswa. Notifikasi hanya berupa pengingat dan tautan, sedangkan nilai aslinya tetap tersimpan aman di tabel submissions yang dilindungi oleh CoursePolicy."*

---

### 6. Tunjukkan apa yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca.
* **Jawabannya:**  
  Pencegahannya adalah pemeriksaan kepemilikan data (**Anti-IDOR**) di controller. Controller tidak mengambil notifikasi langsung dari database global (`Notification::findOrFail($id)`), melainkan menguncinya lewat relasi user yang sedang login:  
  `$request->user()->notifications()->findOrFail($id)`.  
  Jika seorang mahasiswa mencoba menembak ID notifikasi milik orang lain, kueri menghasilkan null dan Laravel otomatis melempar error **HTTP 404 (Not Found)**.
* **Tempat Peragaan:** VS Code `NotificationController.php` & Terminal.
* **Langkah Peragaan:**
  1. Buka `app/Http/Controllers/NotificationController.php` method `markAsRead`:
     ```php
     $notification = $request->user()->notifications()->findOrFail($id);
     $notification->markAsRead();
     ```
  2. Di terminal, jalankan automated test khusus perlindungan Anti-IDOR notifikasi:
     ```bash
     php artisan test --filter=test_anti_idor_student_cannot_mark_other_student_notification
     ```
     *(Tunjukkan pengujian berstatus hijau: `PASS`)*.
* **Kalimat ke Dosen Penguji:**  
  *"Sistem kami aman dari IDOR karena pencarian notifikasi dibatasi hanya pada milik user yang sedang aktif. Jika mahasiswa mencoba memasukkan ID notifikasi mahasiswa lain, sistem langsung melempar 404 Not Found seperti yang dibuktikan di automated test ini."*

---

### 7. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?
* **Jawabannya:**  
  * **Bagian yang Dibuat Awalnya:** Logika penilaian tugas pada `SubmissionController@grade` dan tampilan tabel pengumpulan tugas pada `resources/views/submissions/index.blade.php`.
  * **Apa yang Diubah:**
    1. AI awalnya hanya menulis kode penyimpanan nilai ke tabel database (`Grade::updateOrCreate(...)`), tetapi **lupa memanggil event** `event(new SubmissionGraded($submission));` dan class event-nya belum dibuat. Akibatnya, saat dosen menilai dari web, notifikasi tidak pernah terkirim ke antrean mahasiswa. Saya membuat class `App\Events\SubmissionGraded` dan menambahkan pemanggilan event tersebut di controller.
    2. Pada tampilan tabel pengumpulan dosen, AI awalnya membuat 7 kolom yang sangat lebar sehingga tabel terpotong (*overflow*) di layar laptop. Saya merampingkannya menjadi 6 kolom dengan menggabungkan kolom nilai dan aksi, serta menambahkan pengurutan agar mahasiswa yang sudah mengumpulkan tampil di baris paling atas.
  * **Kenapa Diubah:**  
    Agar alur penilaian memenuhi spesifikasi wajib **F9 (Notifikasi via Queue)** dan antarmuka dosen menjadi ergonomis serta mudah digunakan tanpa perlu scroll berulang kali.
* **Tempat Peragaan:** VS Code file `app/Http/Controllers/SubmissionController.php` & Browser Dosen.
* **Langkah Peragaan:**
  1. Buka `app/Http/Controllers/SubmissionController.php` baris 210–215, tunjukkan pemanggilan:
     ```php
     event(new \App\Events\SubmissionGraded($submission));
     ```
  2. Buka method `index` baris 38–43, tunjukkan pengurutan `sortByDesc` mahasiswa yang sudah mengumpulkan.
* **Kalimat ke Dosen Penguji:**  
  *"Awalnya AI lupa menambahkan event pemicu notifikasi saat dosen memberi nilai dan membuat tabel terlalu lebar. Saya memperbaiki arsitekturnya dengan menambahkan dispatch event SubmissionGraded agar notifikasi masuk ke worker antrean, serta merampingkan tampilan tabel agar responsif di layar laptop."*

---
*Catatan ini disusun oleh Armansyah (10241013) untuk pemenuhan Milestone M3 Praktikum Pemrograman Web.*
