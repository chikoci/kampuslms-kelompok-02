# Audit IDOR & Mitigasi Privilege Escalation (Minggu 7 - Prompt Pack B)

## 1. Tabel Titik Rawan IDOR & Pertahanannya

Dokumentasi resmi ini disajikan juga di `docs/keamanan.md` dan siap diuji langsung oleh dosen penguji saat sesi Uji Keamanan:

| No | Endpoint / URL | Parameter Kritis | Peran Penyerang | Dampak Bila Rentan | Mekanisme Pertahanan Server-Side | Kode HTTP Respon |
|---|---|---|---|---|---|---|
| 1 | `GET /submissions/{id}` | `{id}` (Submission) | Mahasiswa | Mengintip file tugas & jawaban mahasiswa lain | `Gate::authorize('view', $submission)` di `SubmissionController@show` | 403 Forbidden |
| 2 | `GET /submissions/{id}/download` | `{id}` (Submission) | Mahasiswa | Mengunduh berkas arsip milik mahasiswa lain | `Gate::authorize('view', $submission)` sebelum streaming file | 403 Forbidden |
| 3 | `POST /submissions/{id}/grades` | `{id}` (Submission) | Dosen lain / Mahasiswa | Memberikan atau memanipulasi nilai tugas ilegal | `Gate::authorize('grade', $submission)` di `SubmissionController@grade` | 403 Forbidden |
| 4 | `GET /courses/{id}` | `{id}` (Course) | Mahasiswa tak terdaftar | Melihat materi privat mata kuliah yang tidak diambil | `CoursePolicy@view` memverifikasi relasi pivot `course_user` | 403 Forbidden |
| 5 | `GET /courses/{id}/edit` | `{id}` (Course) | Dosen lain | Mengubah kurikulum / deskripsi MK dosen lain | `CoursePolicy@update` memvalidasi `$course->lecturer_id === $user->id` | 403 Forbidden |
| 6 | `PUT /courses/{id}` | `{id}` (Course) | Mahasiswa / Dosen lain | Menimpa data mata kuliah via API/POST request | `CoursePolicy@update` dieksekusi di controller sebelum `$course->update()` | 403 Forbidden |
| 7 | `POST /courses/{id}/materials` | `{id}` (Course) | Dosen lain / Mahasiswa | Mengunggah materi palsu ke mata kuliah orang lain | `MaterialPolicy@create` memverifikasi kepemilikan MK induk | 403 Forbidden |
| 8 | `POST /courses/{id}/assignments` | `{id}` (Course) | Dosen lain / Mahasiswa | Menambahkan tugas bodong pada mata kuliah orang lain | `AssignmentPolicy@create` memvalidasi dosen pengampu | 403 Forbidden |
| 9 | `PUT /users/{id}` | `{id}` (User) | Mahasiswa / Dosen | Mengubah profil atau kata sandi akun pengguna lain | `UserPolicy@update` membatasi modifikasi hanya pada `$user->id === $target->id` | 403 Forbidden |
| 10 | `PUT /users/{id}` | Payload `role: 'admin'` | Pengguna biasa | **Privilege Escalation** (mengangkat diri jadi Admin) | `role` tidak ada di `$fillable`; diverifikasi khusus di `UpdateUserRequest` | 403 / 422 |

---

## 2. Implementasi Scoping Kueri (Query Scoping)
Otorisasi tidak hanya bekerja saat pengguna membuka satu halaman detail, melainkan juga harus menyaring data yang tampil pada daftar (Index).

Pada `CourseController@index`:
```php
public function index(Request $request)
{
    $user = Auth::user();

    $coursesQuery = Course::with('lecturer')->withCount(['students', 'materials', 'assignments']);

    // Query Scoping otomatis berdasarkan peran:
    if ($user->role === 'dosen') {
        // Dosen hanya melihat mata kuliah yang diampunya
        $coursesQuery->where('lecturer_id', $user->id);
    } elseif ($user->role === 'mahasiswa') {
        // Mahasiswa hanya melihat mata kuliah yang diikutinya
        $coursesQuery->whereHas('students', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }
    // Admin melihat seluruh mata kuliah (tanpa klausul where tambahan)

    $courses = $coursesQuery->paginate(10)->withQueryString();
    return view('courses.index', compact('courses'));
}
```

---

## 3. Mitigasi Serangan Privilege Escalation
Pada model `app/Models/User.php`:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'nim_nip',
    // 'role' sengaja DITIADAKAN dari $fillable untuk mencegah
    // penyerang menyisipkan input 'role' => 'admin' lewat form/request
];
```
Pengubahan nilai peran hanya diperbolehkan melalui `UserController` jika aktor yang melakukan request terbukti memiliki peran Administrator (`auth()->user()->role === 'admin'`).
