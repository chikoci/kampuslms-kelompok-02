# Catatan Praktikum Minggu 10: Realtime, Event, Queue, dan Notifikasi

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Telusuri Satu Job Antrean

Pada tahap ini, saya menelusuri bagaimana antrean (*queue*) bekerja saat tugas baru dibuat, membaca payload job di tabel database, memprosesnya dengan worker, dan melihat hasil notifikasi yang sampai ke mahasiswa.

### 1. Kondisi tabel `jobs` saat worker dimatikan
Ketika dosen membuat tugas baru dan antrean diatur ke database (`QUEUE_CONNECTION=database`), tetapi queue worker dalam kondisi mati:
* Tugas tetap berhasil disimpan di database. Dosen tidak melihat pesan error apa pun di browser.
* Namun, pada tabel `jobs` muncul 1 baris antrean baru. Notifikasi belum terkirim ke mahasiswa karena belum ada worker yang mengeksekusi antrean tersebut.

### 2. Membaca isi kolom `payload` di tabel `jobs`
Di dalam kolom `payload` tabel `jobs`, tersimpan data JSON serialisasi yang memuat:
* `displayName`: Nama class listener yang akan dipanggil, yaitu `App\Listeners\SendAssignmentNotification`.
* `data.command`: Berisi objek event `App\Events\AssignmentPublished` beserta data tugas (ID tugas, judul tugas, ID mata kuliah). Dari data ini, worker tahu listener mana yang harus dijalankan dan data tugas apa yang dibawa.

### 3. Menjalankan worker `php artisan queue:work`
Saat worker dijalankan di terminal:
* Worker membaca baris antrean di tabel `jobs`, lalu mencetak tulisan:
  `Processing: App\Listeners\SendAssignmentNotification`
  diikuti dengan:
  `Processed: App\Listeners\SendAssignmentNotification`.
* Setelah selesai diproses, baris job tersebut otomatis dihapus dari tabel `jobs` (tabel kembali kosong) karena tugas antrean sudah selesai dikerjakan.

### 4. Memeriksa tabel `notifications`
* Pada tabel `notifications`, muncul sejumlah baris baru yang sesuai dengan jumlah mahasiswa yang terdaftar di kelas tersebut (misal ada 3 mahasiswa terdaftar, maka muncul 3 baris).
* Kolom `notifiable_id` mencatat ID masing-masing mahasiswa penerima, dan kolom `read_at` bernilai `NULL` (menandakan notifikasi belum dibaca).

### 5. Memeriksa isi kolom `data` pada notifikasi
Kolom `data` berisi payload JSON:
* Berisi `assignment_id`, `course_code`, `assignment_title`, `type`, `message`, dan tautan `link`.
* Terbukti **tidak ada data sensitif** yang tersimpan di kolom ini (tidak ada nilai ujian atau komentar feedback), sehingga aman dan terhindar dari kebocoran privasi akademik.

---

## Bagian BREAK: Eksperimen Kerusakan & Keamanan

*(Bagian ini akan dicoba dan diisi sendiri setelah sesi pengujian fitur selesai).*

---

## Bagian CHECKPOINT: Gerbang Interview Tugas 3 (10.4)

Panduan jawaban 7 soal Checkpoint 10.4 yang **ringkas, to the point, dan 100% peragaan visual lewat Browser + Perintah Terminal Pendek (tanpa ribet mengetik Tinker)**:

---

### 1. Apa yang terjadi kalau queue worker mati saat notifikasi dikirim? Peragakan.
* **Jawabannya:**  
  Tugas tetap tersimpan normal di database dan dosen tidak melihat error apa pun. Namun, pengiriman notifikasi tertahan di antrean dan notifikasi tidak pernah sampai ke mahasiswa (lonceng mahasiswa tetap kosong) sampai worker dihidupkan kembali.
* **Tempat Peragaan:** Browser Chrome & Terminal Git Bash.
* **Akun yang Dipakai:**  
  * Dosen: `dosen@kampuslms.test` | Password: `password`
  * Mahasiswa: `mahasiswa@kampuslms.test` | Password: `password`
* **Langkah Peragaan (Cukup Browser + 1 Perintah Terminal):**
  1. Pastikan terminal worker sedang mati (tekan `Ctrl + C` jika menyala).
  2. Di browser biasa, login sebagai Dosen: buat tugas baru di kelas Pemrograman Web dengan status **Dipublikasikan**, klik **Simpan**.
  3. Buka browser incognito, login sebagai Mahasiswa: tunjukkan ke dosen bahwa **ikon lonceng di navbar masih KOSONG (0 notifikasi)** karena worker mati (*silent failure*).
  4. Buka terminal, hidupkan worker sekali saja dengan perintah pendek:
     ```bash
     php artisan queue:work --once
     ```
     *(Terminal langsung mencetak: `Processing` lalu `DONE`)*.
  5. Kembali ke browser Mahasiswa dan refresh: seketika **badge merah angka 1 muncul di lonceng**, dan saat diklik notifikasi tugas baru langsung muncul.
