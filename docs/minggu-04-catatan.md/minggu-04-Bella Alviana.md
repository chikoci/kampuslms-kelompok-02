# Minggu 4
**NAMA:** Bella Alviana
**NIM:** 10241015

## READ

**1. Method apa yang menerima request? Di controller mana?**
Jawab: Request dari form dikirim menggunakan method `POST` ke URL `/courses`. Request tersebut diterima oleh method `store()` yang ada di `App\Http\Controllers\CourseController`. Jadi, ketika tombol Simpan ditekan, data dari form masuk ke method `store()`.

**2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?**
Jawab: Validasi terjadi *sebelum* kode di dalam method `store()` dijalankan. Hal ini karena `store()` menggunakan `StoreCourseRequest`. Laravel akan mengecek data terlebih dahulu sesuai aturan yang ada di Form `Request`. Karena saya memasukkan `SKS = 99`, sedangkan aturan SKS hanya `1 sampai 6`,maka tidak terpenuhi sehingga request dihentikan dan method `store()` tidak melanjutkan proses `Course::create()`. Hal ini sesuai dengan fungsi Form Request pada modul sebagai tempat validasi sebelum data diproses oleh controller.

**3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?**
Jawab: Setelah validasi gagal, Laravel mengembalikan saya ke halaman form sebelumnya, yaitu `/courses/create`. Proses kembali ke halaman tersebut dilakukan otomatis oleh Laravel ketika validasi gagal. Laravel juga membawa pesan error dan data yang sebelumnya saya masukkan agar bisa ditampilkan kembali di form.

**4. Dari mana `@error('sks')` mengambil pesannya?**
Jawab: `@error('sks')` mengambil pesan dari hasil validasi Laravel untuk field sks. Jika pengguna memasukkan 99, aturan `between:1,6` gagal sehingga Laravel menyediakan pesan error untuk field tersebut. Pesan tersebut kemudian dapat ditampilkan melalui variabel `$message` di dalam `@error('sks')` berisi pesan kustom yang sudah kita buat: *"Beban SKS harus antara 1 sampai 6."*

**5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?**
Jawab: `old('sks')` mengambil nilai SKS yang sebelumnya dimasukkan pada form dan disimpan sementara oleh Laravel ketika validasi gagal. Alurnya dimulai dari file `resources/views/courses/create`.`blade.php`, saat pengguna mengisi SKS dan mengirim form, kemudian data masuk ke `StoreCourseRequest.php` untuk divalidasi. Jika `SKS = 99` tidak memenuhi aturan `between:1,6`, Laravel menyimpan input sebelumnya ke session dan mengembalikan pengguna ke halaman form. Saat `create.blade.php` dibuka kembali, `old('sks')` mengambil nilai SKS yang tadi dimasukkan sehingga angka 99 masih muncul di form. Nilai tersebut hanya digunakan untuk satu request berikutnya, kemudian dihapus dari session.

**6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.**
Jawab: Saya membuka browser, klik `f12` **Inspect Element → DevTools → Application → Storage → Cookies → http://127.0.0.1:8000**. Di sana saya menemukan cookie session dengan nama `laravel_session`. Cookie ini digunakan oleh Laravel untuk membantu menyimpan `session` sehingga data atau keadaan pengguna dapat tetap dikenali antar-request (contoh nilai saat diuji di DevTools: `eyJpdGls...`).
*(Tambahan)* **`XSRF-TOKEN`** : Cookie yang menyimpan token CSRF terenkripsi untuk keamanan form.

## BREAK
**1. Hapus `@csrf` dari form, lalu kirim**
Jawab: @csrf berfungsi untuk menghasilkan token keamanan rahasia berupa input tersembunyi yang memvalidasi bahwa permintaan (request) `POST` pengiriman data formulir tersebut benar-benar berasal dari pengguna sah di aplikasi bukan dari situs luar yang berbahaya. Jika @csrf dihapus di file `resources/views/courses/create.blade.php` maka request akan menghasilkan *error **419 | page expired***

