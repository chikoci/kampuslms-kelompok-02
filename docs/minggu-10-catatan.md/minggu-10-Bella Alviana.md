# Catatan Praktikum Minggu 10: Realtime, Event, Queue, dan Notifikasi

**Nama:** Bella Alviana  
**NIM:** 10241015  
**Kelompok:** Kelompok 02  
**Kelas:** B / Sistem Informasi  

---

## 10.3 Read → Break → Fix → Build

---

### READ — Ikuti Satu Job Antrean (30 Menit)

Pada tahap ini, saya melakukan penelusuran mandiri pada alur asinkron Laravel 12: mulai dari pemanggilan *Event* (`AssignmentPublished`), peletakan job antrean di tabel `jobs`, penanganan oleh *Listener* (`SendAssignmentNotification`), hingga pembuatan baris notifikasi pada tabel `notifications`.

---

#### 1. Matikan worker. Buat satu tugas baru. Buka tabel `jobs` — apa isinya?
* **Langkah:**
  1. Memastikan pada `.env` konfigurasi queue disetel ke database: `QUEUE_CONNECTION=database`.
  2. Mematikan worker (`Ctrl + C` pada terminal `php artisan queue:work`).
  3. Memastikan antrean awal kosong:
     ```powershell
     php artisan tinker --execute="echo DB::table('jobs')->count();"
     ```
     *(Hasil awal: `0`)*.
  4. Login sebagai Dosen di peramban web dan membuat satu tugas baru pada mata kuliah Pemrograman Web.
  5. Memeriksa isi tabel `jobs` menggunakan Tinker:
     ```powershell
     php artisan tinker --execute="dump(DB::table('jobs')->select('id', 'queue', 'payload')->get());"
     ```
* **Hasil Pengamatan:**
  Muncul **1 baris pekerjaan baru** di tabel `jobs`:
  ```text
  Illuminate\Support\Collection^ {#7431
    #items: array:1 [
      0 => {#7428
        +"id": 4
        +"queue": "default"
        +"payload": "{"uuid":"b329d896-cd7f-4dbb-ae32-c090c8259a45","displayName":"App\\Listeners\\SendAssignmentNotification","job":"Illuminate\\Queue\\CallQueuedHandler@call",...}"
      }
    ]
  }
  ```
* **Analisis:**  
  Tugas berhasil tersimpan di tabel `assignments` dan halaman web dosen langsung merespons dengan cepat. Namun, pengiriman notifikasi ke mahasiswa tertahan di tabel `jobs` dan belum terkirim karena worker yang bertugas memproses antrean sedang mati (*silent failure*).

---

#### 2. Baca kolom `payload`. Bisakah Anda mengenali nama Listener dan ID tugasnya?
* **Langkah:**  
  Menganalisis string JSON di kolom `payload` yang tersimpan pada baris antrean tersebut.
* **Hasil Pengamatan:**
  Di dalam string `payload` ditemukan informasi serialized object dari Laravel:
  * **Nama Listener:** `"displayName":"App\\Listeners\\SendAssignmentNotification"`
  * **Class Pemanggil:** `"commandName":"Illuminate\\Events\\CallQueuedListener"`
  * **Event & ID Tugas:** `"class";s:21:"App\Models\Assignment";s:2:"id";i:20;`
* **Analisis:**  
  Laravel membungkus seluruh konteks eksekusi ke dalam format JSON serialized string. Worker tidak perlu menebak apa yang harus dikerjakan; worker cukup mendeserialisasi payload tersebut untuk mengetahui bahwa listener yang harus dijalankan adalah `SendAssignmentNotification` dengan membawa data model `Assignment` dengan ID `20`.

---

#### 3. Jalankan `php artisan queue:work`, amati keluarannya. Periksa tabel `jobs` lagi.
* **Langkah:**
  1. Menjalankan worker di terminal:
     ```powershell
     php artisan queue:work
     ```
  2. Mengamati keluaran terminal, lalu menghentikan worker dan memeriksa tabel `jobs` kembali via Tinker.
