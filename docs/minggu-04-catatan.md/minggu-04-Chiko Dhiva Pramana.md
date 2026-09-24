# Catatan Praktikum Minggu 4

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ

Pada tahap ini, saya mencoba mengisi form tambah mata kuliah (`/courses/create`) dengan data yang sengaja dibuat tidak valid, yaitu mengisi **SKS = 99** (melebihi batas maksimal 6 SKS), lalu mengklik tombol Simpan.

Berikut hasil penelusuran dan jawaban dari 6 pertanyaan pengamatan alur:

### 1. Method apa yang menerima request? Di controller mana?
Request POST dari form dikirim ke URL `/courses` dan diterima oleh method **`store()`** di dalam controller **`App\Http\Controllers\CourseController`**.

### 2. Di titik mana persisnya validasi terjadi: sebelum atau sesudah baris pertama method controller?
Validasi terjadi **sebelum** baris pertama kode di dalam method controller dijalankan.  
*Alasannya:* Parameter pada method `store(StoreCourseRequest $request)` menggunakan type-hinting kelas Form Request. Service container Laravel secara otomatis memvalidasi data request saat proses dependency injection sebelum memanggil method `store()`. Karena SKS = 99 melanggar aturan `between:1,6`, Laravel langsung membatalkan eksekusi controller dan melempar `ValidationException` untuk me-redirect balik. Jadi, baris kode pertama di dalam `store()` sama sekali tidak sempat dieksekusi.

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
Laravel me-redirect kembali ke halaman asal form, yaitu **`/courses/create`**.  
Yang menentukan tujuannya adalah handler exception validasi bawaan Laravel (`ValidationException`). Laravel membaca header HTTP `Referer` dari request browser atau membaca riwayat URL sebelumnya (`url()->previous()`), lalu otomatis mengeksekusi `redirect()->back()`.

### 4. Dari mana `@error('sks')` mengambil pesannya?
Direktif `@error('sks')` mengambil pesan error dari variabel global **`$errors`** (View Error Bag).  
Variabel `$errors` ini disediakan oleh middleware bawaan Laravel (`ShareErrorsFromSession`) yang membaca data error dari **Flash Session** hasil redirect validasi gagal tadi. Variabel `$message` di dalam blok `@error` berisi pesan kustom yang sudah kita buat: *"Beban SKS harus antara 1 sampai 6."*

### 5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?
Helper `old('sks')` mengambil nilainya dari **Flash Session** pada key internal `_old_input`.  
Ketika validasi gagal, Laravel otomatis memanggil method `withInput()` yang mem-flash input form ke session. Nilai ini hanya bertahan selama **1 request berikutnya saja**. Begitu halaman selesai dirender atau pengguna me-refresh halaman (F5), data flash session tersebut langsung terhapus otomatis dari server.

### 6. Cookie Session di DevTools
Ketika saya membuka browser, klik kanan **Inspect Element -> tab Application -> Storage -> Cookies -> http://127.0.0.1:8000**, cookie session yang ditemukan bernama:
* **`laravel_session`** : Cookie yang menyimpan ID sesi terenkripsi milik pengguna agar server mengenali request antar-halaman (contoh nilai saat diuji di DevTools: `eyJpdGls...`).
* *(Tambahan)* **`XSRF-TOKEN`** : Cookie yang menyimpan token CSRF terenkripsi untuk keamanan form.

## Bagian BREAK
1. **create.blade.php**
Prediksi: Akan terjadi error karena request yang tidak ada csrf akan ditolak
**sebenarnya:** Browser langsung menampilkan halaman error 419 | PAGE EXPIRED. Ini membuktikan Laravel menolak request POST yang tidak memiliki token CSRF untuk melindungi form dari serangan lintas situs.

