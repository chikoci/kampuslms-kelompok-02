# Minggu 03

**Nama:** Dawwas Eryansyah Pratama

**NIM:** 10241019

---

## 1. READ

### Analisis Skema Database

### 1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.

Berikut adalah gambaran ulang ERD KampusLMS yang mencakup 8 entitas
tabel, Primary Key (PK), Foreign Key (FK), dan hubungan antar tabel:

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


Ringkasan hubungan antar tabel

1. users ke courses: satu dosen dapat mengajar banyak mata kuliah
melalui lecturer_id.
2. users ke course_user dan courses ke course_user: relasi
many-to-many antara user/mahasiswa dan mata kuliah melalui tabel
pivot course_user.
3. courses ke materials: satu mata kuliah dapat memiliki banyak
materi melalui course_id.
4. courses ke assignments: satu mata kuliah dapat memiliki banyak
tugas melalui course_id.
5. assignments ke submissions: satu tugas dapat memiliki banyak
submission mahasiswa melalui assignment_id.
6. submissions ke grades: satu submission hanya boleh memiliki satu
nilai karena grades.submission_id bersifat unique.
7. users ke grades: satu dosen dapat memberikan banyak nilai
melalui graded_by.
8. users ke materials: satu user/dosen dapat mengunggah banyak
materi melalui uploaded_by.
9. users ke assignments: satu user/dosen dapat membuat banyak tugas
melalui created_by.
10. users ke notifications: satu user dapat menerima banyak
notifikasi melalui relasi polymorphic.

### 2. Untuk setiap foreign key, tentukan perilaku onDelete-nya dan tuliskan alasannya.

Perilaku `onDelete` yang secara eksplisit ditetapkan dalam spesifikasi
adalah:

Tabel Foreign Key Merujuk ke `onDelete` Alasan

`courses` `lecturer_id` `users.id` `RESTRICT` 

Dosen tidak
boleh dihapus jika masih digunakan sebagai pengampu mata kuliah, agar data mata kuliah tetap terjaga.

`materials` `course_id` `courses.id` `CASCADE` 

Jika mata
kuliah dihapus, materi yang dimilikinya ikut dihapus karena materi bergantung pada mata kuliah tersebut.

Untuk foreign key lain yang tidak diberi aturan `onDelete` secara eksplisit dalam spesifikasi, perilakunya tidak ditambahkan secara sembarangan agar migrasi tetap mengikuti spesifikasi.

### 3. Jawab: kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?

Jika seorang dosen masih tercatat sebagai pengampu suatu mata kuliah,
database akan menolak penghapusan dosen tersebut karena
`courses.lecturer_id` menggunakan `RESTRICT`.

Contohnya:

Dosen Budi
   |
   +-- Pemrograman Web
   |
   +-- Basis Data

Jika akun Dosen Budi ingin dihapus, database akan menolak karena masih ada data pada tabel `courses` yang menggunakan `lecturer_id` milik Dosen
Budi.

Kenapa dirancang seperti itu?

Tujuannya adalah menjaga integritas data akademik. Penghapusan seorang dosen tidak boleh menyebabkan mata kuliah yang dia ampu ikut
hilang.

Jika menggunakan `CASCADE`, penghapusan satu akun dosen dapat menyebabkan mata kuliah yang terkait ikut terhapus. Hal tersebut berisiko menghilangkan data akademik yang masih dibutuhkan.

Karena itu, jika dosen berhenti atau pindah, admin sebaiknya mengganti `lecturer_id` mata kuliah ke dosen lain terlebih dahulu. Setelah tidak lagi digunakan oleh mata kuliah, akun dosen dapat ditangani sesuai aturan pengelolaan user.

Jawaban singkat:

Jika dosen masih menjadi pengampu mata kuliah, penghapusan dosen akan
ditolak karena menggunakan RESTRICT. Tujuannya untuk menjaga data
mata kuliah dan mencegah data akademik ikut terhapus.

### 4. Jawab: kenapa grades.submission_id bersifat unique, bukan sekadar index biasa?

`grades.submission_id` dibuat UNIQUE karena aturan bisnis LMS menetapkan bahwa satu submission hanya boleh mempunyai satu nilai.

Contohnya:

Submission #101
      |
      +-- Grade 85

Tidak boleh menjadi:

Submission #101
      |
      +-- Grade 85
      +-- Grade 90
      +-- Grade 95

Jika hanya menggunakan index biasa, database memang dapat mencari submission_id dengan lebih cepat, tetapi duplikasi masih diperbolehkan.

Sedangkan dengan `UNIQUE`, database akan menolak data kedua yang menggunakan `submission_id` yang sama.

Contoh:
submission_id
101
102
103

boleh, tetapi:
submission_id
101
101  <- ditolak karena duplicate

Jadi, `UNIQUE` tidak hanya membantu pencarian, tetapi juga menjadi constraint untuk menjaga aturan bisnis di level database.

Hal ini juga membantu mencegah data nilai ganda akibat kesalahan aplikasi atau request yang masuk secara bersamaan.

Jawaban singkat:
`grades.submission_id` harus UNIQUE karena satu submission hanya boleh
memiliki satu grade. Index biasa hanya membantu pencarian dan tidak
mencegah data yang sama dimasukkan berulang kali.