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
        Gate::authorize('delete', $material);

        $course = $material->course;
        $material->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Materi perkuliahan berhasil dihapus.');
    }

    /**
     * Mengunduh berkas materi (dengan otorisasi Policy).
     */
    public function download(Material $material)
    {
        Gate::authorize('download', $material);

        if (! $material->file_path || ! Storage::exists($material->file_path)) {
            return back()->with('error', 'Berkas materi tidak ditemukan pada penyimpanan server.');
        }

        return Storage::download($material->file_path, $material->original_name);
    }
}