* **Hasil Pengamatan:**
  * **Terminal Worker:**
    ```text
    INFO  Processing jobs from the [default] queue.

    2026-10-27 12:48:06 App\Listeners\SendAssignmentNotification ............................................ RUNNING
    2026-10-27 12:48:06 App\Listeners\SendAssignmentNotification ...................................... 126.60ms DONE
    ```
  * **Pemeriksaan Tabel `jobs`:**
    ```powershell
    php artisan tinker --execute="dump(DB::table('jobs')->select('id', 'attempts', 'reserved_at')->get());"
    ```
    Menghasilkan `#items: []` (kosong).
* **Analisis:**  
  Worker mengambil job dari antrean, mengeksekusi method `handle()`, mengirimkan notifikasi ke mahasiswa, dan secara otomatis **menghapus baris pekerjaan tersebut dari tabel `jobs`** setelah statusnya `DONE`. Tabel `jobs` kembali bersih.

---

#### 4. Periksa tabel `notifications`. Berapa baris yang muncul? Cocok dengan jumlah mahasiswa?
* **Langkah:**  
  Menghitung dan melihat data notifikasi baru yang masuk di database:
  ```powershell
  php artisan tinker --execute="dump(DB::table('notifications')->latest()->first());"
  ```
* **Hasil Pengamatan:**  
  Baris notifikasi baru terbentuk pada tabel `notifications`. Jumlah baris notifikasi yang masuk persis sesuai dengan jumlah mahasiswa yang terdaftar (*enrolled*) pada mata kuliah tersebut.
  * `id`: Berupa UUID (contoh: `d1e2f3a4-5678-90ab-cdef-1234567890ab`).
  * `type`: `App\Notifications\NewAssignmentNotification`.
  * `notifiable_type`: `App\Models\User`.
  * `notifiable_id`: ID mahasiswa penerima.
  * `read_at`: `NULL` (karena belum dibuka oleh mahasiswa).
* **Analisis:**  
  Sistem berhasil memetakan relasi `assignment -> course -> students` dan mendistribusikan notifikasi secara tepat sasaran kepada seluruh mahasiswa peserta kelas tanpa duplikasi.

---

#### 5. Baca kolom `data` pada satu notifikasi. Adakah yang tidak seharusnya tersimpan di situ?
* **Langkah:**  
  Mengekstrak isi kolom `data` dari notifikasi terbaru:
  ```powershell
  php artisan tinker --execute='echo DB::table("notifications")->latest()->first()->data;'
  ```
* **Hasil Pengamatan:**
  ```json
  {
    "assignment_id": 24,
    "course_id": 1,
    "course_name": "Pemrograman Web",
    "title": "tugas 40",
    "due_at": "2026-11-02T20:47:00+00:00",
    "url": "http://localhost/assignments/24",
    "type": "assignment_published"
  }
  ```
* **Analisis Keamanan Data:**  
  **Tidak ada data yang tidak semestinya.** Data yang disimpan murni merupakan metadata publik tugas (ID, nama mata kuliah, judul tugas, batas waktu, dan URL). Tidak ada nilai mahasiswa, catatan evaluasi privat dosen, atau token rahasia yang disimpan. Ini sesuai dengan prinsip *Data Minimization*.

---
---

### BREAK — Delapan Kerusakan (50 Menit)

---

#### 1. Set `QUEUE_CONNECTION=database`, jangan jalankan worker, buat tugas baru
* **Prediksi:**  
  Tugas berhasil dibuat dan disimpan oleh dosen di antarmuka web, namun notifikasi gagal terkirim ke mahasiswa tanpa memunculkan satu pun pesan error (*silent failure*).
* **Langkah Percobaan:**
  1. Memastikan di `.env`:
     ```env
     QUEUE_CONNECTION=database
     ```
  2. Mematikan proses `php artisan queue:work` di terminal.
  3. Memeriksa jumlah awal tabel `jobs` (bernilai `0`).
  4. Login sebagai Dosen di browser, membuat tugas baru berjudul *"Tugas Uji Antrean"*, lalu menekan tombol **Simpan Tugas**.
  5. Login di browser lain sebagai Mahasiswa pada kelas tersebut, lalu memeriksa badge lonceng notifikasi di navbar.
  6. Memeriksa isi tabel antrean lewat Tinker:
     ```powershell
     php artisan tinker --execute="dump(DB::table('jobs')->select('id', 'queue', 'payload')->get());"
     ```
