# Dokumentasi Keamanan & Matriks Mitigasi IDOR KampusLMS

Dokumen ini disusun sebagai deliverable wajib Milestone M2 (Tugas 2) untuk membuktikan bahwa seluruh titik rawan akses langsung objek (*Insecure Direct Object Reference* / IDOR), eskalasi hak akses (*privilege escalation*), dan kebocoran data tingkat daftar (*list-level data leak*) telah ditutup secara menyeluruh menggunakan Laravel 12 Policies dan Query Scoping.

---

## 1. Definisi & Bahaya IDOR

**IDOR (*Insecure Direct Object Reference*)** terjadi ketika aplikasi menyediakan akses langsung ke objek internal (seperti ID database pada URL `/submissions/5` atau `/courses/2/edit`) tanpa memverifikasi apakah pengguna yang sedang login benar-benar memiliki hak akses atau kepemilikan sah atas data tersebut.

Pada KampusLMS, IDOR berpotensi menyebabkan:
1. Mahasiswa mengintip catatan dan mengunduh berkas jawaban tugas mahasiswa lain.
2. Dosen mengubah atau menghapus materi/tugas mata kuliah dosen lain.
3. Mahasiswa mengakses materi perkuliahan dari kelas yang tidak diikutinya.
4. Pengguna memanipulasi form profil untuk menaikkan perannya sendiri menjadi Administrator (*Privilege Escalation*).

---

## 2. Tabel Titik Rawan IDOR & Mekanisme Penutupnya

