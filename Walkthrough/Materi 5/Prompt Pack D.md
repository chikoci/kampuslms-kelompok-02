# Verifikasi Nested Route & Scoped Bindings (Minggu 5 - Prompt Pack D)

## Konteks & Rute Pengujian
Skenario rute bersarang (nested route) di `routes/web.php`:
```php
Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show']);
```

---

## 1. Jawaban Pertanyaan 1: Apakah URL `/courses/1/assignments/99` Akan Berhasil?

**JAWABAN:** **YA, SECARA DEFAULT AKAN BERHASIL (HTTP 200 OK).**

### Alasan Teknis:
Secara bawaan (*default*), Route Model Binding di Laravel memperlakukan setiap parameter model secara **independen (terpisah)** tanpa saling memeriksa relasi antar-model.

Saat request `/courses/1/assignments/99` masuk, Laravel menjalankan 2 query terpisah:
1. `Course::findOrFail(1);` ➔ Mencari mata kuliah ID 1 (Ditemukan).
2. `Assignment::findOrFail(99);` ➔ Mencari tugas ID 99 di seluruh tabel assignments (Ditemukan).

Laravel sama sekali tidak mengecek apakah kolom `course_id` pada tugas ID 99 bernilai 1 atau bukan. Akibatnya:
* Tugas ID 99 (yang sebenarnya milik mata kuliah ID 7) tetap sukses ditampilkan di bawah rute mata kuliah ID 1.
* Ini menyebabkan **anomali integritas data**: data tugas tertampil di kelas yang salah dan URL menjadi menyesatkan.

---

## 2. Jawaban Pertanyaan 2: Apa yang Dilakukan `Route::scopeBindings()`?

**JAWABAN:** 
`Route::scopeBindings()` memerintahkan Laravel untuk **mengikat model anak (child) melalui relasi Eloquent dari model induknya (parent)** secara otomatis.

### Cara Kerjanya di Balik Layar:
Alih-alih mencari tugas langsung dari tabel global (`Assignment::findOrFail(99)`), Laravel akan mencarinya lewat relasi kepemilikan parent:
```php
$course->assignments()->findOrFail(99);
```
Query SQL yang dieksekusi menjadi:
```sql
SELECT * FROM assignments WHERE id = 99 AND course_id = 1 LIMIT 1;
```

### Bagaimana Ia Mengubah Jawaban Nomor 1?
Karena tugas ID 99 memiliki `course_id = 7`, maka saat query mencari dengan syarat `course_id = 1`, database tidak menemukan baris data yang cocok.

Laravel langsung menghentikan request dan otomatis mengembalikan status:
**HTTP 404 Not Found**.

Dengan demikian, URL janggal `/courses/1/assignments/99` otomatis ditolak.

---

## 3. Penerapan di Laravel 12 & Cara Pengujian

### Cara Menerapkannya di `routes/web.php`

#### Opsi A: Pada Route Group (Direkomendasikan untuk banyak rute bersarang)
```php
Route::scopeBindings()->group(function () {
    Route::resource('courses.assignments', AssignmentController::class)->shallow();
    Route::resource('courses.materials', MaterialController::class)->shallow();
});
```

#### Opsi B: Pada Baris Route Tunggal
```php
Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])
    ->scopeBindings();
```

> **Syarat Wajib:** Model parent (`Course`) harus memiliki relasi Eloquent ke model child (`assignments`):
> ```php
> // app/Models/Course.php
> public function assignments(): HasMany
> {
>     return $this->hasMany(Assignment::class);
> }
> ```

---

### Cara Menguji Bahwa Perlindungannya Benar-Benar Bekerja

1. **Skenario Data Uji (di Database):**
   * Mata Kuliah A: `id = 1`
   * Mata Kuliah B: `id = 7`
   * Tugas X: `id = 99` (kolom `course_id = 7`)

2. **Uji Kasus Salah SEBELUM Mengaktifkan `scopeBindings()`:**
   ```bash
   curl -i http://127.0.0.1:8000/courses/1/assignments/99
   ```
   * **Hasil:** `HTTP/1.1 200 OK` (Cacat logika: tugas ID 99 bocor dan muncul di mata kuliah 1).

3. **Uji Kasus Salah SESUDAH Mengaktifkan `scopeBindings()`:**
   ```bash
   curl -i http://127.0.0.1:8000/courses/1/assignments/99
   ```
   * **Hasil:** `HTTP/1.1 404 Not Found` (Aman: server menolak karena tugas 99 bukan anak dari mata kuliah 1).

4. **Uji Kasus Benar (Pasangan Sah):**
   ```bash
   curl -i http://127.0.0.1:8000/courses/7/assignments/99
   ```
   * **Hasil:** `HTTP/1.1 200 OK` (Berhasil diakses normal karena tugas 99 memang milik mata kuliah 7).