2. **CourseController.php (Ganti $request->validated() menjadi $request->all())**
   - **Prediksi:** Filter whitelist dari Form Request akan mati, sehingga field liar yang diselipkan dari luar bisa ikut lolos masuk ke controller (mass assignment terbuka).
   - **Cara Uji:**
     1. Mengubah method `store()` di `CourseController.php` agar mengembalikan respons JSON perbandingan data:
        ```php
        return response()->json([
            'hasil_all'       => $request->all(),
            'hasil_validated' => $request->validated(),
        ]);
        ```
     2. Mengambil token CSRF dan menyimpan session ke `cookies.txt`:
        ```bash
        curl -s -c cookies.txt http://127.0.0.1:8000/courses/create | grep '_token'
        ```
     3. Mengirim request POST via curl dengan menyisipkan input liar `field_liar=hacked`:
        ```bash
        curl -s -X POST http://127.0.0.1:8000/courses \
          -H "X-CSRF-TOKEN: <token>" \
          -b cookies.txt \
          -d "code=DEMO02" -d "name=Demo" -d "sks=3" \
          -d "lecturer_id=2" -d "status=active" \
          -d "field_liar=hacked"
        ```
   - **Sebenarnya:** Input liar berhasil menembus controller. Nilai `"field_liar": "hacked"` ikut masuk ke dalam `$request->all()`, sedangkan pada `$request->validated()` field liar tersebut otomatis dibuang karena tidak terdaftar di rules. Ini membuktikan `$request->all()` mematikan proteksi whitelist Form Request.

3. **StoreCourseRequest.php (Hapus validasi exists:users,id pada lecturer_id)**
   - **Prediksi:** Input dengan ID dosen fiktif (99999) akan lolos dari validasi Form Request Laravel karena aturan `exists` dimatikan, namun akan ditolak oleh database MySQL saat query insert dijalankan karena melanggar Foreign Key Constraint (data yatim).
   - **Cara Uji:**
     1. Menghapus aturan `exists:users,id` pada field `lecturer_id` di `StoreCourseRequest.php`:
        ```php
        'lecturer_id' => ['required', 'integer'],
        ```
     2. Mengirim request POST via curl dengan ID dosen fiktif `lecturer_id=99999`:
        ```bash
        curl -i -s -X POST http://127.0.0.1:8000/courses \
          -H "X-CSRF-TOKEN: <token>" \
          -b cookies.txt \
          -d "code=KULIAH78" -d "name=Uji" -d "sks=3" \
          -d "lecturer_id=99999" -d "status=active" | head -n 5
        ```
   - **Sebenarnya:** Terjadi perbedaan status HTTP yang sangat nyata:
     * **Saat aturan `exists` dihapus (kondisi rusak):** Server merespons **`HTTP/1.1 500 Internal Server Error`**. Form Request meloloskan data yatim tersebut, lalu server crash di lapisan MySQL dengan pesan `SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails`.
     * **Saat aturan `exists` aktif (kondisi normal):** Server merespons **`HTTP/1.1 302 Found`** (redirect balik ke form create). Form Request berhasil mencegat ID fiktif tersebut di gerbang aplikasi sehingga database tidak pernah disentuh dan server tidak mengalami crash 500.