* **Kalimat ke Dosen:**  
  *"Bisa dilihat Pak/Bu, saat worker mati tugas tetap tersimpan tapi lonceng mahasiswa kosong. Begitu worker kita jalankan satu kali di terminal, notifikasinya seketika sampai ke lonceng mahasiswa. Ini bukti nyata alur asinkron dan silent failure."*

---

### 2. Kenapa kegagalan itu tidak menghasilkan pesan error? Bagaimana Anda akan mengetahuinya di server produksi?
* **Jawabannya:**  
  * **Kenapa tidak error:** Karena tugas controller selesai begitu job berhasil dimasukkan ke tabel antrean database (status HTTP 302 sukses). Web server tidak menunggu notifikasi dikirim.
  * **Cara mengetahui di server produksi:**
    1. Menggunakan pemantau antrean resmi Laravel: perintah `php artisan queue:monitor default --max=50` yang akan memberi sinyal alert jika antrean menumpuk melewati batas toleransi.
    2. Memasang *process supervisor* (seperti `Supervisor` di Linux) yang otomatis merestart worker jika mati mendadak.
* **Tempat Peragaan:** Terminal Git Bash.
* **Langkah Peragaan:**
  1. Di terminal, jalankan perintah monitor antrean:
     ```bash
     php artisan queue:monitor default
     ```
     *(Tunjukkan tabel rapi di terminal yang menampilkan antrean `default`, jumlah job, dan statusnya)*.
* **Kalimat ke Dosen:**  
  *"Tidak ada error karena request web dosen hanya bertugas menitipkan job ke database. Di server produksi, kita mengetahuinya dengan perintah bawaan php artisan queue:monitor dan memasang Supervisor agar worker selalu aktif otomatis."*

---

### 3. Apa beda `QUEUE_CONNECTION=sync` dan `database`? Kapan masing-masing dipakai?
* **Jawabannya:**  
  * **`sync` (Sinkron):** Job dikerjakan saat itu juga di dalam request HTTP yang sama (tanpa antrean latar belakang). Browser dosen akan loading menunggu sampai notifikasi selesai dikirim.
  * **`database` (Asinkron):** Job disimpan ke tabel database dan dikerjakan oleh worker terpisah di latar belakang. Respons halaman langsung kembali ke dosen tanpa menunggu.
  * **Kapan masing-masing dipakai:**
    * `sync` hanya dipakai untuk **pengujian lokal sederhana (testing)** agar tidak perlu membuka terminal worker.
    * `database` wajib dipakai di **server produksi** atau saat mengirim tugas ke kelas dengan banyak mahasiswa agar web tetap cepat dan responsif.
* **Tempat Peragaan:** VS Code file `.env`.
* **Langkah Peragaan:**
  1. Buka file `.env` baris 33 di VS Code.
  2. Tunjukkan baris `QUEUE_CONNECTION=database`.
* **Kalimat ke Dosen:**  
  *"Perbedaannya pada lokasi eksekusinya Pak/Bu. Mode sync mengeksekusi langsung di request web dan membuat user menunggu, cocok untuk tes lokal. Sedangkan mode database memindahkan proses ke antrean worker latar belakang, wajib untuk produksi."*

---

### 4. Tunjukkan Listener yang Anda tulis. Apa akibatnya kalau `ShouldQueue` dihapus?
* **Jawabannya:**  
  Jika kontrak `ShouldQueue` dihapus, listener akan berubah menjadi proses sinkron. Akibatnya, saat dosen membuat tugas di kelas dengan puluhan mahasiswa, browser dosen akan *loading* berputar lama karena server harus menyisipkan puluhan notifikasi satu per satu sebelum merespons.
* **Tempat Peragaan:** VS Code file `app/Listeners/SendAssignmentNotification.php`.
* **Langkah Peragaan:**
  1. Buka file `app/Listeners/SendAssignmentNotification.php` di VS Code.
  2. Tunjukkan baris 12:
     ```php
     class SendAssignmentNotification implements ShouldQueue
     ```
  3. Tunjukkan properti penanganan kegagalan di baris 19-24 (`$tries = 3`, `$backoff = 10`) dan method `failed()` di baris 43.
* **Kalimat ke Dosen:**  
  *"Ini listener kami Pak/Bu. Jika ShouldQueue dihapus, listener berjalan sinkron dan web dosen akan hang atau freeze saat mengirim tugas ke kelas besar. Dengan ShouldQueue, pengiriman berjalan di antrean latar belakang."*

---

