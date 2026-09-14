# Catatan Praktikum Minggu 3
**Nama:** Armansyah  
**NIM:** 10241013

## Bagian READ: Analisis Skema Database Sebelum Menulis Kode

### 1. Gambar Ulang ERD Lengkap dari Spesifikasi Proyek  
Di bawah ini adalah pemetaan ERD untuk 8 entitas tabel di KampusLMS. Diagram ini mencakup tipe data, Primary Key (PK), Foreign Key (FK), dan kardinalitas (hubungan antar tabel):

```mermaid
erDiagram
    users ||--o{ course_user : "terdaftar"
    users ||--o{ courses : "mengajar"
    users ||--o{ submissions : "mengumpulkan"
    users ||--o{ materials : "mengunggah"
    users ||--o{ notifications : "menerima"

    courses ||--o{ course_user : "punya peserta"
    courses ||--o{ materials : "punya"
    courses ||--o{ assignments : "punya"

    assignments ||--o{ submissions : "dikumpulkan"
    submissions ||--o| grades : "dinilai"
    users ||--o{ grades : "memberi nilai"

    users {
        bigint id PK
        string name
        string email UK
        string password
        enum role "admin|dosen|mahasiswa"
        string nim_nip UK "nullable"
        timestamp email_verified_at
        timestamps created_updated
        softdeletes deleted_at
    }

    courses {
        bigint id PK
        string code UK "contoh SI2514024"
        string name
        text description
        tinyint sks
        bigint lecturer_id FK "users.id"
        enum status "draft|active|archived"
        timestamps created_updated
    }

    course_user {
        bigint id PK
        bigint course_id FK
        bigint user_id FK
        timestamp enrolled_at
        timestamps created_updated
    }

    materials {
        bigint id PK
        bigint course_id FK
        bigint uploaded_by FK "users.id"
        string title
        text description
        enum type "file|link"
        string file_path "nullable"
        string original_name "nullable"
        unsignedbigint file_size "nullable"
        string mime_type "nullable"
        string external_url "nullable"
        timestamps created_updated
    }

    assignments {
        bigint id PK
        bigint course_id FK
        bigint created_by FK "users.id"
        string title
        text instructions
        datetime due_at
        unsignedtinyint max_score "default 100"
        boolean allow_late "default true"
        enum status "draft|published"
        timestamps created_updated
    }

    submissions {
        bigint id PK
        bigint assignment_id FK
        bigint user_id FK
        string file_path
        string original_name
        unsignedbigint file_size
        text note "nullable"
        datetime submitted_at
        boolean is_late "default false"
        timestamps created_updated
    }

    grades {
        bigint id PK
        bigint submission_id FK "unique - 1 submission 1 nilai"
        bigint graded_by FK "users.id"
        decimal score
        text feedback "nullable"
        datetime graded_at
        timestamps created_updated
    }

    notifications {
        uuid id PK
        string type
        string notifiable_type
        bigint notifiable_id
        text data
        timestamp read_at
        timestamps created_updated
    }
```

Ringkasan hubungan relasi antar entitas:
1. `users` ke `courses`: Akun dosen dihubungkan ke mata kuliah sebagai pengampu atau pengajar utama.
2. `users` ke `course_user`: Mahasiswa dicatat status pendaftarannya saat mengambil atau mengikuti suatu kelas.
3. `courses` ke `course_user`: Mata kuliah menampung daftar seluruh mahasiswa yang menjadi peserta di kelas tersebut.
4. `courses` ke `materials`: Mata kuliah menjadi wadah penyimpanan khusus untuk menampung file materi pembelajaran.
5. `users` ke `materials`: Dosen dicatat sebagai pihak yang berwenang mengunggah materi ke dalam sistem.
6. `courses` ke `assignments`: Mata kuliah memuat daftar tugas perkuliahan yang wajib dikerjakan oleh peserta.
7. `assignments` ke `submissions`: Tugas yang diterbitkan terhubung dengan seluruh lembar jawaban yang diserahkan oleh peserta.
8. `users` ke `submissions`: Mahasiswa diidentifikasi sebagai pemilik dari setiap lembar jawaban (tugas) yang dikumpulkan ke sistem.
9. `submissions` ke `grades`: Setiap lembar jawaban yang sudah terkumpul akan diproses untuk mendapatkan satu hasil evaluasi penilaian.
10. `users` ke `grades`: Dosen dicatat sebagai pihak evaluator yang memberikan skor dan umpan balik atas tugas.
11. `users` ke `notifications`: Setiap pengguna menerima pesan pemberitahuan otomatis dari sistem terkait aktivitas akademik mereka.

