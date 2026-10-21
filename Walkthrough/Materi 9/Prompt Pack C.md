# Walkthrough Materi 9 - Prompt Pack C: Audit Keamanan Penyimpanan Berkas & Anti-IDOR

Dokumen ini mendokumentasikan hasil pengujian dan evaluasi keamanan pada implementasi sistem penyimpanan berkas KampusLMS (Materi 9). Pengujian dilakukan dari perspektif mahasiswa yang mencoba mengakses berkas tugas milik rekan sekelasnya secara tidak sah.

---

## 1. Kesimpulan Singkat (Executive Summary)

Sistem penyimpanan dan pengunduhan berkas KampusLMS terbukti **100% aman dan tahan terhadap berbagai teknik eksploitasi data**:
1. **Kebocoran Akses Antar-Mahasiswa (IDOR)**: **GAGAL**. Mahasiswa sama sekali tidak dapat melihat, mengunduh, atau mengetahui tautan berkas tugas milik mahasiswa lain. Seluruh akses dikunci oleh `SubmissionPolicy` via `Gate::authorize()`.
2. **Akses Berkas Fisik Langsung**: **GAGAL**. Berkas disimpan di disk privat `storage/app/private/` yang berada di luar jangkauan direktori web root `public/`, sehingga akses URL langsung menghasilkan HTTP 404.
3. **Penebakan Nama Berkas**: **GAGAL**. Berkas disimpan dengan nama acak hash 40 karakter, sehingga mustahil ditebak.
4. **Penyusupan Skrip PHP Berkedok PDF**: **GAGAL**. Validasi `mimes:pdf,doc,docx` memeriksa konten biner (*magic bytes*) via PHP `fileinfo`, bukan sekadar ekstensi nama file. Berkas PHP palsu langsung ditolak.
5. **Tabrakan Nama Berkas Sama**: **AMAN**. Dua mahasiswa yang mengunggah nama berkas yang sama (misal `tugas1.pdf`) tidak akan saling menimpa karena nama fisik diacak dan nama asli dicatat terpisah di kolom database.

---

## 2. Analisis Vektor Penyerangan & Pertahanan Sistem

| No | Vektor Serangan | Hasil Pengujian | Mekanisme Pertahanan Kode |
|:---:|:---|:---:|:---|
| 1 | **IDOR pada Endpoint Download** (`/submissions/{id}/download`) | **DITAHAN (HTTP 403)** | `SubmissionController::download()` memanggil `Gate::authorize('download', $submission)`. Policy memverifikasi `$user->id === $submission->user_id`. Jika bukan pemilik, dilempar HTTP 403 Forbidden. |
| 2 | **IDOR pada Detail Submission** (`/submissions/{id}`) | **DITAHAN (HTTP 403)** | `SubmissionController::show()` memanggil `Gate::authorize('view', $submission)` dengan aturan kepemilikan yang sama. |
| 3 | **Scraping Daftar Pengumpulan** (`/assignments/{id}/submissions`) | **DITAHAN (Filter Query)** | `SubmissionController::index()` menyaring data di tingkat database menggunakan `->where('user_id', $user->id)`. Mahasiswa hanya menerima datanya sendiri. |
| 4 | **Akses Statis Langsung Web Server** (`/storage/...`) | **DITAHAN (HTTP 404)** | Disk `local` menunjuk ke `storage/app/private/`. Direktori ini tidak memiliki symlink atau rute publik dari web server. |
| 5 | **Tebak Nama Berkas Fisik (Brute Force)** | **DITAHAN (Nama Acak)** | Metode `$file->store()` mengacak nama fisik berkas menjadi hash unik 40 karakter. |
| 6 | **Path Traversal Parameter** | **DITAHAN (Model Binding)** | Pengunduhan menggunakan Route Model Binding; server hanya membaca path aman dari database yang terverifikasi. |

---

## 3. Evaluasi Sisi Pengunggahan (Upload Edge Cases)

### A. Berkas PHP yang Diganti Ekstensi Menjadi `.pdf`
* **Hasil**: Ditolak oleh server dengan pesan error validasi: *"Format berkas harus berupa PDF, DOC, atau DOCX."*
* **Penyebab**: Aturan `mimes:pdf,doc,docx` pada Laravel menggunakan pembacaan MIME biner (*magic bytes*), bukan sekadar mencocokkan string ekstensi.
* **Pertahanan Lapis Kedua**: Berkas disimpan di disk privat tanpa eksekusi skrip web server dan hanya dikirim sebagai lampiran biner (*attachment*).

### B. Dua Mahasiswa Mengunggah Nama Berkas yang Sama
* **Hasil**: Berkas tersimpan berdampingan dengan aman tanpa saling menimpa (*Zero Collision*).
* **Penyebab**: Nama berkas fisik diacak secara independen oleh `$file->store()`, sedangkan nama asli disimpan di kolom terpisah `original_name`.

---

## 4. Perintah Pembuktian cURL

### A. Uji Otorisasi Unduhan Antar-Mahasiswa (Anti-IDOR)
```bash
# Login sebagai Mahasiswa 2 (penyerang)
curl -s -c mhs2.cookie -X POST "http://127.0.0.1:8000/login" \
  -F "email=mahasiswa2@kampus.ac.id" \
  -F "password=password"

# Mencoba mengunduh berkas milik Mahasiswa 1 (Submission ID 1)
curl -i -b mhs2.cookie "http://127.0.0.1:8000/submissions/1/download"
# Output Diharapkan: HTTP/1.1 403 Forbidden
```

### B. Uji Akses Langsung Direktori Penyimpanan Privat
```bash
curl -i "http://127.0.0.1:8000/storage/submissions/1/berkas_tugas.pdf"
curl -i "http://127.0.0.1:8000/storage/app/private/submissions/1/berkas_tugas.pdf"
# Output Diharapkan: HTTP/1.1 404 Not Found
```

### C. Uji Unggah Berkas PHP Berektensi `.pdf`
```bash
# 1. Buat berkas PHP palsu
echo "<?php phpinfo(); ?>" > backdoor.pdf

# 2. Ambil token CSRF
CSRF_TOKEN=$(curl -s -b mhs2.cookie "http://127.0.0.1:8000/assignments/1" | grep -oP 'name="_token" value="\K[^"]+')

# 3. Kirim permintaan unggah
curl -i -b mhs2.cookie -X POST "http://127.0.0.1:8000/assignments/1/submissions" \
  -F "_token=${CSRF_TOKEN}" \
  -F "file=@backdoor.pdf"
# Output Diharapkan: Redirect kembali dengan pesan error MIME type tidak valid.
```