4. **StoreCourseRequest.php (Hapus validasi in:... pada status, kirim status=superadmin)**
   - **Prediksi:** Input dengan nilai status di luar opsi yang diizinkan (misal `superadmin`) akan lolos dari validasi Form Request Laravel karena aturan `in:...` dimatikan, namun akan ditolak oleh database MySQL karena kolom `status` bertipe ENUM (`draft`, `active`, `archived`).
   - **Cara Uji:**
     1. Menghapus aturan `in:draft,active,archived` pada field `status` di `StoreCourseRequest.php`:
        ```php
        'status' => ['required'],
        ```
     2. Mengambil token CSRF terbaru jika session habis:
        ```bash
        curl -s -c cookies.txt http://127.0.0.1:8000/courses/create | grep '_token'
        ```
     3. Mengirim request POST via curl dengan `status=superadmin` (pastikan `code` unik dan `lecturer_id` valid, misal `2`):
        ```bash
        curl -i -s -X POST http://127.0.0.1:8000/courses \
          -H "X-CSRF-TOKEN: <token>" \
          -b cookies.txt \
          -d "code=KULIAH91" -d "name=Uji Status" -d "sks=3" \
          -d "lecturer_id=2" -d "status=superadmin" | head -n 5
        ```
   - **Sebenarnya:** Terjadi perbedaan status HTTP yang jelas:
     * **Saat aturan `in:...` dihapus (kondisi rusak):** Server merespons **`HTTP/1.1 500 Internal Server Error`**. Form Request menganggap nilai `superadmin` sah, namun database MySQL menolaknya karena kolom `status` bertipe ENUM (`SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1`).
     * **Saat aturan `in:...` aktif (kondisi normal):** Server merespons **`HTTP/1.1 302 Found`** (redirect balik ke form create). Form Request berhasil mencegat nilai di luar enum di lapisan aplikasi sebelum query database dieksekusi.

5. **Controller Pagination (Hapus ->withQueryString() pada pagination)**
   - **Prediksi:** Saat pengguna memfilter data (misal pencarian kata kunci atau filter role) lalu berpindah ke halaman berikutnya (halaman 2), parameter filter pada URL akan hilang karena Laravel secara default hanya menyertakan parameter `?page=...` tanpa membawa query string yang sedang aktif.
   - **Cara Uji:**
     1. Menghapus method `->withQueryString()` pada pemanggilan pagination di controller (misal `UserController.php` atau `CourseController.php`):
        ```php
        ->latest()
        ->paginate(15);
        // ->withQueryString();
        ```
     2. Membuka halaman di browser dengan menerapkan filter (misal filter role mahasiswa):
        `http://127.0.0.1:8000/users?q=&role=mahasiswa`
     3. Mengklik tombol halaman **2** pada komponen pagination di bagian bawah tabel.
   - **Sebenarnya:** Terjadi perbedaan perilaku navigasi dan keutuhan filter:
     * **Saat `withQueryString()` dihapus (kondisi rusak):** URL browser berubah menjadi `http://127.0.0.1:8000/users?page=2`. Parameter filter `role=mahasiswa` tertinggal dan hilang total. Pilihan dropdown ter-reset kembali ke "-- Semua Role --", dan saat berpindah halaman data kembali bercampur dengan seluruh data pengguna tanpa filter.
     * **Saat `withQueryString()` aktif (kondisi normal):** URL browser tetap mempertahankan query filter: `http://127.0.0.1:8000/users?q=&role=mahasiswa&page=2`. Dropdown tetap konsisten terpilih Mahasiswa, dan data yang disajikan di halaman berikutnya tetap sesuai dengan kriteria filter yang diinginkan pengguna.

6. **CourseController.php (Ganti return redirect() menjadi return view() pada store)**
   - **Prediksi:** Jika request form POST langsung mengembalikan view tanpa redirect, request aktif di browser akan tetap berstatus POST (melanggar pola Post-Redirect-Get). Akibatnya, jika pengguna menekan tombol refresh (F5), browser akan memunculkan dialog konfirmasi pengiriman ulang formulir dan data berisiko terkirim ganda ke database.
   - **Cara Uji:**
     1. Mengubah method `store()` di `CourseController.php` agar langsung merender view `courses.index` alih-alih melakukan `redirect()`:
        ```php
        Course::create($request->validated());

        $courses = Course::latest()->paginate(15);
        return view('courses.index', compact('courses'));
        ```
     2. Mengakses halaman form tambah mata kuliah di browser (`/courses/create`), lalu mengisi data form dan mengklik tombol **Simpan**.
     3. Setelah tabel index tampil, menekan tombol **F5** (Refresh browser).
   - **Sebenarnya:** Terjadi perbedaan penanganan request di browser:
     * **Saat memakai `return view()` (kondisi rusak):** URL browser tetap berada di endpoint POST `/courses`. Saat F5 ditekan, browser langsung menampilkan pop-up peringatan **"Confirm Form Resubmission"** (Konfirmasi Pengiriman Ulang Formulir). Jika pengguna mengklik lanjutkan, browser akan mengeksekusi ulang request POST dengan data yang sama persis sehingga memicu data ganda atau error duplikasi.
     * **Saat memakai `return redirect()` (kondisi normal / pola PRG):** Setelah data tersimpan, server merespons dengan HTTP 302 Redirect ke route index sehingga browser berpindah ke request GET. Saat pengguna menekan F5, browser hanya me-refresh halaman GET biasa secara aman tanpa mengirim ulang data form.

