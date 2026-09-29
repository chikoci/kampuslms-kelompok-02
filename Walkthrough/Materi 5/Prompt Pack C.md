# Pengujian Kerentanan IDOR (Minggu 5 - Prompt Pack C)

## Konteks & Skenario Serangan
Berikut kode route dan controller untuk modul `submissions`:

```php
// routes/web.php
Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');
```

```php
// app/Http/Controllers/SubmissionController.php
namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function show(Submission $submission)
    {
        return view('submissions.show', compact('submission'));
    }

    public function download(Submission $submission)
    {
        return Storage::download($submission->file_path, $submission->original_name);
    }
}
```

---

## Jawaban Simulasi: Mahasiswa Nakal (IDOR Attacker)

Sebagai mahasiswa biasa (misal akun saya bernama Budi, `user_id = 5`) yang sudah login ke dalam KampusLMS:

### 1. Daftar URL yang Akan Saya Coba Tebak
Tugas saya sendiri berada di URL: `http://127.0.0.1:8000/submissions/10`.
Karena saya tahu ID submission memakai angka berurutan (auto-increment integer), saya tinggal mengganti angka ID di URL browser:

* `http://127.0.0.1:8000/submissions/1` (Tugas mahasiswa pertama yang mengumpulkan)
* `http://127.0.0.1:8000/submissions/2`
* `http://127.0.0.1:8000/submissions/9` (Tugas mahasiswa sebelum saya)
* `http://127.0.0.1:8000/submissions/11` (Tugas teman sekelas saya)
* `http://127.0.0.1:8000/submissions/12`
* `http://127.0.0.1:8000/submissions/11/download` (Mengunduh langsung berkas tugas teman)

---

### 2. Apa yang Akan Saya Lihat Jika Controller Tidak Memeriksa Kepemilikan?

Karena method `show` hanya memanggil `Submission $submission` (Route Model Binding polos) lalu langsung me-render view:
1. **Di halaman `/submissions/11`:**
   * Saya bisa melihat nama lengkap dan NIM mahasiswa lain.
   * Saya bisa melihat waktu pengumpulan (apakah dia terlambat atau tepat waktu).
   * Saya bisa membaca teks catatan/jawaban tugasnya (`note`).
   * Saya bisa melihat nama file berkas yang diunggahnya.
   * Jika tugas itu sudah dinilai oleh dosen, saya bahkan bisa melihat **nilai ujian/tugas teman saya** beserta komentar evaluasi dari dosen!
2. **Di endpoint `/submissions/11/download`:**
   * Browser saya akan langsung mengunduh file tugas asli milik teman saya tersebut (.pdf atau .zip). Saya bisa menyontek isi tugasnya untuk tugas saya sendiri tanpa sepengetahuannya.

---

### 3. Solusi Perbaikan & Penjelasan Mitos Keamanan

#### Mengapa Menyembunyikan Tombol/Link di Halaman TIDAK Mengamankan Apa Pun?
Banyak pengembang pemula mengira cukup dengan menyembunyikan link di file Blade:
```blade
{{-- SALAH! Ini bukan keamanan --}}
@if($submission->user_id == auth()->id())
    <a href="/submissions/{{ $submission->id }}">Lihat Tugas</a>
@endif
```
Menyembunyikan link di frontend **sama sekali bukan keamanan**. Penyerang tidak butuh mengklik tombol di halaman; penyerang cukup mengetik langsung URL `http://127.0.0.1:8000/submissions/11` di kolom alamat browser atau menembaknya via script/curl. Pintu rumah tidak terkunci hanya karena kita menyembunyikan pegangan pintunya.

#### Perbaikan yang Benar di Sisi Server (Backend):
Gunakan `abort_unless()` di controller sebelum data atau file dikembalikan:

```php
namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function show(Submission $submission)
    {
        // Izinkan HANYA jika: pemilik submission, ATAU admin, ATAU dosen pengampu mata kuliah
        abort_unless(
            $submission->user_id === auth()->id()
                || auth()->user()->role === 'admin'
                || $submission->assignment->course->lecturer_id === auth()->id(),
            403,
            'Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.'
        );

        return view('submissions.show', compact('submission'));
    }

    public function download(Submission $submission)
    {
        abort_unless(
            $submission->user_id === auth()->id()
                || auth()->user()->role === 'admin'
                || $submission->assignment->course->lecturer_id === auth()->id(),
            403,
            'Anda tidak memiliki izin untuk mengunduh berkas ini.'
        );

        return Storage::download($submission->file_path, $submission->original_name);
    }
}
```

---

### 4. Perintah `curl` Pembuktian Lubang (Sebelum vs Sesudah)

#### Langkah 1: Login sebagai Mahasiswa A & Simpan Session
```bash
# Simpan cookie sesi login ke file cookies.txt
curl -s -c cookies.txt http://127.0.0.1:8000/login | grep -o 'name="_token" value="[^"]*"'

# Kirim data login akun mahasiswa biasa
curl -s -b cookies.txt -c cookies.txt -X POST http://127.0.0.1:8000/login \
  -d "_token=<TOKEN_DARI_BARIS_ATAS>" \
  -d "email=mahasiswa1@kampuslms.test" \
  -d "password=password"
```

#### Langkah 2: Tembak URL Submission Milik Mahasiswa Lain (ID 12)
```bash
curl -i -s -b cookies.txt http://127.0.0.1:8000/submissions/12
```

#### Hasil Pengamatan:
* **SEBELUM PERBAIKAN (Vulnerable / Rentan):**
  ```text
  HTTP/1.1 200 OK
  Content-Type: text/html; charset=UTF-8

  <!-- Tampilan halaman memuat data mahasiswa lain, link file tugas, dan nilainya -->
  ```
  Status **200 OK** membuktikan celah IDOR sukses ditembus. Data rahasia bocor!

* **SESUDAH PERBAIKAN (Protected / Aman):**
  ```text
  HTTP/1.1 403 Forbidden
  Content-Type: text/html; charset=UTF-8

  <!-- Halaman error 403: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini -->
  ```
  Status **403 Forbidden** membuktikan serangan IDOR berhasil dicegat oleh server.
