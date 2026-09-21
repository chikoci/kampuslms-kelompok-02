# Audit State, Keamanan, dan Performa Controller (Minggu 4 - Prompt Pack C)

Dokumen ini berisi hasil audit menyeluruh terhadap implementasi [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php) berdasarkan 5 kriteria pemeriksaan resmi dari Prompt Pack C.

---

## Kode Controller yang Diaudit
```php
public function index(Request $request)
{
    $courses = Course::query()
        ->with('lecturer')
        ->when($request->filled('q'), fn ($query) =>
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('code', 'like', '%' . $request->q . '%');
            }))
        ->when($request->filled('status'), fn ($query) =>
            $query->where('status', $request->status))
        ->latest()
        ->paginate(15)
        ->withQueryString();

    return view('courses.index', compact('courses'));
}
```

---

## Hasil Audit 5 Poin Pemeriksaan

### 1. Apakah filter tetap bertahan saat pengguna berpindah halaman?
* **Status:** **SUDAH BENAR**
* **Lokasi Baris:** Baris 28-29 pada `CourseController.php`
* **Analisis & Bukti:**
  Query builder diakhiri dengan `->paginate(15)->withQueryString()`. Method bawaan Laravel `withQueryString()` memastikan semua parameter filter yang ada pada URL saat ini (seperti `?q=basis&status=active`) secara otomatis disertakan ke dalam setiap tautan nomor halaman (halaman 2, 3, dst) yang dirender di view `{{ $courses->links() }}`.
* **Risiko jika salah:** Tanpa `withQueryString()`, saat pengguna mencari mata kuliah lalu mengklik halaman 2, parameter filter akan hilang dan halaman 2 menampilkan data keseluruhan tanpa filter. Masalah ini sudah tertangani.

---

### 2. Apakah state disimpan di tempat yang tepat (query string vs session)?
* **Status:** **SUDAH BENAR**
* **Lokasi Baris:** Baris 20 dan 25 pada `CourseController.php`
* **Analisis & Bukti:**
  State filter dan pencarian diambil murni dari objek `$request` melalui URL query string (`$request->filled('q')` dan `$request->filled('status')`). Tidak ada penyimpanan filter ke dalam session server (`session()->put()`).
* **Penjelasan Masalah Jika Disimpan di Session (Kasus Dua Tab):**
  Jika filter disimpan di session, server akan mengikat satu filter global pada sesi pengguna tersebut. Apabila pengguna membuka Tab 1 (mencari "Pemrograman Web") lalu membuka Tab 2 (mencari "Basis Data"), session akan tertimpa oleh pencarian Tab 2. Ketika pengguna kembali ke Tab 1 dan berpindah ke halaman berikutnya, hasil yang tampil justru berubah menjadi "Basis Data". Selain itu, tautan hasil pencarian tidak bisa dibagikan (di-share) ke orang lain dan riwayat tombol back/forward browser menjadi rusak. Penempatan state di query string adalah pendekatan yang benar dan stateless.

---

### 3. Apakah ada N+1 query?
* **Status:** **SUDAH BENAR (Bebas N+1 Query)**
* **Lokasi Baris:** Baris 19 pada `CourseController.php`
* **Analisis & Bukti:**
  Query diawali dengan eager loading relasi dosen: `Course::query()->with('lecturer')`. Pada view `courses/index.blade.php`, setiap baris menampilkan nama dosen pengampu (`$course->lecturer?->name`).
  Dengan `with('lecturer')`, Laravel hanya mengeksekusi tepat **2 query SQL**:
  1. `SELECT * FROM courses WHERE ... LIMIT 15`
  2. `SELECT * FROM users WHERE id IN (...)`
* **Risiko jika salah:** Jika pemanggilan eager loading dihilangkan, sistem akan mengeksekusi 1 query untuk mengambil daftar mata kuliah ditambah 1 query user untuk setiap baris (total 16 query untuk 15 baris data), yang menjadi sumber kelambatan utama aplikasi web.

---

### 4. Apakah input pencarian berpotensi SQL injection?
* **Status:** **SUDAH BENAR (Aman dari SQL Injection)**
* **Lokasi Baris:** Baris 20-24 pada `CourseController.php`
* **Analisis & Bukti:**
  Pencarian menggunakan Eloquent query builder:
  ```php
  $q->where('name', 'like', '%' . $request->q . '%')
    ->orWhere('code', 'like', '%' . $request->q . '%');
  ```
  Eloquent di Laravel menggunakan mekanisme PDO Parameter Binding secara otomatis. Nilai input dari pengguna (`$request->q`) tidak pernah digabungkan secara langsung (string concatenation) ke dalam teks query SQL mentah. Simbol atau perintah SQL berbahaya (seperti `' OR '1'='1` atau `; DROP TABLE`) akan diperlakukan murni sebagai nilai string data pencarian, bukan sebagai instruksi SQL yang dieksekusi oleh mesin database.

---

### 5. Apakah pola PRG diterapkan pada method store dan update?
* **Status:** **SUDAH BENAR**
* **Lokasi Baris:** Baris 51 dan 81 pada `CourseController.php`
* **Analisis & Bukti:**
  - Method `store()` mengakhiri proses dengan:
    `return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil ditambahkan.');`
  - Method `update()` mengakhiri proses dengan:
    `return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil diperbarui.');`
* **Risiko jika salah:** Jika method `store` atau `update` mengembalikan `return view()`, maka ketika pengguna menekan tombol F5 (Refresh) setelah menyimpan data, browser akan memunculkan peringatan "Confirm Form Resubmission" dan mengirim ulang request POST tersebut, yang menyebabkan duplikasi data di database. Dengan pola PRG (Post-Redirect-Get), browser diarahkan melakukan request GET baru sehingga aman dari resubmission ganda. Pesan sukses ditaruh di flash session yang otomatis terhapus setelah ditampilkan satu kali.

---

## Ringkasan Kesimpulan Audit
Seluruh 5 kriteria arsitektur, keamanan, dan performa pada [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php) telah terpenuhi 100% tanpa adanya celah ataupun pelanggaran konvensi Laravel 12. Kode siap diuji tembus pada tahap berikutnya (READ, BREAK, dan Prompt Pack D).
