# Implementasi Pengumpulan Tugas & Keamanan Berkas (Minggu 9 - Prompt Pack B)

## 1. Ketentuan Implementasi
Berdasarkan perancangan arsitektur penyimpanan berkas pada Prompt Pack A, fitur pengumpulan tugas (submission) pada KampusLMS (Laravel 12) telah diimplementasikan dengan ketentuan:
* **Disk Privat**: Berkas disimpan di disk `local` (`storage/app/private/submissions/{assignment_id}`), BUKAN di `storage/app/public`.
* **Nama Berkas Acak**: Nama fisik berkas diacak secara otomatis oleh Laravel untuk mencegah eksploitasi *Path Traversal* (`../../.env`); nama asli berkas dicatat pada kolom terpisah `original_name`.
* **Validasi Ketat**: Wajib ada (`required`), tipe file sah (`file`), format diizinkan `mimes:pdf,doc,docx`, ukuran maksimal 5 MB (`max:5120`).
* **Pengumpulan Ulang (`updateOrCreate`)**: Mahasiswa yang mengumpulkan ulang akan menimpa submission lama berdasarkan composite index `(assignment_id, user_id)`. Berkas fisik lama otomatis dihapus dari disk agar tidak menjadi berkas sampah.
* **Perhitungan Server-Side**: Status keterlambatan `is_late` dihitung di server berdasarkan perbandingan `now()->greaterThan($assignment->due_at)`. Jika `allow_late` bernilai `false` dan sudah melewati tenggat waktu, pengumpulan ditolak.
* **Otorisasi Unduhan**: Endpoint unduhan memanggil Policy melalui `Gate::authorize('download', $submission)` sebelum file dikirim. Di Laravel 12, helper controller `$this->authorize()` ditiadakan sehingga pemanggilan wajib menggunakan fasad `Gate::authorize()`.
* **Penghapusan Berkas Fisik**: Berkas fisik di disk privat otomatis dihapus saat record model dihapus melalui event Eloquent `static::deleting`.

---

## 2. Kode Implementasi Utama

### A. Controller Pengumpulan Tugas (`app/Http/Controllers/SubmissionController.php`)
```php
public function store(Request $request, Assignment $assignment)
{
    Gate::authorize('create', [Submission::class, $assignment]);

    // Cek apakah pengumpulan terlambat ditolak
    $isLate = now()->greaterThan($assignment->due_at);
    if ($isLate && ! $assignment->allow_late) {
        return back()->withErrors([
            'file' => 'Batas waktu pengumpulan tugas telah berakhir dan pengumpulan terlambat tidak diizinkan.',
        ])->withInput();
    }

    $file = $request->file('file');

    if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
        return back()->withErrors(['file' => 'Silakan pilih berkas tugas yang ingin dikumpulkan terlebih dahulu.'])->withInput();
    }

    // Validasi: mimes pdf/doc/docx, maksimal 5 MB (5120 KB), wajib ada
    $validated = $request->validate([
        'note' => ['nullable', 'string', 'max:1000'],
        'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
    ], [
        'file.required' => 'Silakan pilih berkas tugas yang ingin dikumpulkan terlebih dahulu.',
        'file.mimes'    => 'Format berkas harus berupa PDF, DOC, atau DOCX.',
        'file.max'      => 'Ukuran berkas tugas tidak boleh melebihi 5 MB.',
        'file.file'     => 'Berkas yang diunggah harus berupa file yang valid.',
    ]);

    $userId = auth()->id();

    // Hapus berkas fisik lama jika mahasiswa mengumpulkan ulang
    $existing = $assignment->submissions()->where('user_id', $userId)->first();
    if ($existing && $existing->file_path && Storage::disk('local')->exists($existing->file_path)) {
        Storage::disk('local')->delete($existing->file_path);
    }

    // Simpan fisik berkas ke disk privat 'local' (storage/app/private/submissions/{assignment_id})
    // Nama berkas fisik otomatis diacak oleh store() untuk mencegah Path Traversal
    $filePath = $file->store('submissions/' . $assignment->id, 'local');
    $originalName = $file->getClientOriginalName();
    $fileSize = $file->getSize();

    // Terapkan updateOrCreate sesuai composite index (assignment_id, user_id)
    $submission = $assignment->submissions()->updateOrCreate(
        ['user_id' => $userId],
        [
            'file_path'     => $filePath,
            'original_name' => $originalName,
            'file_size'     => $fileSize,
            'note'          => $validated['note'] ?? null,
            'submitted_at'  => now(),
            'is_late'       => $isLate,
        ]
    );

    return redirect()->route('assignments.show', $assignment)->with('success', 'Tugas berhasil dikumpulkan.');
}

public function download(Submission $submission)
{
    // Memanggil Policy via Gate::authorize() karena $this->authorize() tidak ada di Laravel 12
    Gate::authorize('download', $submission);

    if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
        return back()->with('error', 'Berkas submission tidak ditemukan pada penyimpanan server.');
    }

    return Storage::disk('local')->download($submission->file_path, $submission->original_name);
}
```

