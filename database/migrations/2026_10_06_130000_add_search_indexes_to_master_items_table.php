<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('master_items', function (Blueprint $table) {
            $table->index('kode');
            $table->index('nama');
            $table->index('harga_beli');
        });
    }

    public function down()
    {
        Schema::table('master_items', function (Blueprint $table) {
            $table->dropIndex(['kode']);
            $table->dropIndex(['nama']);
            $table->dropIndex(['harga_beli']);
        });
    }
};
