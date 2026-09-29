# Minggu 04

**Nama:** Dawwas Eryansyah Pratama

**NIM:** 10241019

---

## 1. READ

### Analisis Siklus Form Gagal pada Modul Mata Kuliah

### 1. Method apa yang menerima request? Di controller mana?

Request dikirim menggunakan method POST melalui form tambah mata kuliah dengan route:
`route('courses.store')`

Request tersebut diterima oleh method store() pada:
`CourseController`

Jadi alurnya:
```
Form Tambah Mata Kuliah
        ↓
POST /mata-kuliah
        ↓
CourseController@store()
        ↓
Validasi data
        ↓
Simpan ke database
```
Method POST digunakan karena form tersebut mengirimkan data baru untuk disimpan ke database.

### 2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?

Pada kode yang digunakan, validasi dilakukan di dalam method store() melalui:
```
$validated = $request->validate([
    'code' => 'required|string|max:20|unique:courses,code',
    'name' => 'required|string|max:255',
    'description' => 'nullable|string',
    'sks' => 'required|integer|min:1|max:6',
    'lecturer_id' => 'required|exists:users,id',
    'status' => 'required|in:draft,active,archived',
]);
```

Validasi terjadi di awal proses store(), sebelum data dijalankan ke:
`Course::create($validated);`

Misalnya SKS diisi 99, maka aturan:
`'min:1|max:6'`

tidak terpenuhi. Laravel akan menganggap data tidak valid sehingga proses penyimpanan tidak dilanjutkan.

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?

Ketika validasi gagal, Laravel secara otomatis mengembalikan pengguna ke halaman sebelumnya, yaitu halaman form tambah mata kuliah.

Dalam kasus ini kira-kira kembali ke:
`/mata-kuliah/create`

Laravel juga membawa informasi error dan input sebelumnya agar dapat ditampilkan kembali pada form.

Jadi alurnya:
```
POST Form
   ↓
Validasi gagal
   ↓
Redirect kembali
   ↓
/mata-kuliah/create
   ↓
Error ditampilkan pada form
```
Mekanisme redirect dan pengiriman informasi validasi tersebut ditangani oleh Laravel.

### 4. Dari mana `@error('sks')` mengambil pesannya?

`@error('sks')` mengambil pesan dari error bag yang dibuat Laravel ketika proses validasi gagal.

Contohnya:
```
@error('sks')
    <p class="text-red-600">{{ $message }}</p>
@enderror
```

Jika SKS diisi `99`, Laravel mengetahui bahwa nilai tersebut melewati batas maksimal 6, kemudian memasukkan pesan kesalahan untuk field `sks` ke dalam error bag.

`@error('sks')` kemudian mengecek apakah ada error untuk field tersebut. Jika ada, $message berisi pesan error yang bisa ditampilkan kepada pengguna.

### 5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?

`old('sks')` mengambil nilai input dari request sebelumnya yang gagal divalidasi.

Misalnya pengguna mengisi:
`SKS = 99`

Kemudian validasi gagal. Laravel menyimpan input tersebut sementara sehingga ketika form ditampilkan kembali, kita dapat menggunakan: 
`<input type="number" name="sks" value="{{ old('sks') }}">`

Hasilnya, nilai `99` tetap muncul di form sehingga pengguna tidak perlu mengisi ulang dari awal.

Nilai tersebut bersifat sementara, terutama digunakan untuk request setelah validasi gagal.

### 6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.

Cookie session dapat dilihat melalui Inspect Element → tab Application → Storage → Cookies → http://127.0.0.1:8000 .

Cookie tersebut digunakan untuk membantu Laravel mengenali session pengguna antar-request. Nama cookie perlu dicatat berdasarkan hasil yang terlihat pada browser, karena tidak sebaiknya ditebak.

Contohnya, pada bagian Cookies akan terdapat cookie yang digunakan aplikasi Laravel untuk session.

cookie session yang ditemukan bernama:

- `laravel_session`: Cookie yang digunakan Laravel untuk menyimpan ID sesi pengguna secara terenkripsi, sehingga server dapat mengenali session pengguna antar-request atau ketika berpindah halaman. Contoh nilai yang terlihat saat diuji di DevTools adalah `eyJpdi....`
- `XSRF-TOKEN (tambahan)`: Cookie yang menyimpan token CSRF untuk membantu melindungi aplikasi dari request yang tidak sah, terutama pada form yang menggunakan method POST.

## 2. BREAK


### 1. Hapus @csrf dari Form
Hasil percobaan sesuai dengan prediksi, yaitu muncul Error 419 (Page Expired) setelah @csrf dihapus dari form. Error terjadi karena request POST tidak lagi membawa token CSRF yang dibutuhkan Laravel untuk memverifikasi bahwa request berasal dari form yang sah. Akibatnya, Laravel menolak request tersebut sebelum proses penyimpanan data dilakukan. Percobaan ini menunjukkan bahwa @csrf penting untuk melindungi form dari request yang tidak sah atau serangan CSRF.

### 6.
Percobaan menunjukkan bahwa penggunaan return view() membuat browser tetap berada pada hasil request POST. Hal ini menyebabkan ketika halaman di-refresh, browser meminta konfirmasi untuk mengirim ulang data formulir. Data tidak menjadi ganda pada percobaan ini karena validasi unique berhasil mencegah kode mata kuliah yang sama disimpan kembali. Namun, kondisi ini tetap menunjukkan bahwa POST langsung ke view dapat menyebabkan pengiriman ulang request, sehingga pola PRG digunakan untuk mengarahkan pengguna ke request GET setelah data berhasil disimpan. 