<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaikan untuk database yang sudah menjalankan migrasi foto versi stub
     * (hanya membuat tabel 'add_foto_to_master_items', tanpa kolom foto).
     */
    public function up()
    {
        if (!Schema::hasColumn('master_items', 'foto')) {
            Schema::table('master_items', function (Blueprint $table) {
                $table->string('foto')->nullable();
            });
        }

        Schema::dropIfExists('add_foto_to_master_items');
    }

    public function down()
    {
        // Sengaja kosong: kolom foto dikelola oleh migrasi 2026_10_06_060733.
    }
};