| No | Endpoint / URL | Metode HTTP | Parameter | Hak Akses yang Seharusnya | Potensi Ancaman IDOR | Mekanisme Penutup Konkret (Policy / Query Scope) | Status Uji | Test |
|:--:|:---------------|:-----------:|:---------:|:--------------------------|:---------------------|:------------------------------------------------|:----------:|:-----|
| 1 | `/courses` | GET | - | Admin: semua MK<br>Dosen: MK yang diampu<br>Mahasiswa: MK yang diikuti | Mahasiswa/Dosen melihat daftar seluruh mata kuliah orang lain (*List Data Leak*) | **Query Scoping di Controller:**<br>`match ($user->role) { 'admin' => Course::query(), 'dosen' => $user->taughtCourses(), 'mahasiswa' => $user->courses() }` | 200 (Scoped) | `AuthzTest::test_course_index_is_scoped_by_role` |
| 2 | `/courses/{course}` | GET | `{course}` | Admin, Dosen pengampu, & Mahasiswa terdaftar | Mahasiswa membuka detail kelas yang tidak diikutinya | **`CoursePolicy@view`:**<br>`$user->role === 'admin' \|\| $course->lecturer_id === $user->id \|\| $course->students()->where('user_id', $user->id)->exists()` | 403 Forbidden | `AuthzTest::test_mahasiswa_cannot_view_unregistered_course`<br>`PrioritySecurityAndFlowTest::test_unregistered_student_cannot_view_course_download_material_or_submit_assignment`<br>`PrioritySecurityAndFlowTest::test_other_lecturer_cannot_access_or_modify_other_lecturer_course_resources` |
| 3 | `/courses/{course}/edit` | GET | `{course}` | Admin & Dosen pengampu | Dosen lain mengedit informasi mata kuliah | **`CoursePolicy@update`:**<br>`$user->role === 'admin' \|\| $course->lecturer_id === $user->id` dipanggil via `Gate::authorize('update', $course)` | 403 Forbidden | `AuthzTest::test_dosen_cannot_edit_other_dosen_course`<br>`PrioritySecurityAndFlowTest::test_other_lecturer_cannot_access_or_modify_other_lecturer_course_resources` |
| 4 | `/courses/{course}` | PUT / PATCH | `{course}` | Admin & Dosen pengampu | Dosen lain mengirim request modifikasi mata kuliah via curl/Postman | **`CoursePolicy@update`:**<br>Divalidasi ganda di `UpdateCourseRequest@authorize` dan `CourseController@update` via `Gate::authorize('update', $course)` | 403 Forbidden | `AuthzTest::test_dosen_cannot_change_lecturer_id_on_course_update`<br>`PrioritySecurityAndFlowTest::test_other_lecturer_cannot_access_or_modify_other_lecturer_course_resources` |
| 5 | `/courses/{course}` | DELETE | `{course}` | Hanya Administrator | Dosen/Mahasiswa menghapus mata kuliah | **`CoursePolicy@delete`:**<br>`$user->role === 'admin'` dipanggil via `Gate::authorize('delete', $course)` | 403 Forbidden | `PrioritySecurityAndFlowTest::test_non_admin_cannot_create_delete_course_or_escalate_role` |
| 6 | `/courses/{course}/materials/create` | GET / POST | `{course}` | Admin & Dosen pengampu mata kuliah | Dosen lain atau mahasiswa menambahkan materi pada mata kuliah | **`MaterialPolicy@create`:**<br>`$user->role === 'admin' \|\| ($user->role === 'dosen' && $user->id === $course->lecturer_id)` | 403 Forbidden | `AuthzTest::test_dosen_cannot_create_material_in_other_course`<br>`AuthzTest::test_mahasiswa_cannot_create_material`<br>`PrioritySecurityAndFlowTest::test_other_lecturer_cannot_access_or_modify_other_lecturer_course_resources` |
| 7 | `/materials/{material}/download` | GET | `{material}` | Admin, Dosen pengampu, Mahasiswa terdaftar | Mahasiswa liar mengunduh materi privat kelas lain | **`MaterialPolicy@download`:**<br>Memeriksa relasi `students()->where('user_id', $user->id)->exists()` | 403 Forbidden | `PrioritySecurityAndFlowTest::test_unregistered_student_cannot_view_course_download_material_or_submit_assignment`<br>`PrioritySecurityAndFlowTest::test_other_lecturer_cannot_access_or_modify_other_lecturer_course_resources` |
| 8 | `/courses/{course}/assignments/create` | GET / POST | `{course}` | Admin & Dosen pengampu mata kuliah | Mahasiswa atau dosen lain membuat penugasan ilegal | **`AssignmentPolicy@create`:**<br>`$user->role === 'admin' \|\| ($user->role === 'dosen' && $user->id === $course->lecturer_id)` | 403 Forbidden | `AuthzTest::test_dosen_cannot_create_assignment_in_other_course`<br>`AuthzTest::test_mahasiswa_cannot_create_assignment`<br>`PrioritySecurityAndFlowTest::test_other_lecturer_cannot_access_or_modify_other_lecturer_course_resources` |
| 9 | `/assignments/{assignment}/submissions` | GET | `{assignment}` | Admin & Dosen pengampu (semua)<br>Mahasiswa (hanya miliknya) | Mahasiswa mengintip daftar seluruh pengumpulan rekan sekelas | **Query Scoping & `SubmissionPolicy@viewAny`:**<br>Mahasiswa dibatasi query `where('user_id', $user->id)` di `SubmissionController@index` | 200 (Filtered) | `PrioritySecurityAndFlowTest::test_assignment_submissions_index_is_scoped_for_student` |
| 10 | `/assignments/{assignment}/submissions` | POST | `{assignment}` | Mahasiswa yang terdaftar pada mata kuliah | Dosen/Admin atau mahasiswa yang tidak terdaftar mengirim tugas | **`SubmissionPolicy@create`:**<br>`$user->role === 'mahasiswa' && $assignment->course->students()->where('user_id', $user->id)->exists()` | 403 Forbidden | `AuthzTest::test_student_cannot_submit_to_draft_assignment`<br>`PrioritySecurityAndFlowTest::test_unregistered_student_cannot_view_course_download_material_or_submit_assignment`<br>`PrioritySecurityAndFlowTest::test_admin_and_student_cannot_grade_submission_nor_non_student_submit` |
| 11 | `/submissions/{submission}` | GET | `{submission}` | Mahasiswa pemilik, Dosen pengampu, & Admin | **IDOR Utama:** Mahasiswa A mengganti ID ke ID milik Mahasiswa B | **`SubmissionPolicy@view`:**<br>`$user->role === 'admin' \|\| $user->id === $submission->assignment->course->lecturer_id \|\| $user->id === $submission->user_id` | 403 Forbidden | `AuthzTest::test_student_cannot_view_other_student_submission_idor_protection`<br>`AuthzTest::test_other_lecturer_cannot_view_submission`<br>`PrioritySecurityAndFlowTest::test_student_cannot_download_or_view_other_student_submission` |
| 12 | `/submissions/{submission}/download` | GET | `{submission}` | Mahasiswa pemilik, Dosen pengampu, & Admin | Mahasiswa mengunduh berkas jawaban tugas mahasiswa lain | **`SubmissionPolicy@download`:**<br>Memanggil `SubmissionPolicy@view` sebelum mengirim stream unduhan berkas | 403 Forbidden | `FileStorageTest::test_submission_download_authorizes_policy_and_streams_file`<br>`PrioritySecurityAndFlowTest::test_student_cannot_download_or_view_other_student_submission` |
| 13 | `/submissions/{submission}/grades` | POST | `{submission}` | Dosen pengampu mata kuliah terkait | Mahasiswa memberi nilai diri sendiri, atau Admin memberi nilai di luar matriks | **`GradePolicy@create`:**<br>`$user->role === 'dosen' && $user->id === $submission->assignment->course->lecturer_id` | 403 Forbidden | `AuthzTest::test_other_lecturer_cannot_grade_submission`<br>`AuthzTest::test_admin_cannot_grade_submission_according_to_spec_matrix`<br>`PrioritySecurityAndFlowTest::test_admin_and_student_cannot_grade_submission_nor_non_student_submit` |
| 14 | `/users` | GET | - | Hanya Administrator | Pengguna biasa menjelajahi direktori dan kredensial seluruh pengguna | **`UserPolicy@viewAny`:**<br>`$user->role === 'admin'` dipanggil via `Gate::authorize('viewAny', User::class)` | 403 Forbidden | `AuthzTest::test_non_admin_cannot_access_user_index`<br>`AuthzTest::test_admin_can_access_user_index` |
| 15 | `/users/{user}` | PUT / PATCH | `{user}` | Admin (siapa saja)<br>User pemilik (profil sendiri) | Penyerang mengirim payload `role=admin` saat edit profil (*Privilege Escalation*) | **`UserPolicy@updateRole`:**<br>Kolom `role` dikeluarkan dari `$fillable`, dan di controller dipagari `Gate::allows('updateRole', $user)` | 403 (Ignored) | `AuthzTest::test_non_admin_cannot_escalate_role_via_update`<br>`AuthzTest::test_student_cannot_view_edit_or_delete_other_user`<br>`PrioritySecurityAndFlowTest::test_non_admin_cannot_create_delete_course_or_escalate_role` |

