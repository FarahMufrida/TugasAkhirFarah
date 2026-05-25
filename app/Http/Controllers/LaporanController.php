<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    // ─────────────────────────────────────────────
    // Laporan BULANAN  →  /laporan/bulanan?periode_id=X
    // ─────────────────────────────────────────────
    public function downloadBulanan(Request $request, $periode)
    {
       $periodeId = $periode;

        // Ambil info periode
        $periode = DB::table('periode_analisis')->where('id', $periodeId)->first();
        if (!$periode) {
            return back()->with('error', 'Periode tidak ditemukan.');
        }

        $data = $this->collectData($periodeId);
        $data['periode'] = $periode;
        $data['judulLaporan'] = 'Laporan Analisis Sentimen — ' . $periode->nama;

        $pdf = Pdf::loadView('laporan.bulanan', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions(['dpi' => 110, 'defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true]);

        $filename = 'laporan-sentimen-' . strtolower(str_replace(' ', '-', $periode->nama)) . '.pdf';

        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────────
    // Laporan TAHUNAN  →  /laporan/tahunan?tahun=2025
    // ─────────────────────────────────────────────
    public function downloadTahunan(Request $request, $tahun)
    {
        $tahun = $request->input('tahun', now()->year);

        $periodeList = DB::table('periode_analisis')
            ->where('tahun', $tahun)
            ->orderBy('bulan')
            ->get();

        if ($periodeList->isEmpty()) {
            return back()->with('error', "Tidak ada data untuk tahun $tahun.");
        }

        // Kumpulkan data per bulan
        $dataBulanan = [];
        foreach ($periodeList as $periode) {
            $dataBulanan[] = array_merge(
                ['periode' => $periode],
                $this->collectData($periode->id)
            );
        }

        // Agregat tahunan
        $totalUlasan   = collect($dataBulanan)->sum(fn($d) => $d['totalUlasan']);
        $totalPositif  = collect($dataBulanan)->sum(fn($d) => $d['totalPositif']);
        $totalNegatif  = collect($dataBulanan)->sum(fn($d) => $d['totalNegatif']);
        $totalNetral   = collect($dataBulanan)->sum(fn($d) => $d['totalNetral']);

        $pdf = Pdf::loadView('laporan.tahunan', [
            'tahun'         => $tahun,
            'judulLaporan'  => "Laporan Analisis Sentimen Tahunan — $tahun",
            'dataBulanan'   => $dataBulanan,
            'totalUlasan'   => $totalUlasan,
            'totalPositif'  => $totalPositif,
            'totalNegatif'  => $totalNegatif,
            'totalNetral'   => $totalNetral,
            'persen' => $totalUlasan > 0 ? [
                'positif' => round(($totalPositif / $totalUlasan) * 100, 1),
                'negatif' => round(($totalNegatif / $totalUlasan) * 100, 1),
                'netral'  => round(($totalNetral  / $totalUlasan) * 100, 1),
            ] : ['positif' => 0, 'negatif' => 0, 'netral' => 0],
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions(['dpi' => 110, 'defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true]);

        return $pdf->download("laporan-sentimen-$tahun.pdf");
    }

    // ─────────────────────────────────────────────
    // Helper: kumpulkan semua data untuk satu periode
    // ─────────────────────────────────────────────
    private function collectData(int $periodeId): array
    {
        // Statistik sentimen
        $totalUlasan  = DB::table('hasil_analisis')->where('periode_id', $periodeId)->count();
        $totalPositif = DB::table('hasil_analisis')->where('periode_id', $periodeId)->where('sentimen', 'positif')->count();
        $totalNegatif = DB::table('hasil_analisis')->where('periode_id', $periodeId)->where('sentimen', 'negatif')->count();
        $totalNetral  = DB::table('hasil_analisis')->where('periode_id', $periodeId)->where('sentimen', 'netral')->count();

        $persen = $totalUlasan > 0 ? [
            'positif' => round(($totalPositif / $totalUlasan) * 100, 1),
            'negatif' => round(($totalNegatif / $totalUlasan) * 100, 1),
            'netral'  => round(($totalNetral  / $totalUlasan) * 100, 1),
        ] : ['positif' => 0, 'negatif' => 0, 'netral' => 0];

        // Evaluasi model
        $evaluasi = DB::table('evaluasi_model')
            ->where('periode_id', $periodeId)
            ->latest()
            ->first();

        // Per destinasi
        $perWisata = DB::table('hasil_analisis')
            ->where('periode_id', $periodeId)
            ->select('wisata',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN sentimen='positif' THEN 1 ELSE 0 END) as positif"),
                DB::raw("SUM(CASE WHEN sentimen='negatif' THEN 1 ELSE 0 END) as negatif"),
                DB::raw("SUM(CASE WHEN sentimen='netral'  THEN 1 ELSE 0 END) as netral")
            )
            ->groupBy('wisata')
            ->get();

        // Tabel hasil analisis (maks 200 baris agar PDF tidak terlalu besar)
        $hasilAnalisis = DB::table('hasil_analisis')
            ->where('periode_id', $periodeId)
            ->select('wisata', 'ulasan_asli', 'sentimen', 'probabilitas')
            ->orderBy('wisata')
            ->limit(200)
            ->get();

        // Rekomendasi (rule-based dari ulasan negatif)
        $ulasanNegatif = DB::table('hasil_analisis')
            ->where('periode_id', $periodeId)
            ->where('sentimen', 'negatif')
            ->pluck('ulasan_bersih')
            ->implode(' ');

        $rekomendasi = $this->generateRekomendasi($ulasanNegatif);

        return compact(
            'totalUlasan', 'totalPositif', 'totalNegatif', 'totalNetral',
            'persen', 'evaluasi', 'perWisata', 'hasilAnalisis', 'rekomendasi'
        );
    }

    // ─────────────────────────────────────────────
    // Rule-based rekomendasi (sama seperti RekomendasiController)
    // ─────────────────────────────────────────────
    private function generateRekomendasi(string $text): array
    {
        $issueRules = [
            'Kebersihan'    => ['keywords' => ['kotor','sampah','jorok','kumuh','toilet','wc','bau'],
                                'icon' => '🧹', 'tip' => 'Tambah tempat sampah & jadwal pembersihan rutin.'],
            'Aksesibilitas' => ['keywords' => ['parkir','parkiran','motor','mobil','jalan','macet'],
                                'icon' => '🚗', 'tip' => 'Sistem parkir lebih rapi dan transparan.'],
            'Harga / Tiket' => ['keywords' => ['mahal','harga','tiket','bayar','biaya','tarif'],
                                'icon' => '🎟️', 'tip' => 'Penyesuaian harga tiket agar lebih terjangkau.'],
            'Fasilitas'     => ['keywords' => ['fasilitas','gazebo','warung','kantin','musholla','wifi','bangku'],
                                'icon' => '🏗️', 'tip' => 'Fasilitas lengkap & terawat meningkatkan kepuasan.'],
        ];

        $result = [];
        foreach ($issueRules as $nama => $rule) {
            $skor = 0;
            foreach ($rule['keywords'] as $kw) {
                $skor += substr_count(strtolower($text), $kw);
            }
            if ($skor > 0) {
                $result[] = ['nama' => $nama, 'skor' => $skor, 'icon' => $rule['icon'], 'tip' => $rule['tip']];
            }
        }

        usort($result, fn($a, $b) => $b['skor'] - $a['skor']);
        return array_slice($result, 0, 5);
    }
}