### 5. Kenapa nilai mahasiswa tidak boleh masuk payload notifikasi?
* **Jawabannya:**  
  Kolom `data` di tabel `notifications` tersimpan dalam format teks JSON terbuka tanpa enkripsi. Tabel ini mudah dilihat oleh admin, pengembang, atau tersimpan di file cadangan (*backup*). Jika nilai ujian (`score`) dimasukkan ke payload, terjadi **kebocoran privasi akademik (*Academic Data Leakage*)**. Nilai asli mahasiswa harus tetap terlindungi di tabel `submissions` yang hanya bisa diakses lewat verifikasi Gate/Policy.
* **Tempat Peragaan:** VS Code & Browser Mahasiswa.
* **Langkah Peragaan:**
  1. Di VS Code, buka `app/Notifications/SubmissionGradedNotification.php` baris 32-41:
     Tunjukkan array `toArray` hanya berisi judul tugas, ID, pesan pengumuman, dan tautan halaman tanpa ada nilai angka maupun komentar dosen.
  2. Di browser Mahasiswa: klik lonceng notifikasi, tunjukkan pesan notifikasinya hanya bertuliskan *"Nilai tugas Anda untuk Tugas 1 telah diberikan"* dan mahasiswa harus mengklik link tersebut untuk melihat nilainya di halaman resmi yang berotorisasi.
* **Kalimat ke Dosen:**  
  *"Nilai dan feedback tidak boleh masuk payload notifikasi demi menjaga privasi akademik mahasiswa. Kolom data di database hanya berisi pengumuman dan tautan, sedangkan nilai aslinya tetap aman di tabel submissions yang dilindungi Policy."*

---

### 6. Tunjukkan apa yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca.
* **Jawabannya:**  
  Pencegahannya adalah pemeriksaan kepemilikan (**Anti-IDOR**) di controller. Controller tidak mencari ID notifikasi secara global, melainkan membatasi pencarian **hanya melalui relasi user yang sedang login**: `$request->user()->notifications()->findOrFail($id)`. Jika ada mahasiswa yang mencoba mengirimkan ID notifikasi milik mahasiswa lain, sistem otomatis melempar respons **HTTP 404 (Not Found)**.
* **Tempat Peragaan:** VS Code & Terminal Git Bash.
* **Langkah Peragaan:**
  1. Di VS Code, buka `app/Http/Controllers/NotificationController.php` baris 26 pada method `markAsRead`:
     ```php
     $notification = $request->user()->notifications()->findOrFail($id);
     ```
  2. Di terminal, jalankan automated test Anti-IDOR:
     ```bash
     php artisan test --filter=test_anti_idor_student_cannot_mark_other_student_notification
     ```
     *(Tunjukkan hasil tes langsung lulus: `PASS` hijau)*.
* **Kalimat ke Dosen:**  
  *"Sistem kami terlindungi dari IDOR karena kueri pencarian dikunci lewat relasi user login. Jika mahasiswa mencoba menandai notifikasi milik orang lain, kueri menghasilkan null dan Laravel otomatis melempar 404 Not Found seperti yang dibuktikan di automated test ini."*

---

### 7. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?
* **Jawabannya:**  
  * **Bagian yang Dibuat:** Registrasi Event-Listener pada [AppServiceProvider.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Providers/AppServiceProvider.php) dan kueri pengiriman pada [SendAssignmentNotification.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Listeners/SendAssignmentNotification.php).
  * **Apa yang Diubah:**
    1. AI awalnya mendaftarkan event listener manual dengan `Event::listen(...)` di file `AppServiceProvider.php`. Karena Laravel 12 sudah memiliki fitur *Event Auto-Discovery*, registrasi manual itu menyebabkan listener terdaftar dua kali (saat dicek di terminal, listener muncul dobel). Saya menghapus registrasi manualnya agar rapi dan memanfaatkan auto-discovery bawaan Laravel 12.
    2. Pada listener, AI awalnya memberikan contoh perulangan `foreach ($students as $student) { $student->notify(...); }`. Saya ubah menjadi pemanggilan massal: `Notification::send($students, new NewAssignmentNotification(...));`.
  * **Kenapa Diubah:**  
    Untuk mencegah satu job dieksekusi dobel di antrean worker, serta mencegah masalah performa N+1 saat mengirim notifikasi ke banyak mahasiswa.
* **Tempat Peragaan:** Terminal Git Bash & VS Code.
* **Langkah Peragaan:**
  1. Di terminal, jalankan perintah untuk membuktikan listener terdaftar tepat satu kali:
     ```bash
     php artisan event:list
     ```
     *(Tunjukkan `App\Events\AssignmentPublished` hanya memiliki 1 listener `@handle`)*.
  2. Buka `app/Listeners/SendAssignmentNotification.php` baris 33-37 di VS Code, tunjukkan baris `Notification::send($students, ...)`.
* **Kalimat ke Dosen:**  
  *"Awalnya AI menyarankan registrasi manual di provider dan loop foreach. Saya ubah karena Laravel 12 sudah punya Event Discovery otomatis agar job tidak terkirim dua kali, dan saya ubah ke Notification::send massal agar query efisien dan bebas dari masalah N+1."*
