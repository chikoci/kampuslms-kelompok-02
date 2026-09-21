# Catatan Praktikum Minggu 4
**Nama:** Armansyah  
**NIM:** 10241013

## Bagian READ: Telusuri satu siklus form gagal
Tanpa AI. Buat form tambah mata kuliah, isi dengan data yang pasti tidak valid (SKS = 99), kirim, lalu jawab
#

### 1. Method apa yang menerima request? Di controller mana?
Permintaan (request) dari formulir penambahan mata kuliah menggunakan metode `POST`. Jalur rute tersebut dideklarasikan melalui `Route::resource('courses', CourseController::class);` pada file routes dan ditangani oleh method `store()` pada **`App\Http\Controllers\CourseController`**.

#
### 2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
Validasi dilakukan sebelum method `store()` dijalankan, karena controller menggunakan Form Request yaitu `StoreCourseRequest`.

```php
public function store(StoreCourseRequest $request)
{
    Course::create($request->validated());

    return redirect()->route('courses.index')
        ->with('success', 'Mata kuliah berhasil ditambahkan.');
}
```
Aturan validasinya terdapat pada `app/Http/Requests/StoreCourseRequest.php:`

`'sks' => ['required', 'integer', 'between:1,6'],`

Ketika saya mengirim `sks = 99`, Laravel terlebih dahulu memproses `StoreCourseRequest` dan menjalankan aturan validasi tersebut. Nilai 99 tidak memenuhi `between:1,6`, sehingga validasi gagal dan method `store()` tidak dijalankan.

Jadi, berbeda dengan validasi yang ditulis langsung di dalam controller, pada project ini validasi terjadi sebelum baris pertama `store()` dijalankan.

#
### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?

Ketika validasi `StoreCourseRequest` gagal, Laravel melakukan redirect kembali ke halaman yang mengirim request, yaitu halaman form tambah mata kuliah.

Alurnya menjadi:

`GET /courses/create -> isi form dengan SKS = 99 -> POST /courses -> StoreCourseRequest -> validasi gagal -> redirect kembali -> GET /courses/create`

Tujuan redirect saat validasi gagal ditangani oleh mekanisme validasi Laravel pada Form Request, bukan oleh `return redirect()` yang ada di `store()`.

`return redirect()->route('courses.index')` pada `store()` hanya dijalankan apabila validasi berhasil dan data berhasil diproses

#
### 4. Dari mana @error('sks') mengambil pesannya?
`@error('sks')` mengambil pesan kesalahan untuk field `sks` dari error bag yang dibawa melalui session setelah validasi gagal.

Pada `StoreCourseRequest`, terdapat aturan:

```php
'sks' => ['required', 'integer', 'between:1,6'],
```

dan pesan khusus:

```php
'sks.between' => 'Beban SKS harus antara 1 sampai 6.',
```

Ketika `sks = 99`, aturan `between:1,6` gagal. Laravel kemudian memasukkan error untuk field `sks` ke dalam error bag.

Di Blade terdapat:

```blade
@error('sks')
    <p class="form-error">{{ $message }}</p>
@enderror
```

`@error('sks')` memeriksa apakah terdapat error untuk field `sks`, sedangkan `$message` berisi pesan error tersebut.

Jadi pada kasus ini pesan yang ditampilkan adalah:

```text
Beban SKS harus antara 1 sampai 6.
```

Alurnya adalah:

```text
sks = 99
    ↓
between:1,6 gagal
    ↓
error "sks"
    ↓
session / error bag
    ↓
@error('sks')
    ↓
$message
```

#
### 5. Dari mana old('sks') mengambil nilainya? Berapa lama nilai itu bertahan?
Helper `old('sks')` mengambil nilainya dari **Flash Session** pada key internal `_old_input`.  
Ketika validasi gagal, Laravel otomatis memanggil method `withInput()` yang mem-flash input form ke session. Nilai ini hanya bertahan selama **1 request berikutnya saja**. Begitu halaman selesai dirender atau pengguna me-refresh halaman (F5), data flash session tersebut langsung terhapus otomatis dari server.


#
### 6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.
Ketika saya membuka browser, klik kanan **Inspect Element -> tab Application -> Storage -> Cookies -> http://127.0.0.1:8000**, cookie session yang ditemukan bernama:
* **`laravel_session`** : Cookie yang menyimpan ID sesi terenkripsi milik pengguna agar server mengenali request antar-halaman (contoh nilai saat diuji di DevTools: `eyJpdGls...`).
* *(Tambahan)* **`XSRF-TOKEN`** : Cookie yang menyimpan token CSRF terenkripsi untuk keamanan form.
