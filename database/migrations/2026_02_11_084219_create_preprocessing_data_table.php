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
    Schema::create('preprocessing_data', function (Blueprint $table) {
        $table->id();

        $table->text('ulasan_asli');
        $table->text('cleaning');
        $table->text('tokenizing');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preprocessing_data');
    }
};