7. **courses/create.blade.php (Hapus old(...) dari semua input form)**
   - **Prediksi:** Ketika terjadi kesalahan validasi dari server (misal memasukkan kode mata kuliah yang sudah terdaftar), data input yang sudah diketik pengguna pada kolom lain akan hilang ter-reset karena form tidak memanggil kembali nilai dari session flash `_old_input`.
   - **Cara Uji:**
     1. Menghapus helper `old(...)` dari atribut `value` pada input kode, nama, dan textarea deskripsi di `create.blade.php`:
        ```blade
        <input type="text" name="code" ... value="">
        <input type="text" name="name" ... value="">
        <textarea name="description" ...></textarea>
        ```
     2. Mengisi seluruh data form tambah mata kuliah dengan lengkap (nama panjang, deskripsi silabus, dosen), namun sengaja memasukkan kode mata kuliah yang sudah pernah terdaftar di sistem (duplikat) agar memicu penolakan validasi server.
     3. Mengklik tombol **Simpan Mata Kuliah**.
   - **Sebenarnya:** Terjadi perbedaan pengalaman pengguna (UX) yang sangat kontras:
     * **Saat `old(...)` dihapus (kondisi rusak):** Muncul pesan error validasi bahwa kode mata kuliah sudah terdaftar, namun seluruh isian lain (nama mata kuliah, deskripsi panjang) langsung lenyap dan kotak input kembali kosong melompong. Pengguna dipaksa mengetik ulang seluruh data dari awal.
     * **Saat `old(...)` aktif (kondisi normal):** Pesan error tetap muncul, namun seluruh teks nama mata kuliah dan deskripsi panjang yang sudah diketik tetap utuh bertahan di dalam form, sehingga pengguna hanya perlu mengganti kode mata kuliahnya saja tanpa harus mengetik ulang semuanya.

---

## 4.4 Checkpoint Minggu 4

### 1. Kenapa validasi di JavaScript tidak dianggap keamanan? Peragakan cara melewatinya.
Validasi JavaScript berjalan di sisi browser (klien), sehingga sepenuhnya berada di bawah kendali pengguna dan bisa dimatikan atau diubah dengan mudah. Fungsi validasi JavaScript murni hanya untuk kenyamanan pengguna (UX) agar tidak perlu menunggu reload halaman saat salah ketik, bukan untuk pertahanan data.

**Cara melewatinya:**
* Buka **Inspect Element** (DevTools) di browser, lalu hapus atribut pembatas pada HTML seperti `required`, `max="6"`, atau event listener JS pada form.
* Matikan JavaScript langsung dari pengaturan browser.
* Atau kirim data langsung ke endpoint server menggunakan alat seperti **cURL** atau Postman tanpa lewat browser sama sekali.

### 2. Apa yang dikembalikan `$request->validated()` dan kenapa lebih aman daripada `$request->all()`?
`$request->validated()` hanya mengembalikan data input yang sudah lolos seleksi dan secara eksplisit terdaftar pada method `rules()` di Form Request (menerapkan sistem whitelist).