* **Hasil Sebenarnya / Pengamatan:**  
  * **Sisi Dosen:** Halaman berhasil redirect dengan mulus, muncul notifikasi sukses *"Tugas berhasil dibuat"*, status HTTP `302 -> 200 OK`. Tidak ada pesan error sama sekali.
  * **Sisi Mahasiswa:** Lonceng notifikasi tetap angka `0` (tidak ada notifikasi masuk).
  * **Sisi Database:** Ditemukan 1 baris job mengantre di tabel `jobs` dengan payload `App\Listeners\SendAssignmentNotification` untuk tugas tersebut.
* **Penjelasan / Analisis:**  
  Ini membuktikan fenomena **Silent Failure** pada arsitektur antrean asinkron. Web request dosen hanya bertugas menyisipkan (*insert*) pekerjaan ke tabel `jobs`. Karena kueri insert berhasil, HTTP request dinyatakan sukses. Mahasiswa tidak menerima notifikasi bukan karena aplikasinya rusak, melainkan karena tidak ada proses pekerja (*worker*) yang mengambil dan mengeksekusi antrean tersebut.

---

#### 2. Hapus `implements ShouldQueue` dari Listener, buat tugas untuk banyak mahasiswa
* **Prediksi:**  
  Browser dosen akan berputar (*loading*) lama saat menekan tombol simpan karena proses pembuatan dan pengiriman notifikasi dieksekusi secara sinkron di dalam siklus HTTP request saat itu juga.
* **Langkah Percobaan:**
  1. Membuka `app/Listeners/SendAssignmentNotification.php`.
  2. Menghapus sementara `implements ShouldQueue`:
     ```php
     class SendAssignmentNotification // implements ShouldQueue dihapus
     ```
  3. Menambahkan jeda simulasi `sleep(3);` di dalam method `handle()` untuk merepresentasikan beban jaringan/banyak mahasiswa.
  4. Login sebagai Dosen di web browser, lalu membuat 1 tugas baru dan menekan tombol simpan.
* **Hasil Sebenarnya / Pengamatan:**  
  Tab browser dosen mengalami *freeze/loading* berputar selama **3+ detik** sebelum halaman berpindah. Respon sistem terasa sangat lambat dan menyandera pengguna.
* **Penjelasan / Analisis:**  
  Tanpa `ShouldQueue`, listener berjalan secara *synchronous* dalam satu utas (*thread*) permintaan HTTP. Dosen dipaksa menunggu hingga seluruh loop pengiriman notifikasi selesai diproses oleh PHP. Jika mata kuliah memiliki 40 mahasiswa dan notifikasi dikirim via email SMTP, request dapat dengan mudah menembus batas waktu eksekusi (*HTTP 504 Gateway Timeout*).

---

#### 3. Ganti `QUEUE_CONNECTION=sync`, ulangi nomor 2
* **Prediksi:**  
  Job langsung dikerjakan seketika tanpa masuk ke tabel antrean `jobs`, namun sifat asinkronnya tetap hilang.
* **Langkah Percobaan:**
  1. Mengembalikan `implements ShouldQueue` pada `SendAssignmentNotification.php`.
  2. Mengubah konfigurasi `.env`:
     ```env
     QUEUE_CONNECTION=sync
     ```
  3. Membersihkan cache config: `php artisan config:clear`.
  4. Membuat tugas baru sebagai Dosen tanpa menyalakan `php artisan queue:work`.
  5. Memeriksa jumlah baris pada tabel `jobs` dan `notifications`:
     ```powershell
     php artisan tinker --execute="echo 'Jobs: ' . DB::table('jobs')->count() . ' | Notifications: ' . DB::table('notifications')->count();"
     ```
* **Hasil Sebenarnya / Pengamatan:**  
  Jumlah `Jobs` tetap **0** (tidak ada antrean yang masuk ke database), namun `Notifications` langsung bertambah seketika. Notifikasi langsung muncul di akun mahasiswa, tetapi request dosen tetap mengalami jeda eksekusi karena driver `sync` mengeksekusi job secara *inline*.
