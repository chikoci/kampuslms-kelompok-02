<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fitur Diferensiasi: Rubrik Penilaian Bertingkat (Kriteria Berbobot & Auto-Calculation).
     */
    public function up(): void
    {
        // 1. Tabel Induk Rubrik (1 Tugas memiliki tepat 1 Rubrik)
        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->unique()->constrained('assignments')->cascadeOnDelete();
            $table->string('title')->default('Rubrik Penilaian');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Kriteria Rubrik (Memuat Bobot Persentase)
        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubrics')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2); // Bobot dalam % (misal: 40.00 untuk 40%)
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        // 3. Tabel Tingkatan Performa (Levels / Tiers per Kriteria)
        Schema::create('rubric_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_criterion_id')->constrained('rubric_criteria')->cascadeOnDelete();
            $table->string('title'); // Contoh: Sangat Baik, Baik, Cukup, Kurang
            $table->text('description')->nullable();
            $table->decimal('score_percentage', 5, 2); // Persentase skor capaian level (misal: 100.00, 80.00, 50.00)
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        // 4. Tabel Histori Penilaian Rubrik per Submission
        Schema::create('rubric_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained('rubric_criteria')->cascadeOnDelete();
            $table->foreignId('rubric_level_id')->nullable()->constrained('rubric_levels')->nullOnDelete();
            $table->decimal('score_awarded', 8, 2); // Nilai riil yang diperoleh pada kriteria ini
            $table->text('feedback')->nullable(); // Catatan spesifik per kriteria
            $table->timestamps();

            // 1 Submission hanya memiliki 1 catatan penilaian per kriteria
            $table->unique(['submission_id', 'rubric_criterion_id'], 'sub_criterion_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubric_assessments');
        Schema::dropIfExists('rubric_levels');
        Schema::dropIfExists('rubric_criteria');
        Schema::dropIfExists('rubrics');
    }
};

