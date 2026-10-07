# Penyusunan Dokumentasi REST API (Minggu 6 - Prompt Pack D)

## Konteks & Prompt
```text
Berdasarkan routes/api.php dan API Resource saya, buatkan dokumentasi
API dalam format Markdown untuk berkas docs/api.md.

Untuk setiap endpoint sertakan: method, URI, peran yang berhak,
parameter, contoh request (curl), contoh response sukses, dan contoh
response gagal.

Gunakan data yang konsisten dengan seeder saya (akun demo
dosen@kampuslms.test dan seterusnya).
```

---

## 1. Ringkasan Dokumen API yang Dihasilkan

Dokumentasi resmi telah berhasil disusun pada berkas:
📄 [docs/api.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/docs/api.md)

Dokumen tersebut memuat seluruh informasi teknis yang dibutuhkan pengembang frontend maupun penguji:
* **Base URL:** `http://127.0.0.1:8000/api/v1`
* **Format Header:** `Accept: application/json` dan `Authorization: Bearer <token>`
* **Daftar Akun Demo Resmi:**
  * Admin: `admin@kampuslms.test` / `password`
  * Dosen: `dosen@kampuslms.test` / `password`
  * Mahasiswa: `mahasiswa@kampuslms.test` / `password`

---

## 2. Cakupan 16 Endpoint pada Dokumentasi

Dokumentasi mencakup 5 modul utama:
1. **Modul Autentikasi:**
   * `POST /auth/login` (publik, throttle 5/menit)
   * `POST /auth/logout` (mencabut token)
   * `GET /me` (profil akun login)
2. **Modul Mata Kuliah:**
   * `GET /courses` (scoping peran otomatis, format koleksi dengan `meta`)
   * `GET /courses/{id}` (detail MK + hitungan agregat materi, tugas, dan mahasiswa)
   * `GET /courses/{id}/materials` (daftar materi)
   * `GET /courses/{id}/assignments` (daftar tugas dengan filter status dan pagination)
3. **Modul Tugas Kuliah:**
   * `POST /assignments` (dosen pengampu, 201 Created)
   * `PUT/PATCH /assignments/{id}` (dosen pengampu, 200 OK)
   * `DELETE /assignments/{id}` (dosen pengampu, 204 No Content)
   * `GET /assignments/{id}/submissions` (dosen pengampu, melihat pengumpulan kelas)
   * `POST /assignments/{id}/submissions` (mahasiswa terdaftar, unggah berkas multipart, 201 Created)
4. **Modul Penilaian:**
   * `PUT /submissions/{id}/grade` (dosen pengampu, upsert nilai: 201 baru, 200 update)
5. **Modul Notifikasi:**
   * `GET /notifications` (daftar notifikasi akun)
   * `POST /notifications/{id}/read` (menandai telah dibaca)

Setiap endpoint dilengkapi contoh nyata perintah `curl`, payload request, response sukses berformat JSON, dan response error (401 Unauthorized, 403 Forbidden, 422 Validasi).

---

## 3. Status Pemenuhan BUILD
Dengan tersusunnya `docs/api.md`, maka **Poin BUILD 6** resmi tuntas 100%.
