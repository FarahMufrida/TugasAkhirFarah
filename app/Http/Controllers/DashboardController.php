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
        // TOTAL DATA
        // =============================
        $total = DB::table('hasil_analisis')
            ->when($wisata, function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->count();

        // =============================
        // HITUNG SENTIMEN
        // =============================
        $positif = DB::table('hasil_analisis')
            ->where('sentimen', 'Positif')
            ->when($wisata, function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->count();

        $negatif = DB::table('hasil_analisis')
            ->where('sentimen', 'Negatif')
            ->when($wisata, function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->count();

        $netral = DB::table('hasil_analisis')
            ->where('sentimen', 'Netral')
            ->when($wisata, function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->count();

        // =============================
        // PERSENTASE
        // =============================
        $stats = [
            'total' => $total,
            'positif_persen' => $total ? round(($positif / $total) * 100) : 0,
            'negatif_persen' => $total ? round(($negatif / $total) * 100) : 0,
            'netral_persen' => $total ? round(($netral / $total) * 100) : 0,
        ];

        // =============================
        // GRAFIK PIE (Distribusi Sentimen)
        // =============================
        $chartSentimen = DB::table('hasil_analisis')
            ->select('sentimen', DB::raw('count(*) as total'))
            ->when($wisata, function ($q) use ($wisata) {
                $q->where('wisata', $wisata);
            })
            ->groupBy('sentimen')
            ->get();

        // =============================
        // GRAFIK BAR (per destinasi)
        // =============================
        $chartDestinasi = DB::table('hasil_analisis')
            ->select(
                'wisata',
                DB::raw("SUM(CASE WHEN sentimen = 'Positif' THEN 1 ELSE 0 END) as positif"),
                DB::raw("SUM(CASE WHEN sentimen = 'Negatif' THEN 1 ELSE 0 END) as negatif"),
                DB::raw("SUM(CASE WHEN sentimen = 'Netral' THEN 1 ELSE 0 END) as netral")
            )
            ->groupBy('wisata')
            ->get();

        // =============================
        // TOTAL DATA PER DESTINASI
        // =============================
        $totalPerWisata = DB::table('hasil_analisis')
        ->select('wisata', DB::raw('count(*) as total'))
        ->groupBy('wisata')
        ->get()
        ->toArray();

        // =============================
        // WORD CLOUD
        // =============================
        $texts = DB::table('preprocessing_data')
            ->pluck('stemming')
            ->toArray(); // 🔥 WAJIB

        $allText = implode(' ', $texts);

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
            'lastUpdate'
        ));
    }
}