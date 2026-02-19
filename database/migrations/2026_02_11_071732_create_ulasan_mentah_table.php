<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('ulasan_mentah', function (Blueprint $table) {
        $table->id();
        $table->string('nama_wisata');
        $table->string('reviewer_name')->nullable();
        $table->integer('rating')->nullable();
        $table->text('ulasan');
        $table->string('tanggal')->nullable();
        $table->datetime('scraping_date')->nullable();
        $table->boolean('is_processed')->default(0);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ulasan_mentah');
    }
};
