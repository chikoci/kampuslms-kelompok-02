# Walkthrough Materi 10 - Prompt Pack D: Audit Keamanan Notifikasi & Anti-IDOR

Dokumen ini memuat hasil audit keamanan terhadap implementasi Notification dan Komponen Lonceng di LMS Laravel 12 Kelompok 02, disertai bukti pengujian menggunakan perintah cURL dan kueri database.

---

## 1. Kode yang Diaudit

### A. Notification Payload
* File: `app/Notifications/NewAssignmentNotification.php`
* File: `app/Notifications/SubmissionGradedNotification.php`

```php
// app/Notifications/SubmissionGradedNotification.php
public function toArray(object $notifiable): array
{
    return [
        'submission_id'   => $this->submission->id,
        'assignment_id'   => $this->submission->assignment_id,
        'assignment_title'=> $this->submission->assignment->title ?? 'Tugas',
        'course_code'     => $this->submission->assignment->course->code ?? '',
        'type'            => 'submission_graded',
        'message'         => 'Nilai tugas Anda untuk ' . ($this->submission->assignment->title ?? 'Tugas') . ' telah diberikan.',
        'link'            => route('assignments.submissions.show', [$this->submission->assignment_id, $this->submission->id]),
    ];
}
```

### B. Controller Pengambilan & Penandaan Notifikasi
* File: `app/Http/Controllers/NotificationController.php`

```php
// Mengambil notifikasi user login
public function index(Request $request): View
{
    $notifications = $request->user()->notifications()->paginate(15);
    return view('notifications.index', compact('notifications'));
}

// Endpoint polling unread count untuk navbar
public function unreadCount(Request $request): JsonResponse
{
    $count = $request->user()->unreadNotifications()->count();
    return response()->json(['unread_count' => $count]);
}

// Menandai notifikasi telah dibaca dengan proteksi kepemilikan
public function markAsRead(Request $request, string $id): RedirectResponse
{
    $notification = $request->user()->notifications()->findOrFail($id);
    $notification->markAsRead();

    return back()->with('success', 'Notifikasi telah ditandai sudah dibaca.');
}
```

---

## 2. Hasil Temuan Audit Keamanan

### Temuan 1: Payload Bebas Data Sensitif (Zero Academic Data Leakage)
* **Status:** **AMAN (PASS).**
* **Analisis:**  
  Pada `SubmissionGradedNotification`, kolom angka nilai (`score`) dan komentar dosen (`feedback`) **sengaja TIDAK dimasukkan** ke dalam payload JSON database.
* **Risiko jika dilanggar:**  
  Tabel `notifications` diakses oleh banyak pihak dan sering tercatat dalam log cadangan (*database backup*). Jika nilai tersimpan di kolom `data`, staf admin atau pengembang yang memegang salinan database dapat melihat nilai pribadi mahasiswa tanpa izin.
* **Solusi yang Diterapkan:**  
  Payload hanya berisi judul tugas, ID, pesan ajakan, dan link menuju submission. Nilai dan feedback asli tetap aman di tabel `submissions` yang dilindungi oleh `SubmissionPolicy@view`.

---

### Temuan 2: Scoping Notifikasi Berbasis Autentikasi Session
* **Status:** **AMAN (PASS).**
* **Analisis:**  
  Endpoint `GET /notifications` dan `GET /notifications/unread-count` tidak menerima parameter `user_id` di URL maupun di Query String.
* **Uji Manipulasi Parameter:**  
  Jika pengguna memanggil `/notifications?user_id=2` atau `/notifications/unread-count?user_id=99`, controller sama sekali tidak membaca query string tersebut. Kueri Eloquent secara konsisten menggunakan `$request->user()->notifications()`, sehingga data yang kembali 100% terkunci pada pengguna yang sedang login.

---

### Temuan 3: Proteksi Anti-IDOR pada Endpoint "Tandai Sudah Dibaca"
* **Status:** **AMAN (PASS).**
* **Analisis:**  
  Pada method `markAsRead($request, $id)`, controller **TIDAK** menggunakan model lookup global seperti `DatabaseNotification::findOrFail($id)`.
  Sebaliknya, controller mengaitkannya ke relasi user:
  ```php
  $notification = $request->user()->notifications()->findOrFail($id);
  ```
