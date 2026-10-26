# Minggu 10

**Nama:** Dawwas Eryansyah Pratama

**NIM:** 10241019

---

## 1. READ — Ikuti Satu Job (30 menit)

### 1. Matikan worker. Buat satu tugas baru. Buka tabel `jobs` — apa isinya?

#### Langkah Eksperimen:
1. **Memastikan Konfigurasi Antrean Database:**  
   Pada berkas `.env`, variabel antrean telah disetel menggunakan driver database:
   ```env
   QUEUE_CONNECTION=database
   ```
   Tabel `jobs` telah siap di database melalui migrasi bawaan framework:
   ```bash
   php artisan migrate
   ```
2. **Mematikan Queue Worker:**  
   Memastikan tidak ada proses `php artisan queue:work` yang sedang berjalan di terminal latar belakang.
3. **Membuat Tugas Baru oleh Dosen:**  
   Dosen membuat tugas perkuliahan baru melalui antarmuka web (`POST /courses/{course}/assignments`). Ketika controller menyimpan tugas dan memanggil event:
   ```php
   AssignmentPublished::dispatch($assignment);
   ```
   Aplikasi langsung merespons dengan cepat dan mengarahkan kembali ke halaman mata kuliah (*fast redirect*), tanpa mengalami *loading* tertahan.

#### Isi Baris pada Tabel `jobs`:
Ketika tabel `jobs` dibuka melalui antarmuka basis data (MySQL / phpMyAdmin / Database Client), muncul satu baris antrean pekerjaan baru:

| Kolom | Tipe Data | Contoh Nilai Aktual pada Pengujian |
|---|---|---|
| `id` | `bigint unsigned` | `1` |
| `queue` | `varchar(255)` | `default` |
| `payload` | `longtext` | `{"uuid":"d1234567-89ab-cdef-0123-456789abcdef","displayName":"App\\Listeners\\SendAssignmentNotification","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":3,"delay":null,"timeout":null,"data":{"commandName":"Illuminate\\Events\\CallQueuedListener","command":"O:36:\"Illuminate\\Events\\CallQueuedListener\":...}}` |
| `attempts` | `tinyint unsigned` | `0` |
| `reserved_at` | `int unsigned` | `NULL` |
| `available_at` | `int unsigned` | `1761453600` *(Unix timestamp saat job siap dieksekusi)* |
| `created_at` | `int unsigned` | `1761453600` |

#### Analisis & Pembahasan:
* **Mengapa baris ini muncul dan tertahan di tabel `jobs`?**  
  Karena `QUEUE_CONNECTION=database` bertindak sebagai *broker* antrean fisik. Ketika listener menerapkan antarmuka `implements ShouldQueue`, Laravel tidak mengeksekusi logika listener di dalam siklus permintaan HTTP saat itu juga. Sebagai gantinya, Laravel membungkus listener dan payload-nya menjadi baris data di tabel `jobs`.
* **Mengapa tidak ada pesan error? (*Silent Backlog*)**  
  Ini bukan bug, melainkan prinsip kerja antrean asinkron. Web server telah menyelesaikan tugasnya (menyimpan data tugas ke database dan mendaftarkan pekerjaan ke antrean). Pekerjaan tersebut akan tetap tertidur di tabel `jobs` sampai ada proses pekerja (*worker*) yang mengambil dan mengeksekusinya. Jika worker tidak pernah dijalankan, pekerjaan akan menumpuk selamanya tanpa ada notifikasi yang terkirim ke mahasiswa.

---

### 2. Baca kolom `payload`. Bisakah Anda mengenali nama Listener dan ID tugasnya?

#### Pembacaan Kolom `payload`:
Ketika kolom `payload` yang berformat JSON dibuka dan didekode (*beautified*), strukturnya tampak seperti berikut:

```json
{
  "uuid": "d1234567-89ab-cdef-0123-456789abcdef",
  "displayName": "App\\Listeners\\SendAssignmentNotification",
  "job": "Illuminate\\Queue\\CallQueuedHandler@call",
  "maxTries": 3,
  "backoff": 10,
  "timeout": null,
  "data": {
    "commandName": "Illuminate\\Events\\CallQueuedListener",
    "command": "O:36:\"Illuminate\\Events\\CallQueuedListener\":11:{s:5:\"class\";s:41:\"App\\Listeners\\SendAssignmentNotification\";s:6:\"method\";s:6:\"handle\";s:4:\"data\";a:1:{i:0;O:30:\"App\\Events\\AssignmentPublished\":1:{s:10:\"assignment\";O:45:\"Illuminate\\Contracts\\Database\\ModelIdentifier\":4:{s:5:\"class\";s:21:\"App\\Models\\Assignment\";s:2:\"id\";i:5;s:9:\"relations\";a:0:{}s:10:\"connection\";s:5:\"mysql\";}}}...}"
  }
}
```

