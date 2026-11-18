<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Rubric;
use App\Models\RubricAssessment;
use App\Models\RubricCriterion;
use App\Models\RubricLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RubricController extends Controller
{
    /**
     * Menampilkan form kelola rubrik untuk tugas tertentu.
     */
    public function manage(Assignment $assignment): View
    {
        Gate::authorize('manage', [Rubric::class, $assignment]);

        $rubric = $assignment->rubric()->with(['criteria.levels'])->first();

        // Cek apakah rubrik sudah pernah digunakan untuk menilai submission
        $isAssessed = false;
        $initialCriteria = null;

        if ($rubric) {
            $criterionIds = $rubric->criteria->pluck('id');
            $isAssessed = RubricAssessment::whereIn('rubric_criterion_id', $criterionIds)->exists();

            $initialCriteria = $rubric->criteria->map(function ($c) {
                return [
                    'id'          => $c->id,
                    'title'       => $c->title,
                    'description' => $c->description,
                    'weight'      => (float) $c->weight,
                    'levels'      => $c->levels->map(function ($l) {
                        return [
                            'id'               => $l->id,
                            'title'            => $l->title,
                            'description'      => $l->description,
                            'score_percentage' => (float) $l->score_percentage,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();
        }

        if (! $initialCriteria) {
            $initialCriteria = [
                [
                    'title'       => 'Kualitas & Kebenaran Fungsional',
                    'description' => 'Aplikasi bekerja sesuai instruksi, bebas error, validasi lengkap.',
                    'weight'      => 50,
                    'levels'      => [
                        ['title' => 'Sangat Baik', 'description' => 'Seluruh fitur bekerja sempurna dan bebas celah', 'score_percentage' => 100],
                        ['title' => 'Baik', 'description' => 'Fitur utama bekerja, terdapat kendala kecil', 'score_percentage' => 80],
                        ['title' => 'Cukup', 'description' => 'Fitur sebagian jalan, ada bug mendasar', 'score_percentage' => 60],
                        ['title' => 'Kurang', 'description' => 'Fitur inti gagal berjalan atau tidak tuntas', 'score_percentage' => 40],
                    ],
                ],
                [
                    'title'       => 'Struktur Kode & Dokumentasi',
                    'description' => 'Kerapian arsitektur, pemisahan MVC, penulisan catatan penjelasan.',
                    'weight'      => 50,
                    'levels'      => [
                        ['title' => 'Sangat Baik', 'description' => 'Struktur MVC bersih, dokumentasi lengkap', 'score_percentage' => 100],
                        ['title' => 'Baik', 'description' => 'Struktur rapi, dokumentasi memadai', 'score_percentage' => 80],
                        ['title' => 'Cukup', 'description' => 'Struktur kurang rapi, dokumentasi minim', 'score_percentage' => 60],
                        ['title' => 'Kurang', 'description' => 'Kode berantakan tanpa dokumentasi', 'score_percentage' => 40],
                    ],
                ],
            ];
        }

        return view('rubrics.manage', compact('assignment', 'rubric', 'isAssessed', 'initialCriteria'));
    }

    /**
     * Menyimpan atau memperbarui rubrik penilaian beserta kriteria dan tingkatannya.
     */
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        Gate::authorize('manage', [Rubric::class, $assignment]);

        $validated = $request->validate([
            'title'                               => ['required', 'string', 'max:255'],
            'description'                         => ['nullable', 'string', 'max:1000'],
            'criteria'                            => ['required', 'array', 'min:1'],
            'criteria.*.id'                       => ['nullable', 'integer'],
            'criteria.*.title'                    => ['required', 'string', 'max:255'],
            'criteria.*.description'              => ['nullable', 'string', 'max:500'],
            'criteria.*.weight'                   => ['required', 'numeric', 'min:0.01', 'max:100'],
            'criteria.*.levels'                   => ['required', 'array', 'min:1'],
            'criteria.*.levels.*.id'              => ['nullable', 'integer'],
            'criteria.*.levels.*.title'           => ['required', 'string', 'max:255'],
            'criteria.*.levels.*.description'     => ['nullable', 'string', 'max:500'],
            'criteria.*.levels.*.score_percentage'=> ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'criteria.required' => 'Rubrik harus memiliki minimal 1 kriteria penilaian.',
            'criteria.*.title.required' => 'Judul kriteria wajib diisi.',
            'criteria.*.weight.required' => 'Bobot kriteria wajib diisi.',
            'criteria.*.levels.required' => 'Setiap kriteria harus memiliki minimal 1 tingkatan (level).',
            'criteria.*.levels.*.title.required' => 'Nama tingkatan/level wajib diisi.',
            'criteria.*.levels.*.score_percentage.required' => 'Persentase skor tingkatan wajib diisi.',
        ]);

        // Validasi: Total bobot seluruh kriteria harus berjumlah tepat 100%
        $totalWeight = collect($validated['criteria'])->sum(fn ($c) => (float) $c['weight']);
        if (abs($totalWeight - 100.0) > 0.05) {
            return back()
                ->withErrors(['criteria' => "Total bobot seluruh kriteria harus berjumlah tepat 100% (saat ini: {$totalWeight}%)."])
                ->withInput();
        }

        DB::transaction(function () use ($assignment, $validated) {
            /** @var Rubric $rubric */
            $rubric = $assignment->rubric()->updateOrCreate(
                ['assignment_id' => $assignment->id],
                [
                    'title'       => $validated['title'],
                    'description' => $validated['description'] ?? null,
                ]
            );

            $existingCriteriaIds = $rubric->criteria()->pluck('id')->all();
            $updatedCriteriaIds = [];

            foreach ($validated['criteria'] as $cIndex => $cData) {
                $criterionId = $cData['id'] ?? null;

                /** @var RubricCriterion $criterion */
                $criterion = $rubric->criteria()->updateOrCreate(
                    ['id' => $criterionId],
                    [
                        'title'       => $cData['title'],
                        'description' => $cData['description'] ?? null,
                        'weight'      => $cData['weight'],
                        'order'       => $cIndex,
                    ]
                );

                $updatedCriteriaIds[] = $criterion->id;

                // Kelola levels untuk kriteria ini
                $existingLevelIds = $criterion->levels()->pluck('id')->all();
                $updatedLevelIds = [];

                foreach ($cData['levels'] as $lIndex => $lData) {
                    $levelId = $lData['id'] ?? null;

                    /** @var RubricLevel $level */
                    $level = $criterion->levels()->updateOrCreate(
                        ['id' => $levelId],
                        [
                            'title'            => $lData['title'],
                            'description'      => $lData['description'] ?? null,
                            'score_percentage' => $lData['score_percentage'],
                            'order'            => $lIndex,
                        ]
                    );

                    $updatedLevelIds[] = $level->id;
                }

                // Hapus level yang dibuang di form
                $deletedLevelIds = array_diff($existingLevelIds, $updatedLevelIds);
                if (! empty($deletedLevelIds)) {
                    RubricLevel::whereIn('id', $deletedLevelIds)->delete();
                }
            }

            // Hapus kriteria yang dibuang di form
            $deletedCriteriaIds = array_diff($existingCriteriaIds, $updatedCriteriaIds);
            if (! empty($deletedCriteriaIds)) {
                RubricCriterion::whereIn('id', $deletedCriteriaIds)->delete();
            }
        });

        return redirect()->route('assignments.show', $assignment)
            ->with('success', 'Rubrik penilaian bertingkat berhasil disimpan.');
    }

    /**
     * Menghapus rubrik dari tugas.
     */
    public function destroy(Assignment $assignment): RedirectResponse
    {
        Gate::authorize('manage', [Rubric::class, $assignment]);

        $rubric = $assignment->rubric;
        if ($rubric) {
            $rubric->delete();
        }

        return redirect()->route('assignments.show', $assignment)
            ->with('success', 'Rubrik penilaian berhasil dihapus.');
    }
}