### B. Event Model Lifecycle pada `app/Models/Submission.php`
```php
protected static function booted(): void
{
    static::deleting(function (Submission $submission) {
        if ($submission->file_path && Storage::disk('local')->exists($submission->file_path)) {
            Storage::disk('local')->delete($submission->file_path);
        }
    });
}
```

---

## 3. Analisis: Kapan Event Model TIDAK Terpicu?

Penghapusan berkas fisik melalui event Eloquent `static::deleting` sangat efektif saat record dihapus via instance model (`$submission->delete()`). Namun, ada beberapa skenario kritis di mana event model **TIDAK TERPICU**:

### Kasus 1: Penghapusan Massal via Query Builder
Jika developer menghapus record langsung menggunakan Query Builder atau mass delete:
```php
Submission::where('assignment_id', $assignmentId)->delete();
// atau
DB::table('submissions')->where('user_id', $userId)->delete();
```
* **Penyebab**: Perintah ini langsung diterjemahkan menjadi kueri SQL mentah `DELETE FROM submissions WHERE ...` di database. Eloquent tidak pernah membuat instance model individual, sehingga event lifecycle (`deleting` / `deleted`) sama sekali tidak dipanggil. Akibatnya, baris database hilang tetapi berkas fisik tertinggal di folder `storage/app/private` (berkas sampah / *orphaned files*).

### Kasus 2: Database Cascade Delete (`ON DELETE CASCADE`)
Pada migrasi database:
```php
$table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
```
Jika kita menghapus data induk (`Assignment`) secara langsung tanpa memuat relasi:
* **Penyebab**: Mesin database (MySQL/PostgreSQL/SQLite) langsung mengeksekusi penghapusan seluruh baris anak (`submissions`) di level database engine. Laravel tidak mengetahui terjadinya penghapusan record anak tersebut, sehingga event `deleting` pada `Submission` tidak pernah dieksekusi.

### Kasus 3: Penggunaan `withoutEvents()`
Jika pemanggilan delete dibungkus dalam metode `withoutEvents`:
```php
Submission::withoutEvents(function () use ($submission) {
    $submission->delete();
});
```
* **Penyebab**: Fitur bawaan Laravel ini secara sengaja membungkam seluruh event listener model.

---

## 4. Solusi Penanganan Kasus Cascade di KampusLMS

Untuk memastikan berkas fisik tidak pernah menjadi berkas yatim/sampah saat record induk dihapus, KampusLMS menerapkan penanganan berjenjang pada model induk (`Assignment` dan `Course`):

### A. Pada Model `Assignment` (`app/Models/Assignment.php`)
```php
protected static function booted(): void
{
    static::deleting(function (Assignment $assignment) {
        // Lakukan iterasi penghapusan per instance model anak
        // agar event deleting pada Submission tetap terpicu
        foreach ($assignment->submissions as $submission) {
            $submission->delete();
        }
    });
}
```

### B. Pada Model `Course` (`app/Models/Course.php`)
```php
protected static function booted(): void
{
    static::deleting(function (Course $course) {
        // Hapus seluruh materi fisik
        foreach ($course->materials as $material) {
            $material->delete();
        }
        // Hapus seluruh tugas dan memicu penghapusan submission fisik
        foreach ($course->assignments as $assignment) {
            $assignment->delete();
        }
    });
}
```

Dengan pola di atas, setiap kali Dosen menghapus Tugas atau Admin menghapus Mata Kuliah, seluruh rantai relasi berkas fisik di `storage/app/private` dibersihkan hingga tuntas.