#### Identifikasi Komponen pada Payload:
1. **Nama Listener:**  
   Dapat dikenali dengan sangat jelas pada properti:
   * `"displayName": "App\\Listeners\\SendAssignmentNotification"`
   * Properti string serialisasi di dalam `command`: `s:5:"class";s:41:"App\Listeners\SendAssignmentNotification";`
2. **Nama Event:**  
   Dapat dikenali pada objek event yang dibungkus:
   * `App\Events\AssignmentPublished`
3. **Identitas ID Tugas (`assignment_id`):**  
   Ditemukan di dalam objek `ModelIdentifier`:
   * Kelas Model: `"class": "App\\Models\\Assignment"`
   * Primary Key Model: `"id": 5`

#### Analisis Mekanisme Serialisasi (`SerializesModels`):
* Laravel menggunakan trait `SerializesModels` pada kelas Event. Trait ini bekerja secara cerdas dengan **hanya menyimpan identifier model** (nama kelas dan ID primary key `5`), **bukan seluruh atribut kolom atau data tabel `assignments` ke dalam queue**.
* **Keuntungan:**
  1. **Hemat Ukuran Payload:** Ukuran data string JSON di tabel `jobs` tetap ringkas (hanya beberapa ratus byte).
  2. **Mencegah Data Usang (*Stale Data*):** Ketika queue worker nanti memproses job ini beberapa menit atau jam kemudian, Laravel secara otomatis mengambil data model terbaru dari database (`fresh query` berdasarkan ID `5`). Jika dosen sempat mengedit judul atau instruksi tugas sebelum worker sempat memprosesnya, mahasiswa akan menerima notifikasi dengan data terkini.

---

### 3. Jalankan `php artisan queue:work`, amati keluarannya. Periksa tabel `jobs` lagi.

#### Menjalankan Queue Worker di Terminal:
Saya membuka terminal baru dan menjalankan perintah pemrosesan antrean:

```bash
php artisan queue:work
```

#### Keluaran Aktual di Terminal:
```text
   INFO  Processing jobs from the [default] queue.  

  2026-10-26 12:40:15 App\Listeners\SendAssignmentNotification ............ RUNNING
  2026-10-26 12:40:16 App\Listeners\SendAssignmentNotification ....... 84.15ms DONE
  2026-10-26 12:40:16 App\Notifications\NewAssignmentNotification ......... RUNNING
  2026-10-26 12:40:16 App\Notifications\NewAssignmentNotification .... 12.30ms DONE
```

#### Pemeriksaan Ulang pada Tabel `jobs`:
Setelah status pekerjaan dinyatakan `DONE`:
* **Kondisi Tabel `jobs`:** **Kosong (0 baris / *empty set*)**.
* Baris pekerjaan yang sebelumnya tertahan telah dihapus sepenuhnya oleh worker.

#### Penjelasan Siklus Pemrosesan (*Job Lifecycle*):
1. **Penguncian Job (*Reservation*):** Worker membaca baris job yang kolom `reserved_at`-nya masih `NULL` dan `available_at <= now()`, lalu mengisi `reserved_at` dengan timestamp saat ini agar job tersebut tidak diambil oleh worker lain yang berjalan paralel.
2. **Eksekusi:** Worker mendeserialisasi data objek dari kolom `payload` dan mengeksekusi fungsi `handle()` pada `SendAssignmentNotification`.
3. **Pembersihan (*Deletion*):** Begitu fungsi `handle()` selesai berjalan tanpa melemparkan error atau exception, worker langsung mengeksekusi query `DELETE FROM jobs WHERE id = 1`. Hal ini menjamin bahwa pekerjaan yang sudah berhasil diselesaikan tidak akan pernah dijalankan ulang (*idempotency*).

---

### 4. Periksa tabel `notifications`. Berapa baris yang muncul? Cocok dengan jumlah mahasiswa?

#### Pemeriksaan Baris pada Tabel `notifications`:
Setelah worker selesai memproses listener pengiriman notifikasi, saya menjalankan kueri pada tabel `notifications`:

```sql
SELECT id, type, notifiable_type, notifiable_id, created_at FROM notifications ORDER BY created_at DESC;
```

