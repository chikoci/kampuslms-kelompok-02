<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /**
     * Menampilkan daftar materi perkuliahan dalam satu mata kuliah.
     */
    public function index(Course $course)
    {
        Gate::authorize('view', $course);

        $materials = $course->materials()->latest()->paginate(10);

        return view('materials.index', compact('course', 'materials'));
    }

    /**
     * Menampilkan form tambah materi.
     */
    public function create(Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        return view('materials.create', compact('course'));
    }

    /**
     * Menyimpan materi baru.
     */
    public function store(Request $request, Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        if ($request->input('type') === 'file') {
            $file = $request->file('file');
            if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
                return back()->withErrors(['file' => 'Silakan pilih berkas materi yang ingin diunggah.'])->withInput();
            }
            if (! $file->isValid()) {
                \Illuminate\Support\Facades\Log::warning('Material upload invalid', [
                    'error_code' => $file->getError(),
                    'error_msg'  => $file->getErrorMessage(),
                ]);
                $msg = match ($file->getError()) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas materi melebihi batas maksimal server.',
                    UPLOAD_ERR_PARTIAL => 'Pengunggahan berkas terputus atau berkas sedang dibuka di aplikasi lain.',
                    UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis berkas ke direktori sementara.',
                    default => 'Terjadi kesalahan saat mengunggah berkas materi: ' . $file->getErrorMessage(),
                };
                return back()->withErrors(['file' => $msg])->withInput();
            }
        }

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'type'         => ['required', 'in:file,link,text'],
            'file'         => ['nullable', 'file', 'mimes:pdf,pptx,docx', 'max:20480'], // Maks 20 MB
            'external_url' => ['required_if:type,link', 'nullable', 'url', 'max:255'],
        ], [
            'file.file'        => 'Berkas yang dipilih tidak valid.',
            'file.uploaded'    => 'Berkas materi gagal diunggah ke server. Pastikan berkas tidak sedang terkunci dan coba lagi.',
            'file.mimes'       => 'Format berkas materi harus berupa PDF, PPTX, atau DOCX.',
            'file.max'         => 'Ukuran berkas materi tidak boleh melebihi 20 MB.',
            'external_url.required_if' => 'Silakan masukkan tautan eksternal yang valid.',
        ]);

        $filePath = null;
        $originalName = null;
        $fileSize = null;
        $mimeType = null;

        if ($request->hasFile('file') && $validated['type'] === 'file') {
            $file = $request->file('file');
            // Simpan berkas fisik ke disk privat 'local' (storage/app/private/materials/{course_id})
            $filePath = $file->store('materials/' . $course->id, 'local');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getClientMimeType();
        }

        $course->materials()->create([
            'title'         => $validated['title'],
            'description'   => $validated['description'] ?? null,
            'type'          => $validated['type'],
            'file_path'     => $filePath,
            'original_name' => $originalName,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
            'external_url'  => $validated['external_url'] ?? null,
            'uploaded_by'   => auth()->id() ?? $course->lecturer_id,
        ]);

        return redirect()->route('courses.show', $course)->with('success', 'Materi perkuliahan berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail satu materi.
     */
    public function show(Material $material)
    {
        Gate::authorize('view', $material);

        return view('materials.show', compact('material'));
    }

    /**
     * Menampilkan form edit materi.
     */
    public function edit(Material $material)
    {
        Gate::authorize('update', $material);

        return view('materials.edit', compact('material'));
    }

    /**
     * Memperbarui materi.
     */
    public function update(Request $request, Material $material)
    {
        Gate::authorize('update', $material);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'file'         => ['nullable', 'file', 'mimes:pdf,pptx,docx', 'max:20480'],
            'external_url' => ['nullable', 'url', 'max:255'],
        ], [
            'file.mimes'   => 'Format berkas materi harus berupa PDF, PPTX, atau DOCX.',
            'file.max'     => 'Ukuran berkas materi tidak boleh melebihi 20 MB.',
        ]);

        if ($request->hasFile('file')) {
            // Hapus berkas fisik lama dari disk privat jika ada
            if ($material->file_path && Storage::disk('local')->exists($material->file_path)) {
                Storage::disk('local')->delete($material->file_path);
            }
            $file = $request->file('file');
            $validated['file_path'] = $file->store('materials/' . $material->course_id, 'local');
            $validated['original_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
            $validated['mime_type'] = $file->getClientMimeType();
        }

        $material->update($validated);

        return redirect()->route('courses.show', $material->course)->with('success', 'Materi perkuliahan berhasil diperbarui.');
    }

    /**
     * Menghapus materi.
     * Memicu static::deleting di model Material yang otomatis menghapus berkas fisik di disk privat.
     */
    public function destroy(Material $material)
    {
        Gate::authorize('delete', $material);

        $course = $material->course;
        $material->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Materi perkuliahan berhasil dihapus.');
    }

    /**
     * Mengunduh berkas materi dari disk privat (dengan otorisasi Policy).
     */
    public function download(Material $material)
    {
        Gate::authorize('download', $material);

        if (! $material->file_path || ! Storage::disk('local')->exists($material->file_path)) {
            return back()->with('error', 'Berkas materi tidak ditemukan pada penyimpanan server.');
        }

        return Storage::disk('local')->download($material->file_path, $material->original_name);
    }
}