* **Penjelasan / Analisis:**  
  Driver `sync` mengeksekusi job secara langsung saat itu juga di dalam proses PHP yang sama tanpa menggunakan tabel antrean. Driver ini cocok untuk keperluan *Automated Testing* di lingkungan lokal, namun menghilangkan seluruh keunggulan performa queue di lingkungan produksi.

---

#### 4. Buat Notification melempar exception, jalankan worker
* **Prediksi:**  
  Worker akan mendeteksi error, melakukan percobaan ulang (*retry*) sesuai nilai `$tries` dan jeda `$backoff`. Jika terus gagal, data pekerjaan dipindahkan ke tabel `failed_jobs`.
* **Langkah Percobaan:**
  1. Membuka `app/Listeners/SendAssignmentNotification.php`, memastikan properti retry telah aktif:
     ```php
     public int $tries = 3;
     public int $backoff = 3;
     ```
  2. Menyisipkan error buatan di awal method `handle()`:
     ```php
     throw new \Exception("Simulasi error: Koneksi database notifikasi terputus!");
     ```
  3. Memastikan `QUEUE_CONNECTION=database`, lalu membuat 1 tugas baru sebagai Dosen.
  4. Menjalankan worker di terminal: `php artisan queue:work`.
  5. Mengamati log worker dan memeriksa tabel job gagal via artisan:
     ```powershell
     php artisan queue:failed
     ```
* **Hasil Sebenarnya / Pengamatan:**  
  * Pada terminal worker: Tercetak status `RUNNING`, lalu `FAIL (25.42ms)`, menunggu 3 detik, mencoba lagi hingga batas maksimal (3 kali).
  * Pada kegagalan ke-3, worker mencetak: *Has been attempted too many times. Marking job as failed*.
  * Perintah `php artisan queue:failed` menampilkan data pekerjaan yang gagal lengkap dengan UUID:
    ```text
    2026-10-27 12:44:30 f4030ae5-401b-4d2f-91c8-e44cd11a04d9 database@default App\Listeners\SendAssignmentNotification
    ```
* **Penjelasan / Analisis:**  
  Fitur resiliensi Laravel menjamin pekerjaan tidak langsung dibuang ketika terjadi error sesaat (*transient failure*). Jika error menetap setelah 3 kali percobaan, job disimpan permanen di tabel `failed_jobs` lengkap dengan payload dan jejak error (*stack trace*), sehingga administrator dapat memperbaiki masalahnya dan menjalankan ulang antrean menggunakan perintah `php artisan queue:retry all`.

---

#### 5. Hentikan worker di tengah pemrosesan job
* **Prediksi:**  
  Pekerjaan yang sedang berjalan saat worker dimatikan secara mendadak tidak akan hilang dari database dan akan diambil kembali oleh worker baru (*At-Least-Once Delivery*).
* **Langkah Percobaan:**
  1. Menambahkan jeda `sleep(10);` di dalam method `handle()` pada `SendAssignmentNotification.php`.
  2. Membuat 1 tugas baru sebagai Dosen.
  3. Menjalankan worker di terminal: `php artisan queue:work`.
  4. Begitu terminal menampilkan status `App\Listeners\SendAssignmentNotification ..... RUNNING`, segera hentikan paksa prosesnya (menekan `Ctrl + C` atau menutup paksa jendela terminal).
  5. Memeriksa kondisi baris pekerjaan di tabel `jobs` menggunakan Tinker:
     ```powershell
     php artisan tinker --execute="dump(DB::table('jobs')->select('id', 'attempts', 'reserved_at')->get());"
     ```
  6. Menjalankan kembali `php artisan queue:work`.
* **Hasil Sebenarnya / Pengamatan:**  
  * Data pekerjaan **tidak hilang** dari tabel `jobs`. Kolom `attempts` bertambah menjadi `1` dan `reserved_at` sempat terisi penanda waktu kunci (*lock*).
  * Begitu worker baru dinyalakan kembali, job yang sempat terputus tersebut otomatis diambil ulang dan diproses dari awal hingga tuntas (`DONE`).
