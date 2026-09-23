# Uji Tembus Validasi Form Request (Minggu 4 - Prompt Pack D)

Dokumen ini berisi hasil pengujian ketahanan validasi terhadap berkas [StoreCourseRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/StoreCourseRequest.php) dari sudut pandang penyerang (security auditing), mengacu pada spesifikasi instruksi Prompt Pack D pada Modul Praktikum Minggu 4.

---

## Kode Form Request yang Diuji

```php
public function rules(): array
{
    return [
        'code'        => ['required', 'string', 'max:20', 'unique:courses,code'],
        'name'        => ['required', 'string', 'max:150'],
        'description' => ['nullable', 'string'],
        'sks'         => ['required', 'integer', 'between:1,6'],
        'lecturer_id' => ['required', 'integer', 'exists:users,id'],
        'status'      => ['required', 'in:draft,active,archived'],
    ];
}
```

---

## Persiapan Pengujian Mandiri

Sebelum menjalankan perintah cURL, ambil token CSRF aktif dan simpan cookie sesi ke berkas `cookies.txt` melalui terminal Git Bash:

```bash
curl -s -c cookies.txt http://127.0.0.1:8000/courses/create | grep '_token'
```

---

## 5 Skenario Uji Tembus Payload (Sudut Pandang Penyerang)

### 1. Payload: Menyisipkan Field Liar di Luar Formulir (Mass Assignment)
* **Tujuan Penyerang:** Menyisipkan parameter ilegal (misal `role=admin` atau `is_verified=1`) yang tidak ada di formulir antarmuka untuk membajak hak akses sistem.
* **Perintah cURL:**
  ```bash
  curl -i -s -X POST http://127.0.0.1:8000/courses \
    -H "X-CSRF-TOKEN: <token>" \
    -b cookies.txt \
    -d "code=SEC01" -d "name=Uji Field Liar" -d "sks=3" \
    -d "lecturer_id=2" -d "status=active" \
    -d "role=admin" -d "is_verified=1" | head -n 5
  ```
* **Apakah validasi menahannya?**
  * **Tidak ditahan di level rules Form Request (Status HTTP: 302 Found / Lolos Validasi):** Secara default, validator Laravel tidak menganggap keberadaan parameter tambahan sebagai kegagalan validasi.
  * **Tetapi aman di level Controller:** Karena controller menggunakan `$request->validated()`, field `role` dan `is_verified` otomatis dibuang dan tidak disertakan ke pemanggilan model `Course::create()`.
* **Aturan pencegahan:** Tetap wajib menggunakan `$request->validated()` di controller dan jangan pernah menggunakan `$request->all()`.

---

### 2. Payload: Mengirim Tipe Data Array padahal Diharapkan String (`name[]=hacked`)
* **Tujuan Penyerang:** Mengirim struktur data array bertingkat untuk memicu fatal error (PHP Type Error) atau membobol filter input teks.
* **Perintah cURL:**
  ```bash
  curl -i -s -X POST http://127.0.0.1:8000/courses \
    -H "X-CSRF-TOKEN: <token>" \
    -b cookies.txt \
    -d "code=SEC02" -d "name[]=hacked" -d "sks=3" \
    -d "lecturer_id=2" -d "status=active" | head -n 5
  ```
* **Apakah validasi menahannya?**
  * **YA, DITAHAN (Validasi Gagal, Status HTTP: 302 Found).**
  * **Aturan yang menahan:** Aturan `'string'` pada `'name' => ['required', 'string', 'max:150']`. Validator Laravel mendeteksi bahwa tipe data yang dikirim adalah array, sehingga input langsung ditolak dan dikembalikan dengan pesan error validasi.

---

### 3. Payload: Mengirim Nilai Status di Luar Enum (`status=superadmin`)
* **Tujuan Penyerang:** Memasukkan nilai status sembarang atau status dengan hak akses khusus yang berada di luar logika bisnis mata kuliah.
* **Perintah cURL:**
  ```bash
  curl -i -s -X POST http://127.0.0.1:8000/courses \
    -H "X-CSRF-TOKEN: <token>" \
    -b cookies.txt \
    -d "code=SEC03" -d "name=Uji Status Enum" -d "sks=3" \
    -d "lecturer_id=2" -d "status=superadmin" | head -n 5
  ```
