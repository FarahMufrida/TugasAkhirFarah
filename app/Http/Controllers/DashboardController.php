<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $wisata = $request->wisata;

        // =============================
        // TOTAL ULASAN (DARI TABEL ULASAN)
        // =============================
        $totalUlasan = DB::table('ulasan')
            ->when($wisata && $wisata != 'Semua Destinasi', function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->count();

        // =============================
        // CEK ADA ANALISIS ATAU BELUM
        // =============================
        $hasAnalisis = DB::table('hasil_analisis')->count() > 0;

        // =============================
        // HITUNG SENTIMEN (DARI HASIL ANALISIS)
        // =============================
        $positif = DB::table('hasil_analisis')
            ->where('sentimen', 'Positif')
            ->when($wisata && $wisata != 'Semua Destinasi', function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->count();

        $negatif = DB::table('hasil_analisis')
            ->where('sentimen', 'Negatif')
            ->when($wisata && $wisata != 'Semua Destinasi', function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->count();

        $netral = DB::table('hasil_analisis')
            ->where('sentimen', 'Netral')
            ->when($wisata && $wisata != 'Semua Destinasi', function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->count();

        // =============================
        // PERSENTASE SENTIMEN
        // =============================
        $totalSentimen = $positif + $negatif + $netral;

        $stats = [
            'total' => $totalUlasan,
            'positif_persen' => $totalSentimen > 0 ? round(($positif / $totalSentimen) * 100) : 0,
            'negatif_persen' => $totalSentimen > 0 ? round(($negatif / $totalSentimen) * 100) : 0,
            'netral_persen' => $totalSentimen > 0 ? round(($netral / $totalSentimen) * 100) : 0,
        ];

        // =============================
        // GRAFIK PIE (Distribusi Sentimen)
        // =============================
        $chartSentimen = $hasAnalisis
    ? DB::table('hasil_analisis')
        ->select('sentimen', DB::raw('count(*) as total'))
        ->when($wisata && $wisata != 'Semua Destinasi', function ($q) use ($wisata) {
            $q->where('wisata', $wisata);
        })
        ->groupBy('sentimen')
        ->orderByRaw("FIELD(sentimen, 'Positif', 'Negatif', 'Netral')") // 🔥 INI KUNCINYA
        ->get()
    : collect();

        // =============================
        // GRAFIK BAR (Sentimen per Destinasi)
        // =============================
        $chartDestinasi = $hasAnalisis
    ? DB::table('hasil_analisis')
        ->select(
            'wisata',
            DB::raw("SUM(CASE WHEN sentimen = 'Positif' THEN 1 ELSE 0 END) as positif"),
            DB::raw("SUM(CASE WHEN sentimen = 'Negatif' THEN 1 ELSE 0 END) as negatif"),
            DB::raw("SUM(CASE WHEN sentimen = 'Netral' THEN 1 ELSE 0 END) as netral")
        )
        ->when($wisata && $wisata != 'Semua Destinasi', function ($q) use ($wisata) {
            $q->where('wisata', $wisata);
        })
        ->groupBy('wisata')
    
        ->get()
    : collect();

        // =============================
        // TOTAL DATA PER DESTINASI (DARI ULASAN)
        // =============================
        $totalPerWisata = DB::table('ulasan')
            ->select('wisata', DB::raw('count(*) as total'))
            ->groupBy('wisata')
            ->get();

        // =============================
        // WORD CLOUD (DARI PREPROCESSING)
        // =============================
        $texts = DB::table('preprocessing_data')->pluck('stemming')->toArray();
        $allText = count($texts) > 0 ? implode(' ', $texts) : '';

        // =============================
        // LAST UPDATE
        // =============================
        $lastUpdate = DB::table('hasil_analisis')
            ->latest('created_at')
            ->value('created_at');

        return view('dashboard', compact(
            'stats',
            'chartSentimen',
            'chartDestinasi',
            'totalPerWisata',
            'allText',
            'lastUpdate',
            'hasAnalisis'
        ));
    }
}