* **Penjelasan / Analisis:**  
  Laravel menggunakan mekanisme *reservation locking*. Ketika worker mengambil job, ia menandai kolom `reserved_at`. Jika worker mati mendadak sebelum mengirimkan sinyal konfirmasi selesai (*acknowledgement*), status reservasi tersebut akan kadaluarsa (*timeout*) dan dilepas kembali ke antrean, mencegah terjadinya kehilangan data pekerjaan penting.

---

#### 6. Masukkan nilai mahasiswa ke payload notifikasi, lalu buka tabel `notifications` sebagai admin
* **Prediksi:**  
  Data nilai mahasiswa tersimpan dalam bentuk teks JSON biasa tanpa enkripsi dan dapat dibaca oleh pihak yang tidak berhak (*Information Disclosure*).
* **Langkah Percobaan:**
  1. Membuka file notifikasi `app/Notifications/NewAssignmentNotification.php`.
  2. Menambahkan data sensitif ke dalam array `toDatabase()`:
     ```php
     public function toDatabase(object $notifiable): array
     {
         return [
             'assignment_id'   => $this->assignment->id,
             'title'           => $this->assignment->title,
             // PELANGGARAN PRIVASI:
             'student_score'   => 45,
             'feedback'        => 'Tugas terindikasi plagiat 85%.',
         ];
     }
     ```
  3. Memicu pengiriman notifikasi dan menjalankan worker.
  4. Mengambil isi kolom `data` pada tabel `notifications`:
     ```powershell
     php artisan tinker --execute='echo DB::table("notifications")->latest()->first()->data;'
     ```
* **Hasil Sebenarnya / Pengamatan:**  
  Kolom `data` menampilkan teks JSON mentah:
  `{"assignment_id":24,"title":"tugas 40","student_score":45,"feedback":"Tugas terindikasi plagiat 85%."}`.
  Siapa pun yang memiliki hak akses baca ke database, membaca file backup `.sql`, atau melihat antarmuka log admin dapat membaca nilai dan catatan evaluasi privat mahasiswa tersebut.
* **Penjelasan / Analisis:**  
  Tabel notifikasi tidak terenkripsi secara default. Sesuai prinsip **Data Minimization**, payload notifikasi **hanya boleh menyimpan metadata publik** (ID, nama mata kuliah, judul, batas waktu, dan URL). Nilai asli dan feedback harus selalu ditarik langsung dari controller yang dilindungi oleh Gate & Policy otorisasi.

---

#### 7. Panggil endpoint "tandai dibaca" dengan ID notifikasi orang lain
* **Prediksi:**  
  Jika controller mencari notifikasi secara global tanpa filter relasi pengguna yang sedang login, mahasiswa A dapat mengubah status notifikasi milik mahasiswa B (terjadi celah IDOR).
* **Langkah Percobaan:**
  1. Menyiapkan dua akun: Mahasiswa A (ID `2`) dan Mahasiswa B (ID `3`).
  2. Mengambil UUID notifikasi yang belum dibaca milik Mahasiswa B:
     ```powershell
     php artisan tinker --execute="echo App\Models\User::find(3)->unreadNotifications->first()->id;"
     ```
     *(Misal UUID: `c4b12345-6789-abcd-ef01-23456789abcd`)*.
  3. Login ke peramban sebagai **Mahasiswa A**.
  4. Menguji endpoint `markAsRead`:
     * **Skenario Rentan (Tanpa Proteksi Kepemilikan):**
       ```php
       DatabaseNotification::findOrFail($id)->markAsRead(); // ❌ IDOR: Mencari secara global
       ```
     * **Skenario Aman (Proteksi Terpasang pada KampusLMS):**
       ```php
       $request->user()->unreadNotifications()->findOrFail($id)->markAsRead(); // ✅ Aman
       ```
  5. Mengirimkan permintaan POST untuk menandai ID milik Mahasiswa B menggunakan sesi Mahasiswa A.
* **Hasil Sebenarnya / Pengamatan:**  
  * Pada skenario rentan: Notifikasi Mahasiswa B tiba-tiba bertanda sudah dibaca (`read_at` terisi).
  * Pada skenario aman: Sistem menolak permintaan dan mengembalikan status **`HTTP 404 Not Found`** karena notifikasi tersebut tidak ditemukan dalam relasi data Mahasiswa A.
