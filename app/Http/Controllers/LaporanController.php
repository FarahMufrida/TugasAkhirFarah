<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function downloadBulanan($periode)
    {
        $periodeData = DB::table('periode_analisis')
            ->where('id', $periode)
            ->first();

        if (!$periodeData) {
            return back()->with('error', 'Periode tidak ditemukan');
        }

        $data = $this->collectData($periode);

        $data['periode']      = $periodeData;
        $data['judulLaporan'] = 'Laporan Analisis Sentimen - ' . ($periodeData->nama ?? '-');
        $data['evaluasi']     = DB::table('evaluasi_model')->orderByDesc('id')->first()
                                ?? (object)['accuracy' => 0];

        // Ulasan KOTOR — dari tabel ulasan mentah
        $data['totalUlasanKotor'] = DB::table('ulasan')
            ->where('periode_id', $periode)
            ->count();

        // Trend by tanggal ULASAN (bukan created_at analisis)
        $data['trendHarian'] = DB::table('hasil_analisis')
            ->select(
                DB::raw('DATE(created_at) as tanggal'),     // ✅ ganti tanggal → created_at
                DB::raw("SUM(CASE WHEN sentimen='positif' THEN 1 ELSE 0 END) as positif"),
                DB::raw("SUM(CASE WHEN sentimen='netral'  THEN 1 ELSE 0 END) as netral"),
                DB::raw("SUM(CASE WHEN sentimen='negatif' THEN 1 ELSE 0 END) as negatif")
            )
            ->where('periode_id', $periode)
            ->groupBy(DB::raw('DATE(created_at)'))          // ✅ ganti tanggal → created_at
            ->orderBy(DB::raw('DATE(created_at)'))          // ✅ ganti tanggal → created_at
            ->get();

        return view('laporan.bulanan', $data);
    }

    public function downloadTahunan(Request $request)
    {
        $tahun = $request->tahun;

        $periodeList = DB::table('periode_analisis')
            ->where('tahun', $tahun)
            ->orderBy('bulan')
            ->get();

        if ($periodeList->isEmpty()) {
            return back()->with('error', 'Tidak ada data tahun tersebut');
        }

        return view('laporan.tahunan', compact('tahun', 'periodeList'));
    }

    private function collectData($periodeId)
    {
        $totalUlasan = DB::table('hasil_analisis')
            ->where('periode_id', $periodeId)
            ->count();

        $totalPositif = DB::table('hasil_analisis')
            ->where('periode_id', $periodeId)
            ->where('sentimen', 'positif')
            ->count();

        $totalNetral = DB::table('hasil_analisis')
            ->where('periode_id', $periodeId)
            ->where('sentimen', 'netral')
            ->count();

        $totalNegatif = DB::table('hasil_analisis')
            ->where('periode_id', $periodeId)
            ->where('sentimen', 'negatif')
            ->count();

        $persen = [
            'positif' => $totalUlasan ? round(($totalPositif / $totalUlasan) * 100, 2) : 0,
            'netral'  => $totalUlasan ? round(($totalNetral  / $totalUlasan) * 100, 2) : 0,
            'negatif' => $totalUlasan ? round(($totalNegatif / $totalUlasan) * 100, 2) : 0,
        ];

        $perWisata = DB::table('hasil_analisis')
            ->select(
                'wisata',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN sentimen='positif' THEN 1 ELSE 0 END) as positif"),
                DB::raw("SUM(CASE WHEN sentimen='netral'  THEN 1 ELSE 0 END) as netral"),
                DB::raw("SUM(CASE WHEN sentimen='negatif' THEN 1 ELSE 0 END) as negatif")
            )
            ->where('periode_id', $periodeId)
            ->groupBy('wisata')
            ->orderByDesc('positif')
            ->get();

        return [
            'totalUlasan'  => $totalUlasan,
            'totalPositif' => $totalPositif,
            'totalNetral'  => $totalNetral,
            'totalNegatif' => $totalNegatif,
            'persen'       => $persen,
            'perWisata'    => $perWisata,
        ];
    }
}