**2. Ganti `$request->validated() ` menjadi `$request->all()`, lalu kirim field liar lewat `curl`**
Jawab:  
    - **Prediksi:** Jika menggunakan `$request->all()`, data tambahan yang tidak ada dalam aturan validasi bisa ikut diterima oleh controller. Jadi, field yang seharusnya tidak boleh masuk bisa ikut diproses dan menyebabkan risiko **mass assignment**.
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
        curl -X POST http://127.0.0.1:8000/courses \
        -H "X-CSRF-TOKEN: <token>" \
        -b cookies.txt \
        -d "code=XX01" \
        -d "name=Uji" \
        -d "sks=3" \
        -d "lecturer_id=1" \
        -d "status=active" \
        -d "is_admin=1"
        ```
   - **Sebenarnya:** Field tambahan yang tidak seharusnya diterima ternyata tetap terbawa ke dalam data ketika menggunakan `$request->all()`. Nilai `"field_liar": "hacked"` ikut diterima oleh request, sedangkan saat menggunakan `$request->validated()`, field tersebut tidak ikut karena tidak tercantum dalam aturan validasi. Dari percobaan ini terlihat bahwa `$request->validated()` lebih membatasi data yang dapat diproses dibandingkan `$request->all()`.


**3. Hapus validasi `exists:users`,id pada `lecturer_id`, kirim `lecturer_id=99999`**
Jawab: 
   - **Prediksi:** Kalau validasi `exists:users,id` dihapus, ID dosen `99999` tidak akan dicek lagi oleh Laravel. Jadi, data tersebut bisa lolos dari validasi. Namun, kalau database masih punya aturan Foreign Key, database akan menolak data tersebut karena ID dosen `99999` tidak ada di tabel `users`.

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
          -d "code=KULIAH78" 
          -d "name=Uji" 
          -d "sks=3" \
          -d "lecturer_id=99999" 
          -d "status=active" | head -n 5
        ```
   - **Sebenarnya:** ada perbedaan hasil antara saat validasi `exists` digunakan dan saat validasi tersebut dihapus. Ketika aturan `exists` dihapus, ID dosen `99999` lolos dari pemeriksaan Laravel, tetapi kemudian database menolaknya karena ID tersebut tidak ada di tabel `users`. Akibatnya, server menghasilkan **HTTP 500 Internal Server Error** karena terjadi masalah pada Foreign Key. Sementara itu, ketika aturan `exists` masih digunakan, Laravel langsung menolak ID `99999` sebelum data dikirim ke database. Hasilnya adalah **HTTP 302 Found**, yaitu kembali ke halaman form, tanpa menyebabkan error 500.


**4. Hapus validasi `in:...` pada status, kirim `status=superadmin`**
Jawab: 
- **Prediksi:** Jika aturan `in:draft,active,archived` dihapus, nilai status seperti `superadmin` tidak akan dicek lagi oleh Laravel. Jadi, Laravel akan menganggap nilai tersebut boleh diproses. Namun, database masih memiliki aturan bahwa status hanya boleh `draft`, `active`, atau `archived`, sehingga database kemungkinan akan menolak data tersebut.

- **Cara Uji:**

    1. Buka file `StoreCourseRequest.php`, lalu cari validasi `status`. Ubah dari:

    ```php
    'status' => ['required', 'in:draft,active,archived'],
    ```

    menjadi:

    ```php
    'status' => ['required'],
    ```

    Dengan begitu, Laravel tidak lagi membatasi pilihan status.

    2. Karena menggunakan `curl`, ambil token CSRF terbaru terlebih dahulu:

    ```bash
    curl -s -c cookies.txt http://127.0.0.1:8000/courses/create | grep '_token'
    ```

    3. Setelah mendapatkan token, kirim data menggunakan `curl`. Gunakan `code` yang belum pernah digunakan dan `lecturer_id` yang memang ada di database, misalnya `2`:
      ```bash
      curl -i -s -X POST http://127.0.0.1:8000/courses \
      -H "X-CSRF-TOKEN: <token>" \
      -b cookies.txt \
      -d "code=KULIAH91" \
      -d "name=Uji Status" \
      -d "sks=3" \
      -d "lecturer_id=2" \
      -d "status=superadmin" | head -n 5
      ```

- **Sebenarnya:** Hasilnya berbeda tergantung validasi `in` masih digunakan atau tidak. Jika aturan `in` dihapus, Laravel akan menerima `superadmin` karena tidak ada lagi aturan yang melarangnya. Setelah itu data diteruskan ke database, tetapi database akan menolak karena `superadmin` bukan salah satu status yang diperbolehkan, sehingga muncul **HTTP 500 Internal Server Error**. Sebaliknya, jika aturan `in:draft,active,archived` masih ada, Laravel akan menolak `superadmin` terlebih dahulu dan mengembalikan **HTTP 302 Found** ke halaman form. Jadi, validasi Laravel berfungsi untuk menyaring data sebelum data tersebut sampai ke database.


**5. Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2**
Jawab: 
- **Prediksi:** Jika `withQueryString()` dihapus, filter yang sedang digunakan kemungkinan tidak ikut terbawa saat berpindah ke halaman berikutnya. Jadi, ketika pengguna sudah memilih filter tertentu lalu masuk ke halaman 2, filter tersebut bisa hilang dan data yang ditampilkan kembali menjadi semua data.