* **Penjelasan / Analisis:**  
  Ini membuktikan kerentanan **IDOR (*Insecure Direct Object Reference*) versi notifikasi**. Mencari data langsung melalui model global (`Model::findOrFail($id)`) tanpa mengikatnya ke `auth()->user()` memungkinkan manipulasi status data lintas pengguna. Menggunakan relasi `$request->user()->unreadNotifications()` secara otomatis menambahkan filter `WHERE notifiable_id = auth_id`.

---

#### 8. Kalau memakai Reverb: ganti `PrivateChannel` menjadi `Channel`, langganan channel orang lain
* **Prediksi:**  
  Menggunakan `Channel` publik memungkinkan peramban pengguna lain berlangganan (*subscribe*) ke aliran data personal pengguna lain tanpa proses autentikasi (penyadapan realtime).
* **Langkah Percobaan:**
  1. Memeriksa deklarasi saluran penyiaran (*broadcast channel*):
     * **Skenario Rentan (Publik):**
       ```php
       public function broadcastOn(): array
       {
           return [new Channel('user.' . $this->userId)]; // ❌ Saluran terbuka tanpa autentikasi
       }
       ```
     * **Skenario Aman (Privat):**
       ```php
       public function broadcastOn(): array
       {
           return [new PrivateChannel('user.' . $this->userId)]; // ✅ Wajib lolos guard routes/channels.php
       }
       ```
  2. Membuka dua browser: Browser 1 login sebagai Mahasiswa B (ID `3`), Browser 2 login sebagai Mahasiswa A (ID `2`).
  3. Pada Console Developer Tools (F12) browser Mahasiswa A, menjalankan langganan ke channel Mahasiswa B:
     ```javascript
     window.Echo.channel('user.3').listen('NewAssignmentNotification', (e) => {
         console.warn("NOTIFIKASI MAHASISWA B DISADAP:", e);
     });
     ```
* **Hasil Sebenarnya / Pengamatan:**  
  * Pada `Channel` publik: Console browser Mahasiswa A berhasil menerima data broadcast notifikasi milik Mahasiswa B secara realtime tanpa login sebagai Mahasiswa B.
  * Pada `PrivateChannel`: Permintaan langganan Mahasiswa A langsung ditolak oleh backend dengan respon **`HTTP 403 Forbidden`** melalui rute otorisasi `/broadcasting/auth`.
* **Penjelasan / Analisis:**  
  Saluran publik pada WebSocket tidak memerlukan jabat tangan (*handshake*) otorisasi. Siapa saja yang mengetahui format penamaan channel dapat melakukan penyadapan data (*Eavesdropping*). Saluran notifikasi pengguna wajib selalu menggunakan instansiasi `new PrivateChannel(...)` yang divalidasi pada `routes/channels.php`.

---
---

## 10.4 Checkpoint Minggu 10 — Gerbang Interview Tugas 3

- [x] Apa yang terjadi kalau queue worker mati saat notifikasi dikirim? Peragakan.
- [x] Kenapa kegagalan itu tidak menghasilkan pesan error? Bagaimana Anda akan mengetahuinya di server produksi?
- [x] Apa beda `QUEUE_CONNECTION=sync` dan `database`? Kapan masing-masing dipakai?
- [x] Tunjukkan Listener yang Anda tulis. Apa akibatnya kalau `ShouldQueue` dihapus?
- [x] Kenapa nilai mahasiswa tidak boleh masuk payload notifikasi?
- [x] Tunjukkan apa yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca.
- [x] Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?

---

### 1. Apa yang terjadi kalau queue worker mati saat notifikasi dikirim? Peragakan.
* **Jawaban:**  
  Data tugas tetap tersimpan di database dan dosen melihat tampilan berhasil tanpa pesan error apa pun (*silent failure*). Namun, tugas pengiriman notifikasi **tertahan di tabel `jobs`** dan tidak pernah sampai ke akun mahasiswa (lonceng notifikasi mahasiswa tetap 0) sampai worker dihidupkan kembali.