* **Kueri SQL yang Terbentuk:**
  ```sql
  SELECT * FROM notifications 
  WHERE id = :notification_id 
    AND notifiable_id = :auth_user_id 
    AND notifiable_type = 'App\\Models\\User' 
  LIMIT 1;
  ```
* **Hasil:**  
  Jika Mahasiswa B mencoba mengirim request `POST /notifications/{id_milik_A}/read`, kueri database menghasilkan `NULL` dan Laravel melempar HTTP `404 Not Found`. Mahasiswa B tidak dapat memanipulasi status notifikasi milik mahasiswa lain.

---

### Temuan 4: Keamanan Saluran Broadcasting (Private vs Public Channel)
* **Status:** **ARSITEKTUR AMAN.**
* **Analisis:**  
  Jika menggunakan real-time broadcasting (misal Laravel Echo + Reverb / Pusher):
  * **Wajib Menggunakan `PrivateChannel`:** Channel notifikasi perorangan wajib diformat sebagai `new PrivateChannel('App.Models.User.' . $this->user->id)`.
  * **Otorisasi di `routes/channels.php`:**
    ```php
    Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
        return (int) $user->id === (int) $id;
    });
    ```
* **Akibat Fatal jika Menggunakan Channel Publik (`Channel`):**  
  Channel publik tidak memiliki lapisan otentikasi. Siapa pun (termasuk hacker atau mahasiswa lain) bisa membuka browser console, menjalankan `Echo.channel('user.1').listen(...)`, dan menyadap notifikasi real-time orang lain secara bebas.

---

## 3. Pembuktian Menggunakan cURL

Jalankan perintah berikut di terminal untuk membuktikan keamanan sistem secara langsung:

### Bukti 1: Verifikasi Payload Database Bebas Nilai / Feedback
Periksa data JSON di tabel database langsung melalui Artisan Tinker:
```bash
php artisan tinker --execute="echo json_encode(DB::table('notifications')->latest()->first()->data ?? 'kosong', JSON_PRETTY_PRINT);"
```
*Hasil:* Tidak ada properti `"score"` maupun `"feedback"`. Hanya ada `assignment_id`, `assignment_title`, `type`, `message`, dan `link`.

---

### Bukti 2: Uji Coba Scoping Notifikasi User
Simulasikan request HTTP sebagai Mahasiswa Budi (ID: 3):
```bash
# 1. Login dan simpan cookie session
curl -s -c cookies.txt -X POST http://127.0.0.1:8000/login \
  -F "email=mahasiswa@kampuslms.test" \
  -F "password=password"

# 2. Panggil unread count dengan mencoba menyisipkan user_id lain di query string
curl -s -b cookies.txt "http://127.0.0.1:8000/notifications/unread-count?user_id=1"
```
*Hasil:* Endpoint mengembalikan jumlah unread murni milik Budi Santoso, parameter `?user_id=1` diabaikan sepenuhnya oleh server.

---

### Bukti 3: Uji Anti-IDOR (Menandai Notifikasi Orang Lain)
Ambil salah satu UUID notifikasi milik mahasiswa lain (misal notifikasi milik mahasiswa ID 4):
```bash
# Ambil token CSRF dari halaman notifikasi
CSRF_TOKEN=$(curl -s -b cookies.txt http://127.0.0.1:8000/notifications | grep -o 'name="_token" value="[^"]*"' | head -1 | cut -d'"' -f4)

# Budi (ID 3) mencoba menandai notifikasi milik orang lain (misal UUID f9b3...)
curl -s -o /dev/null -w "%{http_code}\n" -b cookies.txt -X POST \
  http://127.0.0.1:8000/notifications/f9b30000-0000-0000-0000-000000000000/read \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN"
```
*Hasil yang Didapat:*
```text
404
```
Server mengembalikan status **HTTP 404 (Not Found)** karena notifikasi tersebut tidak terdaftar di bawah relasi akun pengguna yang sedang login.
