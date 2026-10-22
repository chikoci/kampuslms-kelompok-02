# Perancangan Penyimpanan Berkas & Keamanan (Minggu 9 - Prompt Pack A)

## Konteks & Tujuan
Pada aplikasi KampusLMS (Laravel 12), terdapat tiga jenis berkas yang diunggah pengguna:
1. Foto profil pengguna
2. Materi kuliah yang diunggah dosen (PDF/PPTX)
3. Pengumpulan tugas mahasiswa (PDF/DOCX)

Dokumen ini merancang strategi penyimpanan berkas pada disk Laravel (`public` vs `private`/`local`), struktur folder, mekanisme akses, hak akses pengguna, serta analisis risiko kebocoran data jika berkas privat disimpan di folder publik.

---

## 1. Tabel Perancangan Penyimpanan Berkas

| No | Jenis Berkas | Disk Penyimpanan & Alasan | Struktur Folder | Cara Akses Pengguna | Pihak yang Berhak Mengakses |
|:---:|:---|:---|:---|:---|:---|
| 1 | **Foto profil pengguna** | **Disk `public`** (`storage/app/public`)<br>Bukan dokumen rahasia. Perlu dimuat cepat dan langsung tampil di halaman web (navbar, kartu profil) tanpa otorisasi berat. | `profile-photos/{user_id}/` | URL publik langsung di browser:<br>`/storage/profile-photos/...` | Publik (semua pengguna yang melihat profil). |
| 2 | **Materi kuliah dosen** (PDF / PPTX) | **Disk `private` / `local`** (`storage/app/private`)<br>Materi akademik berhak cipta dan hanya untuk mahasiswa yang terdaftar di kelas terkait. | `materials/{course_id}/` | Lewat Controller:<br>Route unduhan khusus yang memvalidasi hak akses via Policy (`Gate::authorize`). Dikirim via stream download. | Dosen pengampu, mahasiswa yang mengambil kelas tersebut, dan Admin. |
| 3 | **Pengumpulan tugas mahasiswa** (PDF / DOCX) | **Disk `private` / `local`** (`storage/app/private`)<br>Lembar jawaban tugas bersifat rahasia dan bernilai akademik. Rawan dicontek jika bisa diakses sembarang orang. | `submissions/{assignment_id}/` | Lewat Controller:<br>Route unduhan khusus yang dicek ketat oleh `SubmissionPolicy` sebelum file di-stream ke browser. | Mahasiswa pemilik tugas, dosen pemeriksa, dan Admin. |

---

## 2. Analisis Keamanan: Mengapa Berkas Privat Bocor Jika Ditaruh di `storage/app/public`?

Meskipun aplikasi sudah dilengkapi autentikasi login dan otorisasi Laravel Policy, menyimpan berkas privat (seperti lembar tugas atau materi terbatas) di `storage/app/public` tetap akan membocorkan data.

Penyebabnya:

1. **Bypass Siklus Request Laravel**
   Folder `storage/app/public` dihubungkan ke folder `public/storage` melalui symlink (`php artisan storage:link`). Ketika browser meminta file via URL publik (misalnya `/storage/submissions/jawaban_uas.pdf`), permintaan tersebut langsung dilayani oleh web server (Nginx/Apache/built-in PHP server) sebagai berkas statis biasa tanpa pernah menjalankan kernel Laravel.

2. **Policy dan Middleware Tidak Pernah Dipanggil**
   Karena web server langsung mengirimkan berkas tanpa melewati `index.php`, seluruh proteksi Laravel seperti middleware `auth` dan pengecekan Policy (`Gate::authorize`) dilewati begitu saja.

3. **Bisa Diakses Siapa Saja Tanpa Login**
   Siapa pun yang mengetahui atau menebak tautan berkas tersebut dapat langsung mengunduh berkas jawaban tugas mahasiswa lain secara bebas, bahkan melalui browser Incognito atau alat seperti `curl` tanpa perlu login sama sekali.

### Kesimpulan
Berkas akademik dan lembar tugas wajib disimpan di disk `private` (`storage/app/private/`). Akses unduhan hanya boleh dibuka lewat Controller yang memanggil `Storage::download()` setelah lolos verifikasi Policy.