* **Apakah validasi menahannya?**
  * **YA, DITAHAN (Validasi Gagal, Status HTTP: 302 Found).**
  * **Aturan yang menahan:** Aturan `'in:draft,active,archived'` pada `'status'`. Karena nilai `superadmin` tidak terdaftar pada whitelist enum, form request langsung memblokirnya di gerbang awal aplikasi sebelum menyentuh database.

---

### 4. Payload: Mengirim Foreign Key Fiktif yang Tidak Ada (`lecturer_id=99999`)
* **Tujuan Penyerang:** Mengaitkan mata kuliah dengan ID dosen yang tidak pernah ada di database, memicu integritas data rusak (data yatim / orphan record).
* **Perintah cURL:**
  ```bash
  curl -i -s -X POST http://127.0.0.1:8000/courses \
    -H "X-CSRF-TOKEN: <token>" \
    -b cookies.txt \
    -d "code=SEC04" -d "name=Uji Dosen Fiktif" -d "sks=3" \
    -d "lecturer_id=99999" -d "status=active" | head -n 5
  ```
* **Apakah validasi menahannya?**
  * **YA, DITAHAN (Validasi Gagal, Status HTTP: 302 Found).**
  * **Aturan yang menahan:** Aturan `'exists:users,id'` pada `'lecturer_id'`. Laravel menjalankan query pengecekan ke tabel `users`. Karena ID `99999` tidak ditemukan, validasi gagal dan mengembalikan pesan *"Dosen pengampu yang dipilih tidak valid atau tidak terdaftar"*.

---

### 5. Payload: Mengirim Nilai Beban SKS di Luar Batas Logika (`sks=99` atau `sks=-5`)
* **Tujuan Penyerang:** Memasukkan angka beban SKS tidak wajar, negatif, atau sangat besar untuk merusak kalkulasi akademik.
* **Perintah cURL:**
  ```bash
  curl -i -s -X POST http://127.0.0.1:8000/courses \
    -H "X-CSRF-TOKEN: <token>" \
    -b cookies.txt \
    -d "code=SEC05" -d "name=Uji SKS Abnormal" -d "sks=99" \
    -d "lecturer_id=2" -d "status=active" | head -n 5
  ```
* **Apakah validasi menahannya?**
  * **YA, DITAHAN (Validasi Gagal, Status HTTP: 302 Found).**
  * **Aturan yang menahan:** Aturan `'integer'` dan `'between:1,6'` pada field `'sks'`. Validator menolak nilai apa pun yang berada di luar rentang 1 sampai 6 dengan pesan *"Beban SKS harus antara 1 sampai 6"*.

---

## Ringkasan Matriks Pengujian Keamanan

| No | Jenis Uji Coba Payload | Payload Contoh | Ditahan Form Request? | Komponen yang Mengamankan |
|---|---|---|---|---|
| 1 | Field Liar (Mass Assignment) | `role=admin&is_verified=1` | Lolos di Rules, **Dibuang di Controller** | Controller Whitelist (`$request->validated()`) |
| 2 | Manipulasi Tipe Data | `name[]=hacked` | **YA, DITAHAN** | Aturan `'string'` pada `name` |
| 3 | Pembobolan Nilai Enum | `status=superadmin` | **YA, DITAHAN** | Aturan `'in:draft,active,archived'` pada `status` |
| 4 | Data Yatim (Orphan Foreign Key) | `lecturer_id=99999` | **YA, DITAHAN** | Aturan `'exists:users,id'` pada `lecturer_id` |
| 5 | Nilai di Luar Batas Bisnis | `sks=99` / `sks=-5` | **YA, DITAHAN** | Aturan `'between:1,6'` pada `sks` |

---

## Kesimpulan
Kombinasi antara **Form Request ketat** (`StoreCourseRequest`) dan **pemanggilan `$request->validated()` pada Controller** memberikan proteksi berlapis yang kokoh. Form Request bertugas sebagai filter gerbang pertama untuk memvalidasi tipe, rentang nilai, integritas relasi, dan format data, sedangkan whitelist pada `$request->validated()` menutup total celah Mass Assignment dari injeksi field liar.