Lebih aman daripada `$request->all()` karena `$request->all()` mengambil mentah-mentah seluruh parameter yang dikirim dari luar. Jika penyerang menyisipkan field liar berbahaya seperti `role=admin` atau `is_verified=1`, field tersebut akan ikut masuk dan berisiko tersimpan ke database (Mass Assignment). Sedangkan pada `$request->validated()`, field yang tidak terdaftar otomatis dibuang.

### 3. Jelaskan pola PRG. Apa yang terjadi kalau `store` mengembalikan view?
Pola **PRG (Post - Redirect - Get)** adalah alur standar pengiriman form web:
1. Browser mengirim data form menggunakan method **POST**.
2. Server memproses dan menyimpan data, lalu membalas dengan header respons **302 Redirect** mengarah ke URL tujuan.
3. Browser otomatis berpindah melakukan request **GET** ke halaman baru tersebut.

**Jika `store` mengembalikan view (`return view()`):**
Status request aktif di riwayat browser tetap berada di method POST. Saat pengguna menekan tombol **F5 (Refresh)**, browser akan memunculkan dialog peringatan *"Confirm Form Resubmission"* dan mengirim ulang paket data POST tersebut, sehingga data yang sama tersimpan ganda ke database.

### 4. Kenapa filter pencarian sebaiknya di query string, bukan session? Beri satu skenario yang rusak kalau dipindah ke session.
Filter pencarian sebaiknya ditaruh di query string URL (`?q=web&status=active`) karena query string bersifat mandiri per-halaman/per-link. Hal ini memungkinkan halaman hasil pencarian bisa di-bookmark, dicopy-paste untuk dibagikan ke orang lain, dan navigasi tombol Back/Forward browser bekerja dengan normal.

**Skenario yang rusak jika disimpan di session:**
Seorang pengguna membuka aplikasi dalam **2 tab browser sekaligus**. Di Tab 1 dia sedang memfilter mata kuliah "Semester 1", lalu di Tab 2 dia mencari mata kuliah "Semester 5". Karena session bersifat global per pengguna, saat dia kembali ke Tab 1 dan berpindah halaman atau me-refresh, filternya akan tertimpa menjadi "Semester 5" mengikuti aksi terakhir di Tab 2.

### 5. Apa fungsi `@csrf`? Serangan apa yang dicegahnya, dan bagaimana serangan itu bekerja?
Fungsi `@csrf` adalah menyisipkan token rahasia acak (`_token`) ke dalam form HTML agar server Laravel bisa memverifikasi bahwa request POST yang masuk benar-benar berasal dari pengguna di form aplikasi kita sendiri.

**Serangan yang dicegah:** CSRF (Cross-Site Request Forgery).

**Cara kerja serangan:**
Korban sedang dalam kondisi login di web aplikasi (cookie session masih aktif di browsernya). Korban kemudian terpancing membuka tautan atau website berbahaya milik penyerang di tab lain. Website penyerang diam-diam menjalankan script otomatis yang mengirim form POST ke server aplikasi korban (misal request hapus akun atau ubah email). Karena browser otomatis melampirkan cookie milik korban, server akan mengira request tersebut sah jika tidak ada token `@csrf` yang memvalidasinya.

### 6. Kenapa aturan `unique` pada update perlu `ignore()`?
Saat proses edit (update), kita sering kali hanya mengubah sebagian data (misal mengubah nama mata kuliah) tetapi membiarkan kode mata kuliahnya tetap sama.

Jika aturan `unique:courses,code` tidak diberi `ignore()`, validator Laravel akan mengecek ke database dan menemukan bahwa kode tersebut sudah ada (yaitu milik data yang sedang diedit itu sendiri), sehingga validasi akan menolaknya sebagai data duplikat. Penambahan `Rule::unique('courses', 'code')->ignore($this->course)` memberitahu Laravel untuk tetap mengecek keunikan kode terhadap baris lain, tetapi mengabaikan ID dari baris data yang sedang diperbarui saat ini.