* **Langkah Peragaan:**
  1. Hentikan worker di terminal (`Ctrl + C`).
  2. Login sebagai Dosen di peramban, buat tugas baru pada kelas, klik **Simpan**.
  3. Buka jendela Incognito, login sebagai Mahasiswa: tunjukkan bahwa **lonceng notifikasi masih angka 0**.
  4. Buka terminal, tunjukkan baris pekerjaan yang tertahan di tabel `jobs`:
     ```powershell
     php artisan tinker --execute="echo DB::table('jobs')->count();"
     ```
  5. Nyalakan worker:
     ```powershell
     php artisan queue:work --once
     ```
  6. Refresh halaman Mahasiswa: seketika badge merah notifikasi muncul di ikon lonceng.

---

### 2. Kenapa kegagalan itu tidak menghasilkan pesan error? Bagaimana Anda akan mengetahuinya di server produksi?
* **Jawaban:**  
  * **Kenapa tidak ada error:** Karena tugas request web dosen hanyalah menyisipkan satu baris data antrean ke tabel `jobs` (`INSERT INTO jobs ...`). Operasi database ini berhasil 100%, sehingga web server merespons dengan status HTTP 302 sukses. Pengiriman notifikasi sesungguhnya diserahkan ke proses background terpisah (*worker*).
  * **Cara mengetahui di server produksi:**
    1. Memasang *process supervisor* (seperti `Supervisor` atau `systemd` di Linux) yang bertugas menjaga proses worker selalu hidup 24 jam dan otomatis merestartnya jika mati mendadak.
    2. Menjalankan perintah pemantau antrean Laravel:
       ```bash
       php artisan queue:monitor default --max=50
       ```
       Jika jumlah antrean menumpuk melebihi batas dan tidak berkurang, sistem mengirim peringatan ke Discord, Slack, atau email tim teknis.

---

### 3. Apa beda `QUEUE_CONNECTION=sync` dan `database`? Kapan masing-masing dipakai?
* **Jawaban:**  
  * **`sync` (Sinkron):** Pekerjaan dieksekusi detik itu juga di dalam request HTTP yang sama. Pengguna dipaksa menunggu hingga seluruh notifikasi selesai dikirim sebelum halaman berpindah.
  * **`database` (Asinkron):** Pekerjaan dicatat ke tabel antrean database dan dikerjakan oleh worker terpisah di latar belakang. Halaman web langsung merespons seketika (< 100 ms).
  * **Kapan dipakai:**
    * Mode `sync` dipakai saat **pengembangan lokal/unit testing** agar praktis tanpa perlu menyalakan terminal worker.
    * Mode `database` wajib dipakai di **lingkungan produksi nyata** agar pengguna tidak tersandera loading lama saat sistem mengirim data ke puluhan mahasiswa.

---