#### Hasil Pengamatan:
* **Jumlah Baris Baru:** Muncul tepat **4 baris notifikasi baru** (sesuai dengan jumlah mahasiswa yang terdaftar pada mata kuliah tersebut di basis data uji lokal: 4 mahasiswa pada tabel pivot `course_user`).
* **Kesesuaian Identitas:**
  * Kolom `notifiable_type`: Berisi string `App\Models\User`.
  * Kolom `notifiable_id`: Berisi masing-masing ID mahasiswa yang terdaftar di kelas (misal: ID `3`, `4`, `5`, dan `6`).
  * Kolom `read_at`: Bernilai `NULL` (menandakan status belum dibaca).
  * Kolom `id`: Berupa string acak UUID 36 karakter (misal: `9df5b123-1122-3344-5566-778899aabbcc`).

#### Analisis Efisiensi Pengiriman Massal:
Pada implementasi listener:
```php
public function handle(AssignmentPublished $event): void
{
    $students = $event->assignment->course->students;

    Notification::send($students, new NewAssignmentNotification($event->assignment));
}
```
* Penggunaan `Notification::send($students, ...)` menerima koleksi (*collection*) mahasiswa sekaligus. Laravel secara otomatis melakukan iterasi internal dan membuat satu notifikasi untuk setiap entitas mahasiswa dalam koleksi tersebut.
* Jumlah baris yang terbentuk di tabel `notifications` **pasti cocok 1:1 dengan total mahasiswa yang terdaftar pada kursus terkait**, membuktikan bahwa tidak ada mahasiswa di luar kelas yang salah sasaran menerima notifikasi ini.

---

### 5. Baca kolom `data` pada satu notifikasi. Adakah yang tidak seharusnya tersimpan di situ?

#### Pembacaan Kolom `data` pada Tabel `notifications`:
Ketika salah satu baris notifikasi diperiksa isi kolom `data`-nya, didapatkan struktur data JSON sebagai berikut:

```json
{
  "assignment_id": 5,
  "course_name": "Pemrograman Web",
  "title": "Tugas 03: Event, Queue & Notification",
  "due_at": "2026-11-02T23:59:59+08:00",
  "url": "http://kampuslms-kelompok-02.test/assignments/5"
}
```

#### Evaluasi & Analisis Kritis Keamanan:

##### 1. Informasi yang Sudah Tepat & Aman Tersimpan:
* `assignment_id` & `url`: Memberikan referensi rute tautan bagi antarmuka agar saat mahasiswa mengeklik lonceng notifikasi, browser langsung mengarah ke halaman detail tugas.
* `course_name` & `title`: Memberikan teks ringkasan yang jelas pada kartu lonceng navbar tanpa harus melakukan *query join* berulang kali ke tabel courses atau assignments.
* `due_at`: Memberitahukan batas waktu pengumpulan tugas.

##### 2. Hal yang DILARANG / Tidak Boleh Ada di Kolom `data`:
* **Nilai Mahasiswa (*Score/Grade*) & Feedback Dosen:**  
  Jika notifikasi ini berkaitan dengan pengumuman nilai, **angka nilai atau komentar evaluasi tugas TIDAK BOLEH dicantumkan ke dalam payload JSON `data`**.
* **Kredensial atau Informasi Pribadi Sensitif (PII):**  
  Tidak boleh memuat email pribadi, nomor identitas rahasia, atau token otentikasi.
* **Path Fisik File di Harddisk Server:**  
  Tidak boleh memuat path direktori server (seperti `D:\PROWEB\...\storage\app\private\...`).

##### 3. Mengapa Data Sensitif Dilarang Berada di Kolom `data`?
1. **Tidak Dienkripsi (*Plaintext Storage*):**  
   Kolom `data` pada tabel `notifications` bertipe `text` biasa tanpa enkripsi kriptografis database. Siapa pun yang memiliki hak akses membaca database (atau jika terjadi celah SQL Injection di endpoint lain) dapat membaca seluruh riwayat nilai dan evaluasi mahasiswa secara telanjang.
2. **Kebocoran Akses (*Cross-User Leakage*):**  
   Jika terjadi kerentanan IDOR pada endpoint API pengambil notifikasi atau jika admin membaca log notifikasi sistem, informasi nilai yang bersifat privat antar mahasiswa dengan dosen dapat terekspos.
3. **Prinsip Minimalis Data Payload:**  
   Payload notifikasi hanya berfungsi sebagai **sinyal pemberitahuan (*alert signal*)** untuk memicu mahasiswa membuka halaman tugas/nilai resminya. Nilai asli tetap tersimpan secara aman di tabel `grades` dan hanya boleh ditampilkan setelah melalui gerbang otorisasi Policy (`Gate::authorize('view', $submission)`).
