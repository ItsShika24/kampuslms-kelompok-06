<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah week_number ke materials — pertemuan ke-1 s/d 16
        Schema::table('materials', function (Blueprint $table) {
            $table->unsignedTinyInteger('week_number')
                  ->nullable()
                  ->default(null)
                  ->after('external_url')
                  ->comment('Nomor pertemuan 1–16, null berarti tidak terikat pertemuan tertentu');
        });

        // Tambah week_number ke assignments
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('week_number')
                  ->nullable()
                  ->default(null)
                  ->after('status')
                  ->comment('Nomor pertemuan 1–16, null berarti tidak terikat pertemuan tertentu');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('week_number');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('week_number');
        });
    }
};
