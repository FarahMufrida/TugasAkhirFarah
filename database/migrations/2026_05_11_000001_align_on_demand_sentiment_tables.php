<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('periode_analisis')) {
            Schema::create('periode_analisis', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->unsignedTinyInteger('bulan');
                $table->unsignedSmallInteger('tahun');
                $table->timestamps();
                $table->unique(['bulan', 'tahun']);
            });
        }

        if (! Schema::hasTable('ulasan')) {
            Schema::create('ulasan', function (Blueprint $table) {
                $table->id();
                $table->string('wisata')->nullable();
                $table->string('reviewer')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('ulasan')->nullable();
                $table->string('tanggal')->nullable();
                $table->dateTime('scraping_date')->nullable();
                $table->foreignId('periode_id')->nullable()->index();
                $table->string('sentimen')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('ulasan', function (Blueprint $table) {
                if (! Schema::hasColumn('ulasan', 'wisata')) {
                    $table->string('wisata')->nullable()->after('id');
                }
                if (! Schema::hasColumn('ulasan', 'reviewer')) {
                    $table->string('reviewer')->nullable()->after('wisata');
                }
                if (! Schema::hasColumn('ulasan', 'rating')) {
                    $table->unsignedTinyInteger('rating')->nullable()->after('reviewer');
                }
                if (! Schema::hasColumn('ulasan', 'ulasan')) {
                    $table->text('ulasan')->nullable()->after('rating');
                }
                if (! Schema::hasColumn('ulasan', 'tanggal')) {
                    $table->string('tanggal')->nullable()->after('ulasan');
                }
                if (! Schema::hasColumn('ulasan', 'scraping_date')) {
                    $table->dateTime('scraping_date')->nullable()->after('tanggal');
                }
                if (! Schema::hasColumn('ulasan', 'periode_id')) {
                    $table->foreignId('periode_id')->nullable()->index()->after('scraping_date');
                }
                if (! Schema::hasColumn('ulasan', 'sentimen')) {
                    $table->string('sentimen')->nullable()->after('periode_id');
                }
            });
        }

        if (! Schema::hasTable('hasil_analisis')) {
            Schema::create('hasil_analisis', function (Blueprint $table) {
                $table->id();
                $table->string('wisata')->nullable();
                $table->text('ulasan_asli')->nullable();
                $table->text('ulasan_bersih')->nullable();
                $table->text('hasil_preprocessing')->nullable();
                $table->string('sentimen')->nullable();
                $table->float('probabilitas')->nullable();
                $table->foreignId('periode_id')->nullable()->index();
                $table->timestamps();
            });
        } else {
            Schema::table('hasil_analisis', function (Blueprint $table) {
                if (! Schema::hasColumn('hasil_analisis', 'wisata')) {
                    $table->string('wisata')->nullable()->after('id');
                }
                if (! Schema::hasColumn('hasil_analisis', 'ulasan_asli')) {
                    $table->text('ulasan_asli')->nullable()->after('wisata');
                }
                if (! Schema::hasColumn('hasil_analisis', 'ulasan_bersih')) {
                    $table->text('ulasan_bersih')->nullable()->after('ulasan_asli');
                }
                if (! Schema::hasColumn('hasil_analisis', 'hasil_preprocessing')) {
                    $table->text('hasil_preprocessing')->nullable()->after('ulasan_bersih');
                }
                if (! Schema::hasColumn('hasil_analisis', 'sentimen')) {
                    $table->string('sentimen')->nullable()->after('hasil_preprocessing');
                }
                if (! Schema::hasColumn('hasil_analisis', 'probabilitas')) {
                    $table->float('probabilitas')->nullable()->after('sentimen');
                }
                if (! Schema::hasColumn('hasil_analisis', 'periode_id')) {
                    $table->foreignId('periode_id')->nullable()->index()->after('probabilitas');
                }
            });
        }

        if (! Schema::hasTable('evaluasi_model')) {
            Schema::create('evaluasi_model', function (Blueprint $table) {
                $table->id();
                $table->float('precision')->default(0);
                $table->float('recall')->default(0);
                $table->float('f1_score')->default(0);
                $table->float('accuracy')->default(0);
                $table->integer('tp')->default(0);
                $table->integer('tn')->default(0);
                $table->integer('fp')->default(0);
                $table->integer('fn')->default(0);
                $table->foreignId('periode_id')->nullable()->index();
                $table->timestamps();
            });
        } else {
            Schema::table('evaluasi_model', function (Blueprint $table) {
                if (! Schema::hasColumn('evaluasi_model', 'precision')) {
                    $table->float('precision')->default(0);
                }
                if (! Schema::hasColumn('evaluasi_model', 'recall')) {
                    $table->float('recall')->default(0);
                }
                if (! Schema::hasColumn('evaluasi_model', 'f1_score')) {
                    $table->float('f1_score')->default(0);
                }
                if (! Schema::hasColumn('evaluasi_model', 'accuracy')) {
                    $table->float('accuracy')->default(0);
                }
                if (! Schema::hasColumn('evaluasi_model', 'tp')) {
                    $table->integer('tp')->default(0);
                }
                if (! Schema::hasColumn('evaluasi_model', 'tn')) {
                    $table->integer('tn')->default(0);
                }
                if (! Schema::hasColumn('evaluasi_model', 'fp')) {
                    $table->integer('fp')->default(0);
                }
                if (! Schema::hasColumn('evaluasi_model', 'fn')) {
                    $table->integer('fn')->default(0);
                }
                if (! Schema::hasColumn('evaluasi_model', 'periode_id')) {
                    $table->foreignId('periode_id')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally left non-destructive because this migration can patch existing data tables.
    }
};