- **Cara Uji:**

    1. Buka file `UserController.php` atau `CourseController.php`, lalu cari bagian pagination. Misalnya:

    ```php
    ->latest()
    ->paginate(15)
    ->withQueryString();
    ```

    Hapus `->withQueryString()`, sehingga menjadi:

    ```php
    ->latest()
    ->paginate(15);
    ```

    2. Jalankan website dan buka halaman pengguna dengan filter, misalnya:

    ```text
    http://127.0.0.1:8000/users?q=&role=mahasiswa
    ```

    3. Pilih atau pastikan filter **Mahasiswa** sedang aktif, kemudian klik tombol **halaman 2** pada bagian pagination.

- **Sebenarnya:** Jika `withQueryString()` dihapus, URL halaman 2 hanya membawa parameter halaman, misalnya `?page=2`. Parameter `role=mahasiswa` tidak ikut terbawa. Akibatnya, filter Mahasiswa hilang dan data di halaman 2 bisa kembali menampilkan seluruh pengguna. Jika `withQueryString()` digunakan, parameter filter tetap dibawa ke halaman berikutnya. Contohnya menjadi `?q=&role=mahasiswa&page=2`, sehingga filter tetap aktif dan data yang ditampilkan masih sesuai dengan pilihan pengguna.

**6. Ganti `return redirect()` menjadi `return view()` pada store, lalu tekan `F5` setelah simpan.**
Jawab: 
- **Prediksi:** Jika setelah menyimpan data `store()` langsung menggunakan `return view()` tanpa `redirect()`, browser masih menganggap halaman tersebut berasal dari proses **POST**. Akibatnya, ketika halaman di-refresh dengan **F5**, browser bisa meminta untuk mengirim ulang data form. Jika diteruskan, data yang sama dapat terkirim lagi dan berisiko membuat data ganda.

- **Cara Uji:**

        1. Buka file `CourseController.php`, lalu cari method `store()`. Ubah bagian akhirnya dari `return redirect()` menjadi:

        ```php
        Course::create($request->validated());

        $courses = Course::latest()->paginate(15);

        return view('courses.index', compact('courses'));
        ```

        2. Buka halaman:

        ```text
        http://127.0.0.1:8000/courses/create
        ```

        3. Isi form dengan data yang valid, kemudian klik **Simpan**.

        4. Setelah halaman daftar mata kuliah muncul, tekan **F5** untuk melakukan refresh.

- **Sebenarnya:** Saat menggunakan `return view()`, browser masih berada pada proses **POST `/courses`**. Ketika F5 ditekan, browser dapat menampilkan peringatan seperti **"Confirm Form Resubmission"** karena browser akan mengirim ulang data form. Jika dilanjutkan, data yang sama dapat diproses kembali dan berpotensi masuk ke database lagi. Berbeda dengan `return redirect()`. Setelah data disimpan, Laravel mengarahkan browser ke halaman index menggunakan **HTTP 302**, sehingga browser melakukan request **GET**. Ketika halaman tersebut di-refresh, yang diulang hanya request GET, bukan proses penyimpanan data.

- **Kesimpulan:** `redirect()` digunakan setelah proses POST agar browser tidak mengirim ulang data ketika halaman di-refresh. Inilah yang disebut pola **Post-Redirect-Get (PRG)**.


**7. Hapus `old(...)` dari semua input, lalu kirim form dengan satu kesalahan**
Jawab:
- **Prediksi:** Jika `old()` dihapus dari form, ketika validasi gagal, data yang sebelumnya sudah diketik pengguna kemungkinan akan hilang. Akibatnya, pengguna harus mengisi kembali semua data dari awal.

- **Cara Uji:**

        1. Buka file `create.blade.php`.
        2. Cari input yang menggunakan `old()`, misalnya:

        ```blade
        <input type="text" name="code" value="{{ old('code') }}">
        <input type="text" name="name" value="{{ old('name') }}">
        <textarea name="description">{{ old('description') }}</textarea>
        ```

        3. Hapus `old()` sehingga menjadi:

        ```blade
        <input type="text" name="code" value="">
        <input type="text" name="name" value="">
        <textarea name="description"></textarea>
        ```

        4. Isi form dengan lengkap, tetapi gunakan kode mata kuliah yang sudah ada agar validasi gagal.
        5. Klik **Simpan Mata Kuliah**.

- **Sebenarnya:** Saat `old()` dihapus, pesan error tetap muncul karena validasi server masih berjalan, tetapi data yang sudah diketik pada form menjadi kosong. Pengguna harus mengetik ulang data tersebut. Jika `old()` tetap digunakan, data yang sebelumnya diketik akan muncul kembali setelah validasi gagal. Jadi, pengguna hanya perlu memperbaiki bagian yang salah tanpa mengisi semua data dari awal.