---

## 3. Prinsip Pertahanan Berlapis (*Defense in Depth*)

KampusLMS menerapkan 4 lapis perlindungan:

1. **Lapis 1: Routing & Middleware (`routes/web.php`)**
   - Middleware `auth` memastikan hanya pengguna terotentikasi yang dapat mengakses modul akademik.
   - Session fixation dicegah melalui `session()->regenerate()` saat login.
2. **Lapis 2: Controller & Policy Discovery (`Gate::authorize`)**
   - Setiap aksi controller wajib memanggil `Gate::authorize('ability', $model)`.
   - Tidak mengandalkan `$this->authorize()` (karena trait `AuthorizesRequests` tidak lagi ada secara default di controller Laravel 11/12).
3. **Lapis 3: Query-Level Data Scoping**
   - Tidak pernah menarik semua baris tabel lalu memfilternya di Blade View.
   - Seluruh query daftar (`index`) langsung dibatasi dengan relasi pengguna login (`$user->courses()`, `$user->taughtCourses()`).
4. **Lapis 4: Tampilan Blade Terkondisi (`@can`)**
   - Tombol aksi (+ Tambah, Edit, Hapus, Beri Nilai) hanya ditampilkan ke pengguna yang memenuhi otorisasi Policy.
   - Catatan penting: `@can` di Blade adalah pemanis antarmuka (UX), sedangkan `Gate::authorize()` di controller adalah benteng keamanan mutlak.
