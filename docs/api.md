# Dokumentasi REST API KampusLMS (v1)

Dokumentasi resmi arsitektur REST API KampusLMS berbasis Laravel 12 dan Laravel Sanctum, sesuai kontrak Bagian 5 Spesifikasi Proyek.

* **Base URL:** `http://127.0.0.1:8000/api/v1`
* **Format Data:** `application/json` (Kecuali unggah berkas multipart/form-data)
* **Autentikasi:** Laravel Sanctum Bearer Token (`Authorization: Bearer <token>`)
* **Rate Limiting:**
  * Endpoint Login: 5 permintaan per menit (`throttle:5,1`)
  * Endpoint Terautentikasi: 60 permintaan per menit (`throttle:60,1`)

---

## Akun Uji Coba Demo (Seeder)

| Peran | Alamat Email | Kata Sandi | Deskripsi |
|---|---|---|---|
| **Admin** | `admin@kampuslms.test` | `password` | Administrator sistem |
| **Dosen** | `dosen@kampuslms.test` | `password` | Dosen pengampu mata kuliah SI201 dan SI204 |
| **Mahasiswa** | `mahasiswa@kampuslms.test` | `password` | Mahasiswa aktif peserta perkuliahan |

---

## 1. Modul Autentikasi

### 1.1 POST `/auth/login`
Autentikasi akun dan penerbitan Sanctum Bearer Token.

* **Akses:** Publik (Rate limit: 5/menit)
* **Headers:**
  * `Accept: application/json`
  * `Content-Type: application/json`
* **Request Body:**
  ```json
  {
    "email": "dosen@kampuslms.test",
    "password": "password",
    "device_name": "testing-client"
  }
  ```
* **Contoh cURL:**
  ```bash
  curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"dosen@kampuslms.test","password":"password"}'
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "token": "1|abcdef1234567890...",
    "user": {
      "id": 2,
      "name": "Dr. Dosen Pengampu, M.Kom.",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "198501012010121001",
      "created_at": "2026-09-17T05:35:44+00:00"
    }
  }
  ```
* **Response Gagal (422 Unprocessable Content - Kredensial Salah):**
  ```json
  {
    "message": "Email atau kata sandi salah.",
    "errors": {
      "email": [
        "Email atau kata sandi salah."
      ]
    }
  }
  ```

---

### 1.2 POST `/auth/logout`
Mencabut token autentikasi yang sedang digunakan saat ini.

* **Akses:** Terautentikasi (Semua Peran)
* **Headers:**
  * `Accept: application/json`
  * `Authorization: Bearer <token>`
* **Contoh cURL:**
  ```bash
  curl -X POST http://127.0.0.1:8000/api/v1/auth/logout \
    -H "Accept: application/json" \
    -H "Authorization: Bearer 1|abcdef1234567890..."
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "message": "Berhasil keluar."
  }
  ```
* **Response Gagal (401 Unauthorized - Tanpa Token):**
  ```json
  {
    "message": "Unauthenticated."
  }
  ```

---

### 1.3 GET `/me`
Mengambil data profil pengguna yang sedang login.

* **Akses:** Terautentikasi (Semua Peran)
* **Headers:**
  * `Accept: application/json`
  * `Authorization: Bearer <token>`
* **Contoh cURL:**
  ```bash
  curl http://127.0.0.1:8000/api/v1/me \
    -H "Accept: application/json" \
    -H "Authorization: Bearer 1|abcdef1234567890..."
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "id": 2,
      "name": "Dr. Dosen Pengampu, M.Kom.",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "198501012010121001",
      "created_at": "2026-09-17T05:35:44+00:00"
    }
  }
  ```

---

## 2. Modul Mata Kuliah (Courses)

### 2.1 GET `/courses`
Menampilkan daftar mata kuliah dengan scoping peran otomatis (Dosen: MK ajarannya; Mahasiswa: MK yang diikutinya; Admin: semua MK).

* **Akses:** Terautentikasi (Semua Peran)
* **Query Parameters:**
  * `page`: integer (opsional, nomor halaman pagination)
* **Contoh cURL:**
  ```bash
  curl http://127.0.0.1:8000/api/v1/courses \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token>"
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "code": "SI201",
        "name": "Pemrograman Web",
        "description": "Mata kuliah pengembangan web modern menggunakan Laravel.",
        "sks": 3,
        "status": "active",
        "lecturer": {
          "id": 2,
          "name": "Dr. Dosen Pengampu, M.Kom.",
          "email": "dosen@kampuslms.test",
          "role": "dosen"
        },
        "counts": {
          "materials": 3,
          "assignments": 5,
          "students": 23
        },
        "created_at": "2026-09-17T05:35:44+00:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "total": 1
    }
  }
  ```

---

### 2.2 GET `/courses/{id}`
Menampilkan rincian satu mata kuliah beserta counter materi, tugas, dan mahasiswa.