### 4. Tunjukkan Listener yang Anda tulis. Apa akibatnya kalau `ShouldQueue` dihapus?
* **Jawaban:**  
  Listener yang saya buat berada di file [`app/Listeners/SendAssignmentNotification.php`](file:///d:/kampuslms-kelompok-02/app/Listeners/SendAssignmentNotification.php):
  ```php
  namespace App\Listeners;

  use App\Events\AssignmentPublished;
  use App\Notifications\NewAssignmentNotification;
  use Illuminate\Contracts\Queue\ShouldQueue;
  use Illuminate\Support\Facades\Notification;

  class SendAssignmentNotification implements ShouldQueue
  {
      public int $tries = 3;
      public int $backoff = 10;

      public function handle(AssignmentPublished $event): void
      {
          $students = $event->assignment->course->students;

          if ($students->isNotEmpty()) {
              Notification::send($students, new NewAssignmentNotification($event->assignment));
          }
      }
  }
  ```
  * **Akibat jika `implements ShouldQueue` dihapus:**  
    Listener berubah menjadi proses sinkron. Saat dosen mempublikasikan tugas di kelas yang berisi 40 mahasiswa, Laravel akan mengeksekusi loop pengiriman notifikasi ke 40 mahasiswa saat dosen menekan tombol Simpan. Dosen dipaksa menunggu (browser berputar lama), dan jika satu pengiriman gagal atau timeout, seluruh transaksi pembuatan tugas bisa ikut gagal (*HTTP 500/504*).

---

### 5. Kenapa nilai mahasiswa tidak boleh masuk payload notifikasi?
* **Jawaban:**  
  1. **Tabel Notifikasi Tidak Terenkripsi:** Kolom `data` di tabel `notifications` menyimpan teks JSON biasa tanpa enkripsi. Siapa pun yang memiliki akses ke database, membaca file backup `.sql`, atau melihat log dapat membaca nilai rahasia mahasiswa.
  2. **Privasi Akademik (*Information Disclosure*):** Nilai adalah data sensitif yang hanya boleh diketahui oleh mahasiswa bersangkutan dan dosen pengampu (*prinsip Data Minimization*).
  3. **Mencegah Penyadapan Realtime:** Menghindari kebocoran nilai jika terjadi celah pada saluran penyiaran (*broadcast*).
  4. **Data Nilai Bersifat Dinamis:** Jika dosen meralat nilai mahasiswa (misal dari 70 diperbaiki menjadi 85), teks notifikasi lama yang memuat angka 70 akan menjadi tidak konsisten.
  * *Solusi:* Payload notifikasi hanya memuat referensi ID dan judul tugas. Nilai asli selalu ditarik langsung dari controller yang dilindungi oleh Gate & Policy otorisasi.

---

### 6. Tunjukkan apa yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca.
* **Jawaban:**  
  Pencegahan serangan IDOR diterapkan pada controller [`app/Http/Controllers/NotificationController.php`](file:///d:/kampuslms-kelompok-02/app/Http/Controllers/NotificationController.php) di method `markAsRead`:
  ```php
  public function markAsRead(Request $request, string $id)
  {
      $notification = $request->user()
          ->unreadNotifications()
          ->where('id', $id)
          ->firstOrFail();

      $notification->markAsRead();

      return back()->with('success', 'Notifikasi telah ditandai sudah dibaca.');
  }
  ```
  * **Analisis Proteksi:**  
    Pencarian notifikasi tidak dilakukan secara global (`Notification::findOrFail($id)`), melainkan dikunci melalui relasi pengguna yang sedang aktif: `$request->user()->unreadNotifications()`. Laravel secara otomatis menambahkan klausul `WHERE notifiable_id = [auth_user_id]`. Jika Mahasiswa A memasukkan ID notifikasi milik Mahasiswa B, query menghasilkan null dan Laravel melempar respons **`HTTP 404 Not Found`**.

---

### 7. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?
* **Jawaban:**  
  * **Bagian yang Dibuat dengan Bantuan AI:** Pembuatan listener [`SendAssignmentNotification.php`](file:///d:/kampuslms-kelompok-02/app/Listeners/SendAssignmentNotification.php) dan kelas notifikasi [`NewAssignmentNotification.php`](file:///d:/kampuslms-kelompok-02/app/Notifications/NewAssignmentNotification.php).
  * **Saran Awal dari AI:**  
    AI awalnya menyarankan loop manual menggunakan `foreach ($students as $student) { $student->notify(...); }` dan tidak menyertakan konfigurasi percobaan ulang jika antrean gagal.
  * **Apa yang Saya Ubah dan Kenapa:**  
    1. **Mengubah loop manual menjadi `Notification::send($students, ...)`:** Menggunakan facade `Notification::send` agar Laravel mengoptimalkan pengiriman massal ke seluruh koleksi mahasiswa secara batch dan efisien, menghindari overhead query berulang (*N+1 problem*).
    2. **Menambahkan Ketahanan Antrean (`$tries = 3` dan `$backoff = 10`):** Menjamin jika terjadi kendala database atau jaringan sesaat, pekerjaan tidak langsung dibuang, melainkan dicoba ulang sebanyak 3 kali dengan jeda 10 detik sebelum masuk ke tabel `failed_jobs`.
    3. **Menyederhanakan Payload:** Memastikan method `toDatabase()` hanya memuat metadata umum tugas tanpa memasukkan data sensitif.

---
*Dokumen ini disusun oleh Bella Alviana (10241015) sebagai dokumentasi resmi pemenuhan Milestone M3 Praktikum Pemrograman Web.*
