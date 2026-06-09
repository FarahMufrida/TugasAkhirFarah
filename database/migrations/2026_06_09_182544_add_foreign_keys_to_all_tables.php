<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── STEP 1: Seragamkan tipe data ──
        Schema::table('ulasan', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->change();
            $table->unsignedBigInteger('periode_id')->nullable()->change();
        });

        Schema::table('hasil_analisis', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->change();
            $table->unsignedBigInteger('ulasan_id')->nullable()->change();
        });

        Schema::table('evaluasi_model', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->change();
        });

        // ── STEP 2: Tambah foreign key ──
        Schema::table('ulasan', function (Blueprint $table) {
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('periode_analisis')
                  ->onDelete('cascade');
        });

        Schema::table('hasil_analisis', function (Blueprint $table) {
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('periode_analisis')
                  ->onDelete('cascade');

            $table->foreign('ulasan_id')
                  ->references('id')
                  ->on('ulasan')
                  ->onDelete('set null');
        });

        Schema::table('evaluasi_model', function (Blueprint $table) {
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('periode_analisis')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('ulasan', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
        });

        Schema::table('hasil_analisis', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropForeign(['ulasan_id']);
        });

        Schema::table('evaluasi_model', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
        });
    }
};