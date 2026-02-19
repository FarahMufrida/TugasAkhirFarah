<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('preprocessing_data', function (Blueprint $table) {
        $table->text('case_folding')->nullable();
        $table->text('stopword_removal')->nullable();
        $table->text('slang_word')->nullable();
        $table->text('stemming')->nullable();
        $table->text('final_text')->nullable();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preprocessing_data', function (Blueprint $table) {
            $table->dropColumn([
            'case_folding',
            'stopword_removal',
            'slang_word',
            'stemming',
            'final_text'
            ]);
        });
    }
};
