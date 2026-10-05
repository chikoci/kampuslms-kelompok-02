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
Ketika saya membuka browser, klik kanan **Inspect Element -> tab Application -> Storage -> Cookies -> https://kampuslms-kelompok-02.test**, cookie session yang ditemukan bernama:
* **`laravel_session`** : Cookie yang menyimpan ID sesi terenkripsi milik pengguna agar server mengenali request antar-halaman (contoh nilai saat diuji di DevTools: `eyjpdil6lmp...`).
* *(Tambahan)* **`XSRF-TOKEN`** : Cookie yang menyimpan token CSRF terenkripsi untuk keamanan form.


## BREAK — Tujuh kerusakan 

### 1. Hapus @csrf dari form, lalu kirim
`resources/views/...`  
Hasilnya adalah Muncul error 419 Page Expired.

**Penjelasan :**  
`@csrf `digunakan untuk memberikan `CSRF` token pada form. Token ini diperiksa Laravel ketika request dikirim. Jika token tidak ada atau tidak sesuai, Laravel menolak request tersebut. Perlindungan ini digunakan untuk mencegah serangan CSRF, yaitu ketika situs lain mencoba membuat browser pengguna yang sedang login mengirim request tanpa sepengetahuan pengguna.

##

### 2. Ganti $request->validated() menjadi $request->all(), lalu kirim field liar lewat curl
perbedaan antara `$request->validated()` dan `$request->all()`. Normalnya controller hanya meneruskan data yang sudah lolos validasi. Kami ubah menjadi `$request->all()` dan mengirim tambahan field `field_liar` untuk melihat apakah data yang tidak divalidasi akan ikut tersimpan. Hasilnya, mata kuliah berhasil dibuat, tetapi `field_liar` tidak masuk ke database karena masih dibatasi oleh `$fillable` pada model. Jadi pengujian ini menunjukkan bahwa validasi Form Request dan perlindungan mass assignment pada model merupakan dua lapisan perlindungan yang berbeda.

##

### 3. Hapus validasi exists:users,id pada lecturer_id, kirim lecturer_id=99999


##

### 4. Hapus validasi in:... pada status, kirim status=superadmin

##

### 5. Hapus ->withQueryString(), lakukan pencarian lalu klik halaman 2
`withQueryString()` berfungsi untuk mempertahankan parameter pada URL ketika berpindah halaman pagination.

`withQueryString()` dihapus dari pagination pada `CourseController`. Pengujian ini menunjukkan bahwa ketika pengguna melakukan pencarian kemudian berpindah ke halaman berikutnya, parameter pencarian tidak lagi dipertahankan pada URL. Akibatnya, hasil pencarian dapat hilang dan halaman berikutnya menampilkan data tanpa filter pencarian. Hal ini menunjukkan bahwa `withQueryString()` diperlukan agar parameter pencarian tetap dibawa selama proses pagination.

##

### 6. Ganti return redirect() menjadi return view() pada store, lalu tekan F5 setelah simpan
Bagian redirect pada method store()` dihapus dan diganti dengan return `view()`. Tujuannya untuk melihat apa yang terjadi ketika proses penyimpanan data langsung menampilkan halaman tanpa melakukan redirect terlebih dahulu.

Sebelumnya, setelah data berhasil disimpan, controller menggunakan:

```php
return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil ditambahkan.');
```

Setelah dirusak, controller langsung mengembalikan halaman daftar mata kuliah. Akibatnya, halaman yang ditampilkan masih merupakan hasil dari request POST.

Masalahnya muncul ketika halaman tersebut di-refresh. Browser dapat meminta pengiriman ulang form, sehingga request POST dapat dikirim kembali dan data yang sama berpotensi tersimpan lagi.

Hal ini menunjukkan pentingnya pola **PRG (Post-Redirect-Get)**, yaitu setelah proses POST berhasil, aplikasi melakukan redirect terlebih dahulu ke halaman GET. Dengan begitu, ketika pengguna melakukan refresh, browser hanya me-refresh halaman GET dan tidak mengirim ulang data form.

##

### 7. Hapus old(...) dari semua input, lalu kirim form dengan satu kesalahan
bagian `old()` pada form tambah mata kuliah dihapus. Tujuannya untuk melihat apa yang terjadi ketika pengguna mengisi form dengan data yang salah, kemudian Laravel melakukan redirect kembali ke halaman form karena validasi gagal.

Normalnya, input pada form menggunakan `old()`, contohnya:

```php
value="{{ old('code') }}"
```

`old()` berfungsi untuk mengambil kembali nilai yang sebelumnya sudah dimasukkan pengguna setelah terjadi kegagalan validasi.

Misalnya pengguna mengisi:
```
Kode: SI208
Nama: Business Intelligence
SKS: 99
```
Karena SKS hanya diperbolehkan antara 1 sampai 6, validasi akan gagal dan pengguna dikembalikan ke form.

**Ketika old() masih digunakan** data yang sebelumnya sudah diisi tetap muncul:
```
Kode: SI208
Nama: Business Intelligence
SKS: 99
```
Pengguna hanya perlu memperbaiki bagian yang salah.

**Ketika old() dihapus** Setelah validasi gagal, form kembali dalam keadaan kosong pada field yang sebelumnya menggunakan old().

Akibatnya pengguna harus mengisi kembali data dari awal, meskipun sebenarnya Laravel sudah menyimpan input sebelumnya di session untuk kebutuhan redirect.