* **Akses:** Terautentikasi + Scope (Dosen pengampu, Mahasiswa terdaftar, atau Admin)
* **Contoh cURL:**
  ```bash
  curl http://127.0.0.1:8000/api/v1/courses/1 \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token>"
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "id": 1,
      "code": "SI201",
      "name": "Pemrograman Web",
      "description": "Mata kuliah pengembangan web modern.",
      "sks": 3,
      "status": "active",
      "lecturer": {
        "id": 2,
        "name": "Dr. Dosen Pengampu, M.Kom.",
        "email": "dosen@kampuslms.test",
        "role": "dosen"
      },
      "counts": {
        "materials": 3,
        "assignments": 5,
        "students": 23
      },
      "created_at": "2026-09-17T05:35:44+00:00"
    }
  }
  ```
* **Response Gagal (403 Forbidden - Bukan Pengampu / Tidak Terdaftar):**
  ```json
  {
    "message": "Anda tidak memiliki akses ke sumber daya ini."
  }
  ```

---

### 2.3 GET `/courses/{id}/materials`
Menampilkan daftar materi pada mata kuliah tertentu.

* **Akses:** Terautentikasi + Scope
* **Contoh cURL:**
  ```bash
  curl http://127.0.0.1:8000/api/v1/courses/1/materials \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token>"
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "course_id": 1,
        "title": "Materi 01: Pengenalan Web & HTTP",
        "type": "pdf",
        "file_size": 204800,
        "uploader": {
          "id": 2,
          "name": "Dr. Dosen Pengampu, M.Kom."
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "total": 1
    }
  }
  ```

---

### 2.4 GET `/courses/{id}/assignments`
Menampilkan daftar tugas pada mata kuliah tertentu dengan dukungan filter status dan pagination.

* **Akses:** Terautentikasi + Scope
* **Query Parameters:**
  * `status`: string (opsional: `draft`, `published`, `closed`)
  * `page`: integer (opsional)
* **Contoh cURL:**
  ```bash
  curl "http://127.0.0.1:8000/api/v1/courses/1/assignments?status=published" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token>"
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "course_id": 1,
        "title": "Tugas 01: Desain Basis Data",
        "due_at": "2026-10-15T23:59:00+00:00",
        "max_score": 100,
        "allow_late": true,
        "status": "published",
        "submissions_count": 18
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "total": 1
    }
  }
  ```

---

## 3. Modul Tugas Kuliah (Assignments)

### 3.1 POST `/assignments`
Membuat tugas kuliah baru.

* **Akses:** Dosen (Pengampu mata kuliah bersangkutan) atau Admin
* **Request Body:**
  ```json
  {
    "course_id": 1,
    "title": "Tugas API Sanctum",
    "instructions": "Implementasikan REST API sesuai Bagian 5.",
    "due_at": "2026-10-30 23:59:00",
    "max_score": 100,
    "allow_late": true,
    "status": "published"
  }
  ```
* **Contoh cURL:**
  ```bash
  curl -X POST http://127.0.0.1:8000/api/v1/assignments \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <token_dosen>" \
    -d '{"course_id":1,"title":"Tugas API Sanctum","instructions":"Kerjakan sesuai modul.","due_at":"2026-10-30 23:59:00","max_score":100,"status":"published"}'
  ```
* **Response Sukses (201 Created):**
  ```json
  {
    "data": {
      "id": 6,
      "course_id": 1,
      "title": "Tugas API Sanctum",
      "instructions": "Kerjakan sesuai modul.",
      "due_at": "2026-10-30T23:59:00+00:00",
      "max_score": 100,
      "allow_late": false,
      "status": "published",
      "created_at": "2026-10-06T12:35:00+00:00"
    }
  }
  ```
* **Response Gagal (403 Forbidden - Mahasiswa Ditolak):**
  ```json
  {
    "message": "Anda tidak memiliki akses ke sumber daya ini."
  }
  ```

---

### 3.2 PUT/PATCH `/assignments/{id}`
Memperbarui informasi tugas kuliah.

* **Akses:** Dosen Pemilik atau Admin
* **Contoh cURL:**
  ```bash
  curl -X PUT http://127.0.0.1:8000/api/v1/assignments/6 \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <token_dosen>" \
    -d '{"title":"Tugas API Sanctum (Revisi Deadline)","due_at":"2026-11-05 23:59:00"}'
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "id": 6,
      "title": "Tugas API Sanctum (Revisi Deadline)",
      "due_at": "2026-11-05T23:59:00+00:00"
    }
  }
  ```

---

### 3.3 DELETE `/assignments/{id}`
Menghapus tugas kuliah.

* **Akses:** Dosen Pemilik atau Admin
* **Contoh cURL:**
  ```bash
  curl -X DELETE http://127.0.0.1:8000/api/v1/assignments/6 \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token_dosen>"
  ```