---

### 2. Perilaku `onDelete` pada Foreign Key dan Alasannya

| Tabel | Kolom Foreign Key | Merujuk ke Tabel | Perilaku onDelete | Alasan Pemilihan |
|---|---|---|---|---|
| `courses` | `lecturer_id` | `users.id` | `restrictOnDelete` | Akun dosen tidak boleh dihapus jika masih mengampu mata kuliah aktif agar mata kuliah tidak kehilangan pengajar (mencegah *orphan record*). |
| `course_user` | `course_id` | `courses.id` | `cascadeOnDelete` | Jika mata kuliah dihapus dari sistem, otomatis data kepesertaan mahasiswa di kelas tersebut ikut dibersihkan. |
| `course_user` | `user_id` | `users.id` | `cascadeOnDelete` | Jika akun mahasiswa dihapus dari kampus, pendaftarannya di seluruh mata kuliah otomatis ikut terhapus. |
| `materials` | `course_id` | `courses.id` | `cascadeOnDelete` | Materi melekat langsung pada mata kuliah. Jika mata kuliah dihapus, materi tidak berguna lagi dan harus dihapus agar tidak jadi file sampah. |
| `assignments` | `course_id` | `courses.id` | `cascadeOnDelete` | Tugas perkuliahan adalah bagian dari mata kuliah. Jika mata kuliah dihapus, seluruh tugas di dalamnya otomatis ikut terhapus. |
| `assignments` | `created_by` | `users.id` | `restrictOnDelete` | Dosen pembuat tugas tidak boleh dihapus sembarangan selama tugasnya masih ada demi jejak audit akademik. |
| `submissions` | `assignment_id` | `assignments.id` | `cascadeOnDelete` | Berkas pengumpulan tugas bergantung pada tugasnya. Kalau tugas dihapus, seluruh submission mahasiswa untuk tugas itu otomatis ikut terhapus. |
| `submissions` | `user_id` | `users.id` | `cascadeOnDelete` | Jika akun mahasiswa dihapus, data pengumpulan tugas miliknya ikut terhapus dari sistem. |
| `grades` | `submission_id` | `submissions.id` | `cascadeOnDelete` | Nilai menempel langsung pada lembar tugas mahasiswa. Jika lembar tugas (submission) dihapus, nilainya otomatis terhapus. |
| `grades` | `graded_by` | `users.id` | `restrictOnDelete` | Dosen penilai tidak boleh dihapus jika masih tercatat pernah memberikan nilai demi akuntabilitas riwayat akademik mahasiswa. |

---

### 3. Studi Kasus: Apa yang Terjadi Kalau Akun Dosen Dihapus?      
**Reaksi Sistem:**  
Database akan memblokir perintah delete dan melempar error (Foreign Key Constraint Violation). Intinya: dosen ini tidak bisa dihapus selama namanya masih nyangkut di tabel `courses`.

**Kenapa Dibikin Ketat Begini?**  
Ini soal integritas data. Bayangkan kalau kita pakai aturan `cascade`: saat akun dosen dihapus, seluruh mata kuliah yang dia ajar bakal ikut terhapus otomatis. Mahasiswa yang ambil kelas itu akan kaget karena kelas dan nilainya tiba-tiba hilang dari sistem.
Dengan aturan `restrict`, sistem memaksa admin untuk memindahkan kelas tersebut ke dosen pengganti terlebih dahulu. Setelah kelasnya aman, barulah akun dosen lama bisa dihapus.

---

### 4. Kenapa `submission_id` di Tabel Nilai Pakai `Unique`, Bukan Index Biasa?
**Aturan Bisnis:**  
Satu pengumpulan tugas (lembar jawaban mahasiswa) mutlak hanya boleh diberi satu buah nilai.

**Risiko Kalau Cuma Pakai Index:**  
Index biasa memang bikin query pencarian lebih cepat, tapi tidak mencegah duplikasi data. Kalau cuma pakai index, dosen yang nge-klik tombol "Simpan Nilai" dua kali (atau kalau ada bug di sistem) bisa membuat satu tugas punya dua nilai yang berbeda. Ini akan bikin perhitungan IPK jadi error.

**Solusi Paling Aman (`Unique`):**   
Dengan constraint `unique`, database sendiri yang akan jadi satpam di pintu masuk paling akhir. Meskipun kode backend kita sedang bermasalah atau terjadi klik ganda (race condition), database akan dengan tegas menolak data nilai kedua masuk ke `submission_id` yang sama.