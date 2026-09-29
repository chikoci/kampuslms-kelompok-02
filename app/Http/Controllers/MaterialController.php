<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /**
     * Menampilkan daftar materi perkuliahan.
     */
    public function index(Course $course)
    {
        $materials = $course->materials()->latest()->paginate(10);

        return view('materials.index', compact('course', 'materials'));
    }

    /**
     * Menampilkan form tambah materi.
     */
    public function create(Course $course)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda bukan dosen pengampu mata kuliah ini.'
            );
        }

        return view('materials.create', compact('course'));
    }

    /**
     * Menyimpan materi baru.
     */
    public function store(Request $request, Course $course)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda bukan dosen pengampu mata kuliah ini.'
            );
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:file,link,text'],
            'file_path' => ['nullable', 'string'],
            'original_name' => ['nullable', 'string'],
        ]);

        $validated['uploaded_by'] = auth()->id() ?? $course->lecturer_id;
        $course->materials()->create($validated);

        return redirect()->route('courses.show', $course)->with('success', 'Materi perkuliahan berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail satu materi (shallow route).
     */
    public function show(Material $material)
    {
        return view('materials.show', compact('material'));
    }

    /**
     * Menampilkan form edit materi.
     */
    public function edit(Material $material)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $material->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki hak untuk mengedit materi ini.'
            );
        }

        return view('materials.edit', compact('material'));
    }

    /**
     * Memperbarui materi.
     */
    public function update(Request $request, Material $material)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $material->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki hak untuk memperbarui materi ini.'
            );
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $material->update($validated);

        return redirect()->route('courses.show', $material->course)->with('success', 'Materi perkuliahan berhasil diperbarui.');
    }

    /**
     * Menghapus materi.
     */
    public function destroy(Material $material)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $material->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki hak untuk menghapus materi ini.'
            );
        }

        $course = $material->course;
        $material->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Materi perkuliahan berhasil dihapus.');
    }

    /**
     * Mengunduh berkas materi (dengan proteksi IDOR).
     */
    public function download(Material $material)
    {
        if (auth()->check() && auth()->user()->role === 'mahasiswa') {
            // Mahasiswa hanya boleh unduh jika terdaftar di kelas ini
            $isEnrolled = $material->course->students()->where('user_id', auth()->id())->exists();
            abort_unless($isEnrolled, 403, 'Akses ditolak: Anda tidak terdaftar pada mata kuliah ini.');
        }

        if (! Storage::exists($material->file_path)) {
            return back()->with('error', 'Berkas materi tidak ditemukan pada penyimpanan server.');
        }

        return Storage::download($material->file_path, $material->original_name);
    }
}