* **Response Sukses (204 No Content):** Tidak mengembalikan body.

---

### 3.4 GET `/assignments/{id}/submissions`
Melihat daftar seluruh pengumpulan tugas dari mahasiswa.

* **Akses:** Dosen Pemilik atau Admin
* **Contoh cURL:**
  ```bash
  curl http://127.0.0.1:8000/api/v1/assignments/1/submissions \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token_dosen>"
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "assignment_id": 1,
        "original_name": "tugas1.zip",
        "file_size": 1048576,
        "is_late": false,
        "submitted_at": "2026-09-20T10:00:00+00:00",
        "student": {
          "id": 3,
          "name": "Mahasiswa Uji Coba",
          "email": "mahasiswa@kampuslms.test"
        },
        "grade": {
          "score": 95,
          "feedback": "Bagus sekali."
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "total": 1
    }
  }
  ```

---

### 3.5 POST `/assignments/{id}/submissions`
Mengunggah file pengumpulan tugas oleh mahasiswa terdaftar.

* **Akses:** Mahasiswa Terdaftar
* **Headers:**
  * `Accept: application/json`
  * `Authorization: Bearer <token_mahasiswa>`
* **Content-Type:** `multipart/form-data`
* **Form Data:**
  * `file`: file (wajib, maks: 10MB)
  * `note`: string (opsional)
* **Contoh cURL:**
  ```bash
  curl -X POST http://127.0.0.1:8000/api/v1/assignments/1/submissions \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token_mahasiswa>" \
    -F "file=@/path/to/berkas.pdf" \
    -F "note=Pengumpulan tepat waktu."
  ```
* **Response Sukses (201 Created):**
  ```json
  {
    "data": {
      "id": 25,
      "assignment_id": 1,
      "original_name": "berkas.pdf",
      "file_size": 245760,
      "is_late": false,
      "submitted_at": "2026-10-06T12:40:00+00:00"
    }
  }
  ```

---

## 4. Modul Penilaian Tugas (Grades)

### 4.1 PUT `/submissions/{id}/grade`
Memberikan atau memperbarui nilai tugas mahasiswa menggunakan mekanisme *upsert* (`updateOrCreate`).

* **Akses:** Dosen Pemilik atau Admin
* **Request Body:**
  ```json
  {
    "score": 95.5,
    "feedback": "Hasil pekerjaan sangat rapi dan memenuhi semua kriteria."
  }
  ```
* **Contoh cURL:**
  ```bash
  curl -X PUT http://127.0.0.1:8000/api/v1/submissions/1/grade \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <token_dosen>" \
    -d '{"score":95.5,"feedback":"Kerja bagus!"}'
  ```
* **Response Sukses Pertama Kali Dibuat (201 Created):**
  ```json
  {
    "data": {
      "id": 10,
      "submission_id": 1,
      "score": 95.5,
      "feedback": "Kerja bagus!",
      "graded_at": "2026-10-06T12:42:00+00:00",
      "grader": {
        "id": 2,
        "name": "Dr. Dosen Pengampu, M.Kom."
      }
    }
  }
  ```
* **Response Sukses Saat Nilai Diperbarui (200 OK):** Mengembalikan data nilai terbaru dengan status HTTP 200.
* **Response Gagal (422 Unprocessable Content - Nilai Tidak Valid):**
  ```json
  {
    "message": "Data yang diberikan tidak valid.",
    "errors": {
      "score": [
        "The score field must be between 0 and 100."
      ]
    }
  }
  ```

---

## 5. Modul Notifikasi

### 5.1 GET `/notifications`
Mengambil daftar notifikasi akun pengguna yang sedang login.

* **Akses:** Terautentikasi (Semua Peran)
* **Contoh cURL:**
  ```bash
  curl http://127.0.0.1:8000/api/v1/notifications \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token>"
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": "9d7e35b1-1234-4567-890a-bcdef1234567",
        "type": "App\\Notifications\\NewAssignmentNotification",
        "data": "Tugas baru telah dipublikasikan.",
        "read_at": null,
        "created_at": "2026-10-06T10:00:00+00:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "total": 1
    }
  }
  ```

---

### 5.2 POST `/notifications/{id}/read`
Menandai notifikasi sebagai telah dibaca.

* **Akses:** Terautentikasi (Pemilik Notifikasi)
* **Contoh cURL:**
  ```bash
  curl -X POST http://127.0.0.1:8000/api/v1/notifications/9d7e35b1-1234-4567-890a-bcdef1234567/read \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <token>"
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "message": "Notifikasi telah ditandai sebagai sudah dibaca.",
    "data": {
      "id": "9d7e35b1-1234-4567-890a-bcdef1234567",
      "read_at": "2026-10-06T12:45:00+00:00"
    }
  }
  ```
