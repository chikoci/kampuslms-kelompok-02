# Perancangan Matriks Otorisasi & Laravel Policies (Minggu 7 - Prompt Pack A)

## Konteks & Tujuan
Pada arsitektur KampusLMS (Laravel 12), otorisasi kasar (Coarse-Grained Authorization) berbasis role middleware (`role:admin`, `role:dosen`, dsb.) tidak cukup untuk melindungi keamanan data.
Diperlukan **Fine-Grained Authorization (Otorisasi Tingkat Objek)** menggunakan **Laravel Policies** guna mencegah serangan IDOR (Insecure Direct Object Reference) dan manipulasi hak akses antar pengguna.

---

## 1. Matriks Hak Akses KampusLMS

| Entitas / Sumber Daya | Aksi | Admin | Dosen | Mahasiswa | Tamu (Guest) | Mekanisme Otorisasi |
|---|---|---|---|---|---|---|
| **Dashboard** | View | Ya (Semua) | Ya (MK Ampuan) | Ya (MK Diikuti) | Tidak (302 Login) | Middleware `auth` & view role |
| **Mata Kuliah** | Index | Ya (Semua) | Ya (Scoping Ampuan) | Ya (Scoping Diikuti) | Tidak (302 Login) | `CoursePolicy@viewAny` & Query Scoping |
| | Create / Store | Ya | Tidak (403) | Tidak (403) | Tidak (302 Login) | `CoursePolicy@create` |
| | View / Show | Ya | Ya (Jika Pengampu) | Ya (Jika Terdaftar) | Tidak (302 Login) | `CoursePolicy@view` |
| | Edit / Update | Ya | Ya (Hanya MK Sendiri) | Tidak (403) | Tidak (302 Login) | `CoursePolicy@update` |
| | Delete | Ya | Tidak (403) | Tidak (403) | Tidak (302 Login) | `CoursePolicy@delete` |
| **Materi Perkuliahan** | Create / Store | Ya | Ya (MK Sendiri) | Tidak (403) | Tidak (302 Login) | `MaterialPolicy@create` |
| | Download / View | Ya | Ya (Pengampu) | Ya (Jika Terdaftar MK) | Tidak (302 Login) | `MaterialPolicy@view` |
| | Update / Delete | Ya | Ya (MK Sendiri) | Tidak (403) | Tidak (302 Login) | `MaterialPolicy@update`, `delete` |
| **Tugas Kuliah** | Create / Store | Ya | Ya (MK Sendiri) | Tidak (403) | Tidak (302 Login) | `AssignmentPolicy@create` |
| | View Detail | Ya | Ya (Pengampu) | Ya (Jika Terdaftar MK) | Tidak (302 Login) | `AssignmentPolicy@view` |
| | Update / Delete | Ya | Ya (MK Sendiri) | Tidak (403) | Tidak (302 Login) | `AssignmentPolicy@update`, `delete` |
| **Pengumpulan (Submission)** | Store (Upload) | Tidak (403) | Tidak (403) | Ya (Jika Terdaftar & Tepat Waktu) | Tidak (302 Login) | `SubmissionPolicy@create` |
| | View / Download | Ya (Audit) | Ya (Pengampu MK) | Ya (Hanya Milik Sendiri) | Tidak (302 Login) | `SubmissionPolicy@view` (**Anti-IDOR**) |
| **Penilaian (Grade)** | Create / Update | Tidak (403) | Ya (Pengampu MK) | Tidak (403) | Tidak (302 Login) | `GradePolicy@grade` |
| **Pengguna (User)** | Index & Create | Ya | Tidak (403) | Tidak (403) | Tidak (302 Login) | `UserPolicy@viewAny`, `create` |
| | Update Profil | Ya | Ya (Hanya Diri Sendiri) | Ya (Hanya Diri Sendiri) | Tidak (302 Login) | `UserPolicy@update` (**Anti-Escalation**) |

---

## 2. Struktur Laravel Policies yang Diterapkan

### A. CoursePolicy (`app/Policies/CoursePolicy.php`)
```php
public function update(User $user, Course $course): bool
{
    // Admin boleh mengedit seluruh mata kuliah
    if ($user->role === 'admin') {
        return true;
    }
    // Dosen hanya boleh mengedit mata kuliah miliknya sendiri
    return $user->role === 'dosen' && $course->lecturer_id === $user->id;
}
```

### B. SubmissionPolicy (`app/Policies/SubmissionPolicy.php`)
```php
public function view(User $user, Submission $submission): bool
{
    if ($user->role === 'admin') {
        return true;
    }
    if ($user->role === 'dosen') {
        return $submission->assignment->course->lecturer_id === $user->id;
    }
    if ($user->role === 'mahasiswa') {
        // Mencegah mahasiswa mengintip tugas mahasiswa lain (IDOR)
        return $submission->user_id === $user->id;
    }
    return false;
}
```

### C. GradePolicy (`app/Policies/GradePolicy.php`)
```php
public function grade(User $user, Submission $submission): bool
{
    // Hanya dosen pengampu mata kuliah terkait yang boleh memberikan nilai
    return $user->role === 'dosen' 
        && $submission->assignment->course->lecturer_id === $user->id;
}
```

### D. UserPolicy (`app/Policies/UserPolicy.php`)
```php
public function viewAny(User $user): bool
{
    return $user->role === 'admin';
}

public function update(User $user, User $target): bool
{
    // Admin bebas mengedit, user reguler hanya dapat mengedit profil diri sendiri
    return $user->role === 'admin' || $user->id === $target->id;
}
```
