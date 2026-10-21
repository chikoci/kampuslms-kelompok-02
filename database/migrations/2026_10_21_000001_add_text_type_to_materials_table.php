<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Memperbarui kolom ENUM 'type' pada tabel materials agar mendukung 'text' (Catatan Teks Saja)
        // Menggunakan raw SQL agar kompatibel di MySQL tanpa memerlukan pustaka doktrin dbal tambahan
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `materials` MODIFY COLUMN `type` ENUM('file', 'link', 'text') NOT NULL DEFAULT 'file'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `materials` MODIFY COLUMN `type` ENUM('file', 'link') NOT NULL DEFAULT 'file'");
        }
    }
};
