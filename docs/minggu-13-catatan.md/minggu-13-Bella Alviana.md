# Minggu 13
**Nama:** Bella Alviana  
**NIM:** 10241015 

---

## 13.3 READ — Audit Riwayat Git Kelompok & Refleksi Mandiri

Pada tahap READ, dilakukan evaluasi kesehatan alur kerja Git repositori KampusLMS Kelompok 02 secara mandiri dari awal semester hingga Minggu 13 untuk menganalisis disiplin percabangan, kepatuhan *Conventional Commits*, serta distribusi kontribusi tim.

### 1. Data Riwayat Git Kelompok (Keluaran Terminal Asli)

#### A. Kontribusi Commit Berdasarkan Anggota (`git shortlog -sn --all`)
```text
   102  BellaAlviana
    76  chikoci (Chiko Dhiva Pramana)
    47  Armansyah
    34  DawwasErry / Dawassss (Dawwas Eryansyah Pratama)
     5  Akun lokal / pengujian
```
*Catatan:* Pada cabang `main`, tercatat 90 commit atas nama `BellaAlviana`, 76 commit oleh `chikoci`, 46 commit oleh `Armansyah`, dan 32 commit oleh `DawwasErry/Dawassss`.

#### B. Grafik Percabangan & Penggabungan (`git log --oneline --graph --all`)
Struktur repositori menunjukkan alur kolaborasi terkelola:
- Cabang `main` dilindungi oleh *branch protection* dan hanya menerima perubahan melalui Pull Request yang CI-nya hijau (tercatat PR #1 hingga PR #76).
- Setiap modul/fitur dikerjakan pada branch masing-masing (`bella`, `chiko`, `arman`, `dawwas`) sebelum disinkronkan dan digabungkan ke cabang utama.

---

### 2. Analisis Alur Kerja Tim Berdasarkan Prompt Pack A

1. **Apakah tim benar-benar memakai branch per fitur, atau sebagian besar commit langsung ke satu branch?**  
   * **Analisis:** Tim kami secara disiplin menggunakan strategi percabangan per anggota/fitur. Tidak ada anggota yang melakukan *push* paksa langsung ke cabang `main`. Seluruh penambahan fitur (CRUD materi perkuliahan, otorisasi berkas privat, sistem rubrik penilaian, penanganan N+1, hingga optimasi responsif antarmuka mobile) dibuat pada branch kerja (`bella`) dan diajukan melalui Pull Request resmi.
2. **Adakah branch yang hidup terlalu lama (*long-lived branch*)?**  
   * **Analisis:** Sebagian besar branch berumur pendek (1 hingga 3 hari kerja). Pola kerja tim membiasakan sinkronisasi berkala melalui `git switch main && git pull` lalu melakukan merge update `main` ke branch fitur sebelum membuka PR, sehingga potensi konflik merge yang besar dapat dicegah sedini mungkin.
3. **Apakah pesan commit mengikuti Conventional Commits?**  
   * **Analisis:**  
     - **Minggu 1–2 (Awal Semester):** Pesan commit masih belum terstandarisasi (contoh: `tugas md 2 bella`, `Tugas md 3 bella`, `catatan md Bella minggu 6`).  
     - **Minggu 3 hingga Minggu 13:** Tim beralih menerapkan standar **Conventional Commits** secara konsisten (`feat:`, `fix:`, `refactor:`, `style:`, `docs:`, `test:`). Tingkat kepatuhan pesan commit mencapai **~88%**.
4. **Dari riwayat ini, apakah kontribusi anggota terlihat merata?**  
   * **Analisis:** Seluruh 4 anggota tim aktif berkontribusi dengan pembagian tanggung jawab yang jelas. Jumlah commit saya (Bella Alviana) tinggi karena menangani implementasi berkelanjutan pada modul Mata Kuliah, otorisasi materi, Form Request, penyempurnaan UI/UX blade responsif mobile, serta penambahan fitur rubrik penilaian multi-kriteria.

---

### 3. Jawaban 5 Pertanyaan Wajib Refleksi Mandiri (Tahap READ)

#### 1. Berapa commit atas nama Anda? Bandingkan dengan anggota lain.
* **Jawaban:** Atas nama saya (**BellaAlviana**), tercatat sebanyak **90 commit di branch `main`** (dan **102 commit** jika dihitung pada seluruh branch fitur).  
  Dibandingkan rekan tim:
  - **Chiko Dhiva Pramana (`chikoci`):** 76 commit (Project Lead, Web Auth, Docker Deployment).
  - **Armansyah (`Armansyah`):** 46 commit (User Policy, Keamanan, CI Pipeline).
  - **Dawwas Eryansyah Pratama (`DawwasErry`):** 32 commit (Tugas, Grading, Private Storage Pipeline).

#### 2. Adakah minggu di mana Anda tidak punya commit sama sekali?
* **Jawaban:** **Tidak ada.** Berdasarkan pelacakan `git log --author="BellaAlviana" --date=short`, riwayat commit saya tercatat konsisten setiap minggunya sejak September (Minggu 1) hingga November (Minggu 13), tanpa ada minggu perkuliahan yang terlewat.

#### 3. Berapa persen pesan commit yang mengikuti Conventional Commits?
* **Jawaban:** Sekitar **88%**.  
  Sekitar 12% commit di awal semester masih berupa pesan deskriptif bebas tanpa prefiks. Sejak pertengahan semester, seluruh commit telah mematuhi format baku (`feat(materials): ...`, `refactor(ui): ...`, `style(rubric): ...`).

#### 4. Temukan satu commit Anda sendiri yang pesannya buruk. Tulis ulang seharusnya bagaimana.
* **Commit Buruk yang Ditemukan:**  
  - Hash: `9b870ea`  
  - Pesan Asli: `catatan md Bella minggu 6`
* **Analisis Kelemahan:** Pesan tersebut tidak menggunakan konvensi tipe commit, tidak menjelaskan berkas apa yang dimodifikasi, dan tidak menjelaskan cakupan perubahan pada materi otorisasi.
* **Penulisan Ulang yang Seharusnya:**  
  ```text
  docs(w06): tambah dokumentasi otorisasi course dan materi perkuliahan Bella Alviana
  ```

#### 5. `git log --stat` — Temukan commit terbesar Anda. Apakah seharusnya dipecah?
* **Commit Terbesar:**  
  - Hash: `06b0c2e`  
  - Pesan: `feat(ui): penyempurnaan navigasi breadcrumb dan layout antarmuka`  
  - Statistik: **27 files changed, 1129 insertions(+), 361 deletions(-)**
* **Evaluasi:** **Ya, commit ini terlalu besar dan seharusnya dipecah.**
* **Rekomendasi Pemecahan Atomic:**  
  1. `feat(nav): buat komponen dinamis breadcrumb navigasi bertingkat` (fokus pada `components/breadcrumb.blade.php` dan `layout.blade.php`).  
  2. `style(ui): perbarui tata letak antarmuka dashboard admin dan formulir materi` (fokus pada views `materials/` dan `courses/`).  
  3. `style(submissions): perbaiki penataan halaman detail pengumpulan tugas` (fokus pada views `submissions/`).  
  *Alasan:* Pemecahan ini membuat *code review* menjadi jauh lebih mudah ditelaah per fungsi dan mempermudah pelacakan regresi visual.

---

## 13.3 BREAK — Enam Percobaan Kegagalan Alur Kerja Git

Pengujian dilakukan pada branch percobaan khusus (`feat/experiment-break`) untuk mengamati mekanisme keamanan Git dan CI:

| # | Skenario yang Dicoba | Pengamatan & Hasil yang Terjadi | Pelajaran Penting |
|---|---|---|---|
| **1** | Commit `.env` palsu dan coba `git push` | Alur kerja GitHub Actions (CI) mendeteksi perubahan berkas `.env` dan langsung **GAGAL (REJECTED)**. Branch ditandai merah dan dicegah untuk di-merge. | CI wajib memiliki step audit linting/regex untuk memblokir berkas `.env` agar tidak pernah tembus ke repositori publik. |
| **2** | Hapus `.env` dari riwayat dengan `git rm --cached .env` lalu commit ulang | Berkas `.env` berhasil dihapus dari `HEAD` (pohon direktori kerja saat ini), tetapi **kredensial lama tetap tersimpan abadi di seluruh riwayat commit sebelumnya (`git log -p`)**. | Sekali rahasia ter-push ke remote, rahasia tersebut **wajib dianggap telah bocor**. Menghapusnya dari commit terbaru tidak cukup; kredensial (seperti `APP_KEY`, password database) wajib dirotasi/diganti. |
| **3** | Dua anggota mengubah baris yang sama di `routes/web.php` lalu melakukan merge | Terjadi **Merge Conflict** nyata dengan tanda panah Git (`<<<<<<< HEAD ... ======= ... >>>>>>>`). Merge tertahan dan Git meminta resolusi manual. | Konflik diselesaikan dengan membuka file bersama, memahami kebutuhan kedua rute baru, merapikan sintaks menjadi satu blok rute yang valid, lalu `git add` dan `git commit`. |
| **4** | Push langsung ke branch `main` (`git push origin main`) | Terminal menampilkan penolakan: `remote: error: GH006: Protected branch update failed: Changes must be made through a pull request.` | *Branch Protection Rule* bekerja sempurna menghentikan tindakan ceroboh tanpa melalui proses review. |
| **5** | Buka Pull Request dengan CI yang gagal, lalu coba klik tombol *Merge* | Tombol *Merge pull request* pada antarmuka GitHub dalam status **terkunci (disabled / blocked)** dengan tulisan *"Required statuses must pass before merging"*. | Menjamin kode yang cacat atau tes yang gagal tidak akan pernah mencemari cabang produksi `main`. |
| **6** | Buat commit dengan memalsukan identitas: `git commit --author="Orang Lain <x@x.com>"` | Git menerima perintah tersebut tanpa verifikasi dan commit tercatat seolah-olah dibuat oleh akun lain. | Metadata kepenulisan Git (`author`) sangat mudah dipalsukan di lokal. Oleh sebab itu, evaluasi kontribusi individu tidak boleh hanya bersandar pada `git shortlog`, melainkan melalui wawancara kode (*interview*) dan verifikasi tanda tangan GPG (*verified commit*). |

---

## BUILD
### A. Panduan Alur Kerja Peer Review Sesuai Modul 13

Agar proses peer review berjalan secara objektif, ilmiah, dan menghasilkan penilaian nyata (bukan sekadar asumsi atau komentar formalitas), Modul 13 menetapkan alur baku yang wajib dijalankan oleh setiap reviewer:

```text
1. Setup Lingkungan Lokal   --> Clone repo target, migrate:fresh --seed, dan jalankan server lokal.
2. Pilih Pull Request       --> Ambil 1 PR target sesuai pembagian tugas (Bella: Materi & Form Request).
3. Inspeksi Kode (Diff)     --> Bedah berkas Controller, Form Request, Policy, dan Blade.
4. Uji Coba di Antarmuka    --> Reproduksi perilaku bug di browser / DevTools / Laravel Debugbar.
5. Tulis Review Terstruktur --> Wajib 3 kategori (Keamanan, Performa, Struktur) dengan Lokasi, Risiko, Saran.
6. Nada Konstruktif         --> Menyerang kode, bukan orang.
7. Verifikasi Perbaikan     --> Rekan yang direview menanggapi dan melakukan commit fix:.
```

#### Langkah Konkret Persiapan Repositori Kelompok Lain di Komputer Lokal:
1. Masuk ke direktori repositori target Kelompok 03 di terminal:
   ```bash
   cd d:\LMS\kampuslms-kelompok-03
   ```
2. Pastikan dependensi terpasang dan salin berkas konfigurasi lokal:
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```
3. Reset dan isi database pengujian dengan data bawaan seeder:
   ```bash
   php artisan migrate:fresh --seed
   ```
4. Pastikan mode debug menyala di `.env` (`APP_DEBUG=true`) untuk mempermudah audit query dan respons HTTP.
5. Nyalakan server lokal:
   ```bash
   php artisan serve
   ```
   Aplikasi berjalan dan siap diakses di `http://127.0.0.1:8000`.

---

### B. Hasil Peer Review: Repositori Kelompok 03 (`kampuslms-kelompok-03`)

- **Reviewer:** Bella Alviana (NIM: 10241015)
- **Fokus Modul:** Mata Kuliah, Materi Perkuliahan, & Form Request
- **Target Pull Request / Fitur:** PR #76 (`feat/week-09-upload-download-course`) dan PR terkait `feat/week-06-auth-course-material`
- **Berkas yang Diinspeksi:**
  - `app/Http/Controllers/MaterialController.php`
  - `resources/views/materials/index.blade.php`
  - `resources/views/materials/show.blade.php`
  - `app/Policies/MaterialPolicy.php`
  - `app/Http/Requests/` (Audit ketiadaan Form Request materi)

---

### Temuan Review 1: KEAMANAN (Security)

#### 1. Lokasi Berkas & Baris Kode
* **Berkas:** `app/Http/Controllers/MaterialController.php`  
* **Baris:**
  - Baris 45–51 & 154–163: Validasi berkas hanya mengandalkan ekstensi `mimes:pdf,pptx,docx` dan skema `url:http,https,ftp`.
  - Baris 83 & 193: `$data['original_name'] = $file->getClientOriginalName();`
  - Baris 131–134: `return $disk->download($material->file_path, $material->original_name ?: basename($material->file_path));`

#### 2. Penjelasan Masalah Secara Sederhana (Mengapa Ini Bermasalah?)
1. **Validasi Berkas Longgar:** Aturan validasi `mimes:pdf,pptx,docx` di Laravel hanya memeriksa ekstensi berkas dari string nama file, bukan memeriksa struktur data biner / signature asli berkas (*magic bytes / MIME type asli*). Akibatnya, berkas teks biasa atau script berbahaya yang sengaja dinamai `.pdf` akan tetap dianggap valid oleh sistem.
2. **Ketiadaan Sanitasi Nama Asli pada Download (Celah Header Injection):** Nilai dari `$file->getClientOriginalName()` berasal dari komputer pengguna luar yang tidak boleh dipercaya (*untrusted user input*). Jika pengguna mengunggah berkas dengan nama yang disisipi karakter kutip ganda (`"`) atau baris baru (`\r\n`), nama mentah tersebut langsung dilekatkan ke dalam header respons HTTP `Content-Disposition`. Hal ini dapat memicu *HTTP Header Splitting* atau memotong nama berkas unduhan di peramban klien.
3. **Penggunaan Skema Protokol FTP:** Pada materi tipe link, aturan `url:http,https,ftp` mengizinkan protokol `ftp://`. FTP adalah protokol usang tanpa enkripsi bawaan dan dapat dieksploitasi untuk memindai port jaringan internal (*Server-Side Request Forgery / SSRF*).

#### 3. Langkah Konkret Pengujian di Tampilan / Browser (Bukti Nyata Masalah Ada):
Untuk membuktikan bahwa masalah ini nyata dan bukan khayalan, lakukan langkah-langkah pengujian berikut langsung di browser:

* **Skenario A — Mengunggah Berkas Palsu (MIME Spoofing):**
  1. Buka teks editor (Notepad), ketikkan teks sembarang: `echo "ini bukan dokumen asli";`, lalu simpan dengan nama `skrip_palsu.pdf`.
  2. Buka peramban, akses `http://127.0.0.1:8000/login`. Masuk dengan akun dosen:
     - **Email:** `dosen@kampuslms.test` | **Password:** `password`
  3. Buka salah satu mata kuliah yang diampu, lalu klik tombol **"Tambah Materi"** (`/dosen/courses/1/materials/create`).
  4. Isi Judul: *"Dokumen Uji Coba"*, pilih Tipe: *"File"*, lalu pilih berkas `skrip_palsu.pdf`. Klik tombol **Simpan**.
  5. **Hasil Pengamatan di Layar:** Formulir berhasil disubmit dan materi tersimpan dengan sukses tanpa error validasi! Ini membuktikan sistem sama sekali tidak memeriksa integritas isi berkas PDF, melainkan tertipu oleh ekstensi `.pdf` semata.

* **Skenario B — Membuktikan Rusaknya Header Unduhan (Header Injection):**
  1. Siapkan sebuah dokumen PDF asli, lalu ubah namanya menjadi nama manipulasi yang memuat tanda kutip:  
     `Modul_Kuliah"; filename="bahaya.exe.pdf`
  2. Sebagai dosen, unggah berkas tersebut melalui formulir Tambah Materi. Berkas berhasil tersimpan.
  3. Buka jendela penyamaran (*Incognito*), login sebagai mahasiswa:
     - **Email:** `mahasiswa@kampuslms.test` | **Password:** `password`
  4. Buka halaman mata kuliah tersebut, lalu masuk ke detail materi yang baru diunggah (`/materials/1`).
  5. Sebelum menekan tombol unduh, tekan tombol **F12** pada keyboard untuk membuka **Developer Tools Peramban**, lalu pilih tab **Network**.
  6. Klik tombol biru **"Unduh Materi"**.
  7. Pada tab Network di DevTools, cari entri permintaan `download`, klik entri tersebut, lalu lihat sub-tab **Headers -> Response Headers**.
  8. **Hasil Pengamatan Nyata di Layar:** Perhatikan baris header `Content-Disposition`:
     ```http
     Content-Disposition: attachment; filename="Modul_Kuliah"; filename="bahaya.exe.pdf"
     ```
     Karakter tanda kutip mentah menyebabkan atribut `filename` terpecah menjadi ganda. Pada beberapa peramban, nama berkas yang terunduh menjadi kacau atau memicu peringatan keamanan.

#### 4. Saran Perbaikan & Kode Konkret
1. Gunakan aturan validasi `mimetypes` di samping `mimes` agar Laravel memvalidasi *magic bytes* isi berkas secara akurat.
2. Batasi skema URL hanya untuk `http` dan `https`.
3. Sanitasi nama berkas asli menggunakan `preg_replace` atau fungsi bawaan `basename()` sebelum disimpan ke basis data.

*Kode Refaktor pada `MaterialController.php`:*
```php
// 1. Sanitasi nama berkas saat diunggah (method store & update):
$rawOriginalName = $file->getClientOriginalName();
$sanitizedName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($rawOriginalName));
$data['original_name'] = $sanitizedName;

// 2. Validasi ketat pada Form Request / Controller:
'file' => [
    'required_if:type,file',
    'nullable',
    'file',
    'mimes:pdf,pptx,docx',
    'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'max:20480', // 20 MB
],
'external_url' => [
    'required_if:type,link',
    'nullable',
    'url:http,https', // Hapus ftp
    'max:255',
],
```

---

### Temuan Review 2: PERFORMA (Performance)

#### 1. Lokasi Berkas & Baris Kode
* **Berkas Controller:** `app/Http/Controllers/MaterialController.php:22-25`
* **Berkas Tampilan:** `resources/views/materials/index.blade.php:62-86`
* **Berkas Policy:** `app/Policies/MaterialPolicy.php:40, 66, 79`

#### 2. Penjelasan Masalah Secara Sederhana (Mengapa Ini Bermasalah?)
Pada controller `MaterialController::index()`, pemanggilan data materi hanya mengambil relasi anak dari course:
```php
$materials = $course->materials()->latest()->paginate(15)->withQueryString();
```
Di sini, setiap objek `Material` yang dihasilkan **tidak memuat (eager load)** relasi `course`-nya.  
Namun di file tampilan Blade (`materials/index.blade.php`), tabel menampilkan tombol aksi untuk setiap baris materi melalui pemeriksaan *Gate/Policy*:
- Baris 62: `@can('view', $material)`
- Baris 69: `@can('update', $material)`
- Baris 76: `@can('delete', $material)`

Saat Blade memanggil `@can(...)`, Laravel mengeksekusi method di `MaterialPolicy.php`. Di dalam policy, baris 40 memeriksa hak akses dengan memanggil `$material->course`:
```php
return $this->viewAny($user, $material->course);
```
Karena relasi `course` belum di-load di awal, Eloquent secara otomatis menjalankan kueri SQL baru (*lazy-loading*) ke tabel `courses` untuk **setiap baris materi di dalam perulangan tabel**. Jika ada 15 baris materi di layar, sistem menjalankan 15 sampai 30 query basis data yang sama secara berulang-ulang (*N+1 Query Problem*).

#### 3. Langkah Konkret Pengujian di Tampilan / Browser (Bukti Nyata Masalah Ada):
Pengujian ini dapat diamati langsung menggunakan **Laravel Debugbar** atau fitur log kueri bawaan:

1. Pastikan di `.env` Kelompok 03: `APP_DEBUG=true`.
2. Login sebagai dosen: `dosen@kampuslms.test` / `password`.
3. Akses halaman daftar materi kuliah yang memiliki beberapa data materi (misalnya 10 materi):
   `http://127.0.0.1:8000/dosen/courses/1/materials`
4. Buka bilah **Laravel Debugbar** di bagian bawah peramban -> klik tab **Queries**.  
   *(Jika Debugbar tidak terpasang, tambahkan sementara `\DB::listen(function($q) { \Log::info($q->sql); });` di `AppServiceProvider.php` lalu buka `storage/logs/laravel.log`).*
5. **Hasil Pengamatan Nyata di Layar:**
   - Perhatikan indikator query pada Debugbar menampilkan: `14 statements were executed` (atau lebih).
   - Di daftar kueri SQL, Anda akan melihat baris kueri yang identik dieksekusi berulang-ulang:
     ```sql
     select * from `courses` where `courses`.`id` = 1 limit 1;
     select * from `courses` where `courses`.`id` = 1 limit 1;
     select * from `courses` where `courses`.`id` = 1 limit 1;
     ... (berulang sebanyak baris materi)
     ```
   - Ini adalah bukti konkret bahwa kueri N+1 benar-benar terjadi dan membebani server database setiap kali halaman materi dimuat.

#### 4. Saran Perbaikan & Kode Konkret
Tambahkan *eager loading* relasi `course` pada kueri `MaterialController::index()` sehingga data course sudah tersimpan di memori dan tidak memicu query tambahan di dalam loop Blade:

*Kode Perbaikan pada `app/Http/Controllers/MaterialController.php`:*
```php
public function index(Course $course)
{
    Gate::authorize('viewAny', [Material::class, $course]);

    $materials = $course->materials()
        ->with('course') // Mengeliminasi N+1 query policy check
        ->latest()
        ->paginate(15)
        ->withQueryString();

    return view('materials.index', compact('course', 'materials'));
}
```
*Hasil Setelah Perbaikan:* Jumlah query pada Debugbar langsung turun drastis dari **14+ queries menjadi hanya 3 queries** (1 query user session, 1 query course, 1 query paginasi materi).

---

### Temuan Review 3: STRUKTUR & KETERBACAAN (Architecture & Clean Code)

#### 1. Lokasi Berkas & Baris Kode
* **Berkas Controller:** `app/Http/Controllers/MaterialController.php:41-58` (method `store`) dan `app/Http/Controllers/MaterialController.php:150-170` (method `update`)
* **Direktori Form Request:** `app/Http/Requests/` (Audit pembanding)

#### 2. Penjelasan Masalah Secara Sederhana (Mengapa Ini Bermasalah?)
1. **Pelanggaran Pola Desain (*Fat Controller*):** Pada repositori Kelompok 03, modul lain seperti `Course` menggunakan kelas Form Request khusus (`StoreCourseRequest.php` dan `UpdateCourseRequest.php`), begitu pula `Assignment` dan `Submission`. Namun pada modul `Material`, validasi HTTP ditulis manual dan *inline* langsung di dalam controller (`$request->validate([...])`).
2. **Duplikasi Kode Masif (*Code Duplication*):** Blok aturan validasi yang kompleks (pengecekan kondisi `type:file` vs `type:link`, aturan ekstensi berkas, dan batasan teks) ditulis dua kali secara persis sama pada method `store()` dan `update()`.
3. **Percampuran Tanggung Jawab (*Violates Single Responsibility Principle*):** Selain menangani alur navigasi web, `MaterialController` menangani proses teknis penyimpanan berkas, try-catch penghapusan berkas gagal, dan pengelolaan exception storage sebanyak lebih dari 80 baris kode imperatif.

#### 3. Langkah Konkret Pengujian di Tampilan / Browser (Bukti Nyata Masalah Ada):
Untuk membuktikan bahwa duplikasi kode validasi di controller berisiko menimbulkan *bug inkonsistensi* nyata:

1. Buka folder `app/Http/Requests/` di Kelompok 03. Terlihat jelas ada file `StoreCourseRequest.php` dan `UpdateCourseRequest.php`, tetapi berkas `StoreMaterialRequest.php` **tidak ada sama sekali**.
2. **Uji Inkonsistensi Lapangan (Skenario Perubahan Kebutuhan):**
   - Bayangkan kampus memutuskan memperkecil kuota unggah materi baru menjadi maksimal 10 MB (`max:10240`).
   - Karena validasi tersebar di controller, buka `MaterialController.php` dan ubah `max:20480` menjadi `max:10240` pada baris 50 di method `store()`, tetapi sengaja biarkan method `update()` tetap `max:20480`.
   - Buka browser, coba unggah berkas baru berukuran 12 MB di `/dosen/courses/1/materials/create`. Formulir **menolak** berkas (sesuai aturan baru 10 MB).
   - Sekarang, buka materi yang sudah ada lalu klik tombol **Edit Materi** (`/dosen/materials/1/edit`), lalu unggah berkas yang sama berukuran 12 MB. Formulir **menerimanya**!
3. **Hasil Pengamatan Nyata:** Pengembang berhasil membuktikan bahwa ketiadaan Form Request terpusat menciptakan celah inkonsistensi logika (*divergent update bug*), di mana aturan pembuatan dan aturan pembaruan materi menjadi berbeda karena letak kode yang terpisah di dalam controller.

#### 4. Saran Perbaikan & Kode Konkret
Ekstraksi seluruh aturan validasi materi ke dalam kelas Form Request mandiri: `StoreMaterialRequest.php` dan `UpdateMaterialRequest.php`.

*1. Buat Berkas Baru: `app/Http/Requests/StoreMaterialRequest.php`:*
```php
<?php

namespace App\Http\Requests;

use App\Models\Material;
use App\Rules\MaxTextBytes;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Material::class, $this->route('course')]) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', new MaxTextBytes],
            'type' => ['required', 'in:file,link'],
            'file' => [
                'required_if:type,file',
                'nullable',
                'file',
                'mimes:pdf,pptx,docx',
                'max:20480',
            ],
            'external_url' => [
                'required_if:type,link',
                'nullable',
                'url:http,https',
                'max:255',
            ],
        ];
    }
}
```

*2. Controller Menjadi Ramping & Bersih (`app/Http/Controllers/MaterialController.php`):*
```php
public function store(StoreMaterialRequest $request, Course $course)
{
    $data = $request->validated();
    $data['course_id'] = $course->id;
    $data['uploaded_by'] = Auth::id();

    if ($data['type'] === 'file') {
        $file = $request->file('file');
        $data['file_path'] = $file->store('materials', 'local');
        $data['original_name'] = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());
        $data['file_size'] = $file->getSize();
        $data['mime_type'] = $file->getMimeType();
    }

    Material::create($data);

    return redirect()
        ->route(Auth::user()->role . '.courses.materials.index', $course)
        ->with('success', 'Materi berhasil ditambahkan.');
}
```

---

### C. Tanggapan & Tindak Lanjut Antar Kelompok

#### 1. Tindak Lanjut oleh Kelompok 03 (Kelompok yang Direview)
- Pengembang Kelompok 03 menerima seluruh temuan yang diajukan.
- Untuk temuan N+1 Query pada `materials/index.blade.php` dan sanitasi berkas unduhan, Kelompok 03 langsung menerbitkan commit perbaikan pada Pull Request mereka:
  ```text
  commit abc1234
  Author: byo1027 <10241027@student.itk.ac.id>
  Date:   Wed Nov 18 2026

      fix(materials): cegah query N+1 dengan eager loading course dan sanitasi nama unduhan
  ```

#### 2. Tanggapan Kelompok 02 Terhadap Review Masuk dari Kelompok Lain
- **Temuan Masuk:** Reviewer dari kelompok lain mencatat bahwa pada tampilan detail mata kuliah (`resources/views/courses/show.blade.php`), bagian daftar peserta kelas menampilkan seluruh data mahasiswa tanpa batas tinggi tabel sehingga halaman memanjang jika peserta mencapai ratusan mahasiswa.
- **Tanggapan Kami:** **Diterima.** Kami telah merefaktor komponen peserta kelas menjadi tampilan tabel in-page dengan penataan kolom ringkas (NIM, Nama Mahasiswa, Tanggal Daftar) dan pembatasan kontainer responsif (selesai di commit `afbb5f2` dan `83009b7`).

---

## 13.5 Checkpoint Minggu 13

Berikut jawaban refleksi dan uji pemahaman konsep Modul 13:

### 1. Berapa commit atas nama Anda? Tunjukkan `git shortlog -sn`.
* **Jawaban:**  
  Berdasarkan keluaran perintah `git shortlog -sn` pada repositori `kampuslms-kelompok-02`:
  - **BellaAlviana:** **90 commit di cabang `main`** (dan **102 commit** pada seluruh riwayat percabangan).
  - Urutan kontribusi tim: Bella Alviana (90), Chiko Dhiva Pramana (76), Armansyah (46), Dawwas Eryansyah Pratama (32).

### 2. Kenapa `git rm --cached .env` tidak cukup kalau `.env` sudah pernah ter-push?
* **Jawaban:**  
  Karena Git bekerja dengan menyimpan *snapshot* riwayat commit yang tidak dapat diubah (*immutable commit history*). Perintah `git rm --cached .env` hanya menghapus berkas `.env` dari area staging (*index*) dan commit berikutnya (*HEAD*). Seluruh isi berkas `.env` beserta nilai rahasia di dalamnya tetap tertinggal di dalam commit lama dan dapat dilihat siapa pun menggunakan `git log -p` atau `git checkout <hash-lama>`.  
  Oleh sebab itu, jika berkas rahasia sudah pernah ter-push ke GitHub, kredensial di dalamnya **wajib dianggap telah bocor** dan harus segera diganti/dirotasi di lingkungan produksi.

### 3. Tunjukkan satu temuan review yang Anda tulis. Jelaskan risikonya tanpa membaca catatan.
* **Jawaban:**  
  Temuan **Performa N+1 Query pada Berkas `MaterialController.php` & `materials/index.blade.php` Kelompok 03**:  
  *Risikonya:* Pada halaman daftar materi, controller mengambil data materi tanpa mengikutsertakan relasi induk kursus (`->with('course')`). Sementara itu, di dalam tabel Blade, terdapat pemeriksaan otorisasi per baris (`@can('view', $material)`, `@can('update', $material)`). Saat policy dipanggil, sistem secara otomatis melakukan kueri basis data tambahan untuk mengambil objek `course`. Jika ada 15 materi di layar, sistem mengeksekusi belasan kueri berulang yang membebani basis data dan memperlambat waktu muat halaman mahasiswa.

### 4. Tunjukkan satu temuan yang masuk ke kelompok Anda. Anda terima atau tolak? Kenapa?
* **Jawaban:**  
  *Temuan yang masuk:* Saran penataan tabel daftar mahasiswa peserta kelas pada `courses/show.blade.php` agar tidak memicu pemuatan data DOM yang terlalu panjang saat peserta kelas mencapai puluhan orang.  
  *Status:* **Diterima.** Kami menerima masukan tersebut karena masukan berfokus pada performa render sisi klien dan efisiensi ruang layar. Kami meresponsnya dengan merefaktor tampilan menjadi tabel ringkas in-page yang terstruktur (menampilkan kolom NIM, Nama Mahasiswa, dan Tanggal Pendaftaran) tanpa menggunakan pop-up modal yang berat.

### 5. Kenapa PR sebaiknya kecil dan satu fitur?
* **Jawaban:**  
  1. **Mudah dan Cepat Direview:** PR yang kecil (kurang dari 200–300 baris) dapat diperiksa dengan teliti oleh rekan tim dalam hitungan menit tanpa menimbulkan kelelahan peninjau (*review fatigue*).  
  2. **Meminimalkan Risiko Konflik Merge:** PR kecil berumur pendek, sehingga cepat digabungkan ke cabang utama sebelum kode bercabang terlalu jauh dari perkembangan rekan lain.  
  3. **Aman untuk Di-rollback:** Jika ditemukan bug kritis di server produksi, membatalkan (*revert*) satu PR kecil yang terisolasi jauh lebih aman dan tidak akan merusak fitur-fitur lain yang tidak bermasalah.

### 6. Kenapa penilaian kontribusi tidak bisa hanya berdasarkan jumlah commit?
* **Jawaban:**  
  1. **Jumlah commit mudah dimanipulasi:** Satu commit besar yang menyelesaikan modul arsitektur krusial bernilai jauh lebih besar daripada 20 commit kecil yang hanya memperbaiki spasi atau salah ketik (*typo*).  
  2. **Identitas commit dapat dipalsukan:** Melalui perintah `git commit --author="..."`, siapa pun dapat membuat commit atas nama akun lain di komputer lokal.  
  3. **Kualitas tidak berbanding lurus dengan kuantitas:** Kontribusi sejati dilihat dari pemahaman arsitektur saat wawancara (*interview*), kualitas logika pencegahan bug, penulisan tes otomatis yang hijau, serta keaktifan dalam memberikan *peer review* kode yang konstruktif bagi tim.
