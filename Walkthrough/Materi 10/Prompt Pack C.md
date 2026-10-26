# Walkthrough Materi 10 - Prompt Pack C: Queue Debugging & Analisis Kegagalan Senyap (Silent Failure)

## Kasus Masalah
Dosen mempublikasikan tugas baru atau memberi nilai pada submission. Aplikasi berjalan normal, data tersimpan di database, tidak ada pesan error merah di layar (HTTP 200 / 302 Redirect sukses), namun **notifikasi tidak pernah sampai ke mahasiswa**.

---

## 1. Seluruh Titik Potensial Kegagalan Senyap (Silent Failure)

Dalam arsitektur `Event` -> `Listener` -> `Queue` -> `Notification` -> `Database`, ada 5 titik kritis di mana notifikasi bisa hilang tanpa jejak:

```
[Controller / Action]
        |
        v
 [Titik 1: Event Dispatch] ------ (Gagal jika: status draft / conditional guard false)
        |
        v
 [Titik 2: Listener Mapping] ---- (Gagal jika: listener tidak terdaftar di provider)
        |
        v
 [Titik 3: Queue Dispatch] ------ (Gagal jika: QUEUE_CONNECTION=sync / null / payload corrupt)
        |
        v
 [Titik 4: Worker Execution] ---- (Gagal jika: worker mati / crash / masuk failed_jobs)
        |
        v
 [Titik 5: Delivery & Target] --- (Gagal jika: penerima 0 user / via() salah / IDOR query)
```

---

## 2. Rincian Titik Kegagalan & Alat Pemeriksaannya

### Titik 1: Event Dispatch (Pemicu Event Tidak Terpanggil)
* **Penyebab:** 
  * Ada *guard conditional* di controller (misal `if ($assignment->status === 'published')`). Jika tugas disimpan dalam status `draft`, event sengaja tidak dipanggil.
  * Terjadi `return redirect()` sebelum baris `AssignmentPublished::dispatch(...)` dieksekusi.
* **Alat Pemeriksaan:**
  * **Tabel Database:** Periksa nilai kolom status pada record yang baru dibuat:
    ```sql
    SELECT id, title, status FROM assignments ORDER BY id DESC LIMIT 1;
    ```
  * **File Log:** `storage/logs/laravel.log` (jika ada logging debug manual).

---

### Titik 2: Event Listener Registration (Listener Tidak Terhubung ke Event)
* **Penyebab:**
  * Event ter-dispatch, tetapi Laravel tidak tahu bahwa ada listener yang mendengarkannya.
  * Terjadi salah penulisan nama class / namespace di `app/Providers/AppServiceProvider.php` (atau `EventServiceProvider`).
  * Listener memiliki method `shouldQueue()` yang mengembalikan `false` karena kondisi data tertentu.
* **Alat Pemeriksaan:**
  * **Perintah Artisan:**
    ```bash
    php artisan event:list
    ```
    *Pastikan `App\Events\AssignmentPublished` memiliki listener `App\Listeners\SendAssignmentNotification` di bawahnya.*

---

### Titik 3: Queue Dispatch & Connection Config (Job Tidak Masuk ke Antrean Database)
* **Penyebab:**
  * Konfigurasi antrean di `.env` masih `QUEUE_CONNECTION=sync` (dieksekusi langsung saat request, jika gagal saat itu juga bisa tertelan).
  * Konfigurasi diatur ke `QUEUE_CONNECTION=null` (semua job antrean langsung dibuang ke void).
  * Antrean dikirim ke channel antrean khusus (misalnya `onQueue('emails')`), namun worker hanya mendengarkan channel `default`.
* **Alat Pemeriksaan:**
  * **File Konfigurasi / Artisan:**
    ```bash
    php artisan config:show queue
    ```
  * **Tabel Database:** Periksa apakah baris job tersimpan:
    ```sql
    SELECT id, queue, attempts, reserved_at, available_at, created_at FROM jobs;
    ```

---

### Titik 4: Queue Worker Execution & Failed Jobs (Worker Mati atau Terjadi Exception)
* **Penyebab:**
  * **Worker mati:** Antrean masuk ke tabel `jobs`, tetapi tidak ada proses `php artisan queue:work` yang sedang berjalan di server untuk memprosesnya.
  * **Job Crash / Gagal:** Listener mengalami fatal error (misal null pointer exception), melampaui batas `$tries = 3`, lalu dipindahkan dari tabel `jobs` ke tabel `failed_jobs`.
  * **Worker memori limit:** Worker mati diam-diam karena crash tanpa supervisor.
* **Alat Pemeriksaan:**
  * **Perintah Artisan Cek Gagal:**
    ```bash
    php artisan queue:failed
    ```
  * **Tabel Database:**
    ```sql
    SELECT id, connection, queue, failed_at, exception FROM failed_jobs ORDER BY id DESC LIMIT 3;
    ```
  * **File Log:** Periksa jejak stack trace error di:
    ```
    storage/logs/laravel.log
    ```

---

### Titik 5: Recipient Query & Delivery (Penerima Kosong / Saluran Salah)
* **Penyebab:**
  * Job dieksekusi dengan sukses oleh worker (tanpa error), tetapi kueri penerima menghasilkan **0 baris** (misal: belum ada mahasiswa yang di-enroll ke mata kuliah tersebut, atau filter query mencari role `'student'` padahal di database tersimpan `'mahasiswa'`).
  * `Notification::send([], new NewAssignmentNotification(...))` selesai dengan kode sukses, tetapi tidak ada record yang dimasukkan ke database.
  * Method `via($notifiable)` tidak mengembalikan `['database']`.
* **Alat Pemeriksaan:**
  * **Tabel Database Penerima (Enrollment):**
    ```sql
    SELECT COUNT(*) FROM course_user WHERE course_id = <ID_COURSE>;
    ```
  * **Tabel Database Notifikasi:**
    ```sql
    SELECT id, type, notifiable_type, notifiable_id, data, read_at, created_at FROM notifications ORDER BY created_at DESC LIMIT 5;
    ```

---

## 3. Pertanyaan Diagnosa Investigasi

Untuk menemukan sumber masalah secara tepat tanpa menebak-nebak, lakukan 4 pemeriksaan berurutan berikut:
1. **Pemeriksaan Event Mapping:** Apa output dari perintah `php artisan event:list`?
2. **Pemeriksaan Tabel Jobs:** Berapa jumlah baris di tabel `jobs` saat ini? (`SELECT COUNT(*) FROM jobs;`)
3. **Pemeriksaan Tabel Failed Jobs:** Apa hasil dari perintah `php artisan queue:failed`?
4. **Pemeriksaan Tabel Notifications:** Apakah ada baris baru di tabel `notifications`? (`SELECT COUNT(*) FROM notifications;`)
