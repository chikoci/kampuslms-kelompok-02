<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrasi performa Materi 11 (BUILD Poin 4):
     * Menambahkan composite index untuk mengoptimasi query berulang paling sering.
     */
    public function up(): void
    {
        // 1. Composite index pada tabel assignments
        // Query yang diuntungkan:
        // - Dashboard Mahasiswa/Dosen saat mencari tugas aktif yang diurutkan batas waktu:
        //   SELECT * FROM assignments WHERE course_id IN (...) AND status = 'published' AND due_at >= ? ORDER BY due_at ASC
        // - Detail Mata Kuliah & Rekap Nilai saat menyaring tugas terbit:
        //   SELECT * FROM assignments WHERE course_id = ? AND status != 'draft' ORDER BY due_at ASC
        Schema::table('assignments', function (Blueprint $table) {
            $table->index(['course_id', 'status', 'due_at'], 'assignments_course_status_due_idx');
        });

        // 2. Composite index pada tabel courses
        // Query yang diuntungkan:
        // - Halaman Daftar Mata Kuliah Dosen & Dashboard Dosen:
        //   SELECT * FROM courses WHERE lecturer_id = ? AND status = 'active' ORDER BY created_at DESC
        Schema::table('courses', function (Blueprint $table) {
            $table->index(['lecturer_id', 'status'], 'courses_lecturer_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('assignments_course_status_due_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_lecturer_status_idx');
        });
    }
};

