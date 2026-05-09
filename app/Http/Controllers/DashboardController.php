<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private array $issueRules = [
        'Kebersihan' => [
            'keywords' => ['kotor', 'sampah', 'jorok', 'kumuh', 'toilet', 'wc', 'kamar mandi', 'limbah', 'bau', 'bersih'],
            'color'    => 'red',
            'icon'     => '🧹',
            'saran'    => [
                'actions' => ['Tambah tempat sampah', 'Jadwal pembersihan rutin', 'Petugas kebersihan tambahan'],
                'dampak'  => 'Kepuasan meningkat signifikan',
                'tip'     => 'Tambah tempat sampah & jadwal pembersihan rutin.',
            ],
        ],
        'Parkir' => [
            'keywords' => ['parkir', 'lahan parkir', 'tempat parkir', 'parkiran', 'motor', 'mobil'],
            'color'    => 'orange',
            'icon'     => '🚗',
            'saran'    => [
                'actions' => ['Penataan area parkir', 'Tarif transparan', 'Petugas lebih ramah'],
                'dampak'  => 'Mengurangi keluhan',
                'tip'     => 'Sistem parkir lebih rapi dan transparan.',
            ],
        ],
        'Harga / Tiket' => [
            'keywords' => ['mahal', 'harga', 'tiket', 'bayar', 'biaya', 'tarif', 'terjangkau'],
            'color'    => 'yellow',
            'icon'     => '🎟️',
            'saran'    => [
                'actions' => ['Evaluasi harga tiket', 'Promo wisata', 'Diskon tertentu'],
                'dampak'  => 'Daya tarik meningkat',
                'tip'     => 'Penyesuaian harga tiket agar lebih terjangkau.',
            ],
        ],
        'Fasilitas' => [
            'keywords' => ['fasilitas', 'gazebo', 'warung', 'kantin', 'kamar ganti', 'musholla', 'wifi', 'bangku', 'kursi'],
            'color'    => 'green',
            'icon'     => '🏗️',
            'saran'    => [
                'actions' => ['Perbaiki fasilitas rusak', 'Tambah fasilitas umum', 'Perawatan berkala'],
                'dampak'  => 'Kenyamanan pengunjung meningkat',
                'tip'     => 'Fasilitas lengkap & terawat meningkatkan kepuasan.',
            ],
        ],
        'Keramaian' => [
            'keywords' => ['ramai', 'macet', 'antri', 'sesak', 'penuh', 'padat', 'berdesakan'],
            'color'    => 'blue',
            'icon'     => '👥',
            'saran'    => [
                'actions' => ['Atur kapasitas pengunjung', 'Sistem antrean', 'Jam kunjungan fleksibel'],
                'dampak'  => 'Pengalaman pengunjung lebih nyaman',
                'tip'     => 'Manajemen kapasitas pengunjung lebih baik.',
            ],
        ],
    ];

   public function index(Request $request)
{
    $wisata    = $request->wisata;
    $destinasi = $request->destinasi;

    // ================================================================
    // AMBIL SEMUA PERIODE & PERIODE AKTIF
    // ================================================================
    $periodeList   = DB::table('periode_analisis')->orderBy('id', 'desc')->get();
    $periodeAktif  = $request->periode_id
        ? DB::table('periode_analisis')->find($request->periode_id)
        : DB::table('periode_analisis')->orderBy('id', 'desc')->first();
    $periodeId     = $periodeAktif->id ?? null;

    $evaluasi = DB::table('evaluasi_model')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->latest()->first();

    // ================================================================
    // TOTAL ULASAN
    // ================================================================
    $totalUlasan = DB::table('ulasan')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($wisata && $wisata != 'Semua Destinasi', fn($q) => $q->where('wisata', $wisata))
        ->count();

    // ================================================================
    // CEK ADA ANALISIS ATAU BELUM
    // ================================================================
    $hasAnalisis = DB::table('hasil_analisis')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->count() > 0;

    // ================================================================
    // HITUNG SENTIMEN
    // ================================================================
    $positif = DB::table('hasil_analisis')
        ->where('sentimen', 'Positif')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($wisata && $wisata != 'Semua Destinasi', fn($q) => $q->where('wisata', $wisata))
        ->count();

    $negatif = DB::table('hasil_analisis')
        ->where('sentimen', 'Negatif')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($wisata && $wisata != 'Semua Destinasi', fn($q) => $q->where('wisata', $wisata))
        ->count();

    $netral = DB::table('hasil_analisis')
        ->where('sentimen', 'Netral')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($wisata && $wisata != 'Semua Destinasi', fn($q) => $q->where('wisata', $wisata))
        ->count();

    $totalSentimen = $positif + $negatif + $netral;

    $stats = [
        'total'          => $totalUlasan,
        'positif_persen' => $totalSentimen > 0 ? round(($positif / $totalSentimen) * 100) : 0,
        'negatif_persen' => $totalSentimen > 0 ? round(($negatif / $totalSentimen) * 100) : 0,
        'netral_persen'  => $totalSentimen > 0 ? round(($netral  / $totalSentimen) * 100) : 0,
    ];

    // ================================================================
    // CHART DATA
    // ================================================================
    $chartSentimen = $hasAnalisis
        ? DB::table('hasil_analisis')
            ->select('sentimen', DB::raw('count(*) as total'))
            ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->when($wisata && $wisata != 'Semua Destinasi', fn($q) => $q->where('wisata', $wisata))
            ->groupBy('sentimen')
            ->orderByRaw("FIELD(sentimen, 'Positif', 'Negatif', 'Netral')")
            ->get()
        : collect();

    $chartDestinasi = $hasAnalisis
        ? DB::table('hasil_analisis')
            ->select(
                'wisata',
                DB::raw("SUM(CASE WHEN sentimen = 'Positif' THEN 1 ELSE 0 END) as positif"),
                DB::raw("SUM(CASE WHEN sentimen = 'Negatif' THEN 1 ELSE 0 END) as negatif"),
                DB::raw("SUM(CASE WHEN sentimen = 'Netral'  THEN 1 ELSE 0 END) as netral")
            )
            ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->when($wisata && $wisata != 'Semua Destinasi', fn($q) => $q->where('wisata', $wisata))
            ->groupBy('wisata')
            ->get()
        : collect();

    // ================================================================
    // TOTAL PER DESTINASI + WORD CLOUD + LAST UPDATE
    // ================================================================
    $totalPerWisata = DB::table('ulasan')
        ->select('wisata', DB::raw('count(*) as total'))
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->groupBy('wisata')
        ->get();

    $texts   = DB::table('hasil_analisis')
    ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
    ->pluck('ulasan_bersih')
    ->toArray();
    $allText = count($texts) > 0 ? implode(' ', $texts) : '';

    $lastUpdate  = DB::table('hasil_analisis')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->latest('created_at')->value('created_at');

    $destinasiList = DB::table('ulasan')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->select('wisata')->distinct()->pluck('wisata');

    $hasil = DB::table('hasil_analisis')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($wisata && $wisata != 'Semua Destinasi', fn($q) => $q->where('wisata', $wisata))
        ->paginate(10);

    // ================================================================
    // REKOMENDASI
    // ================================================================
    $ulasanNegatif = DB::table('hasil_analisis')
        ->where('sentimen', 'Negatif')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($destinasi && $destinasi != 'Semua Destinasi', fn($q) => $q->where('wisata', $destinasi))
        ->pluck('ulasan_bersih')
        ->toArray();

    $textRek  = strtolower(implode(' ', $ulasanNegatif));
    $wordsRek = array_filter(preg_split('/\s+/', $textRek));

    $issueSkor = [];
    foreach ($this->issueRules as $kategori => $rule) {
        $skor = 0;
        foreach ($rule['keywords'] as $kw) {
            $skor += substr_count($textRek, $kw);
        }
        $issueSkor[$kategori] = $skor;
    }
    arsort($issueSkor);
    $totalSkorIsu = array_sum($issueSkor) ?: 1;

    $isuUtama = [];
    foreach (array_slice($issueSkor, 0, 5, true) as $nama => $skor) {
        $isuUtama[] = [
            'nama'   => $nama,
            'skor'   => $skor,
            'persen' => round(($skor / $totalSkorIsu) * 100),
            'color'  => $this->issueRules[$nama]['color'],
            'icon'   => $this->issueRules[$nama]['icon'],
        ];
    }

    $stopwords = [
        'yang','dan','di','ke','dari','untuk','dengan','ini','itu','tidak',
        'ada','juga','sangat','lebih','sudah','bisa','tapi','karena','pada',
        'akan','atau','saya','kami','mereka','nya','ga','gak','udah','pas',
        'kita','sih','deh','aja','ya','yg','tp','bgt','jadi','biar','kalau',
    ];
    $freq = array_count_values($wordsRek);
    foreach ($stopwords as $sw) { unset($freq[$sw]); }
    foreach (array_keys($freq) as $w) {
        if (mb_strlen($w) < 3) unset($freq[$w]);
    }
    arsort($freq);
    $kataDominan = array_slice($freq, 0, 10, true);

    $saranPerbaikan = [];
    foreach (array_slice($issueSkor, 0, 3, true) as $nama => $skor) {
        $saranPerbaikan[] = [
            'nama' => 'Perbaikan ' . $nama,
            'tip'  => $this->issueRules[$nama]['saran']['tip'],
            'icon' => $this->issueRules[$nama]['icon'],
        ];
    }

    $prioritas = [];
    $rank = 1;
    foreach (array_slice($issueSkor, 0, 3, true) as $nama => $skor) {
        $prioritas[] = [
            'rank'    => $rank++,
            'nama'    => $nama,
            'icon'    => $this->issueRules[$nama]['icon'],
            'actions' => $this->issueRules[$nama]['saran']['actions'],
            'dampak'  => $this->issueRules[$nama]['saran']['dampak'],
            'color'   => $this->issueRules[$nama]['color'],
        ];
    }

    $positifRek = DB::table('hasil_analisis')
        ->where('sentimen', 'Positif')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($destinasi && $destinasi != 'Semua Destinasi', fn($q) => $q->where('wisata', $destinasi))
        ->count();

    $negatifRek = DB::table('hasil_analisis')
        ->where('sentimen', 'Negatif')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($destinasi && $destinasi != 'Semua Destinasi', fn($q) => $q->where('wisata', $destinasi))
        ->count();

    $totalSentimenRek = DB::table('hasil_analisis')
        ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
        ->when($destinasi && $destinasi != 'Semua Destinasi', fn($q) => $q->where('wisata', $destinasi))
        ->count() ?: 1;

    $totalNegatif    = $negatifRek;
    $persenNegatif   = round(($negatifRek / $totalSentimenRek) * 100, 2);
    $tingkatKepuasan = round(($positifRek / $totalSentimenRek) * 100);
    $labelKepuasan   = match(true) {
        $tingkatKepuasan >= 80 => 'Baik',
        $tingkatKepuasan >= 60 => 'Sedang',
        default                => 'Kurang',
    };
    $isuDominan       = $isuUtama[0]['nama']   ?? '-';
    $isuDominanPersen = $isuUtama[0]['persen'] ?? 0;

    // ================================================================
    // RETURN VIEW
    // ================================================================
    return view('dashboard', compact(
        'stats',
        'chartSentimen',
        'chartDestinasi',
        'totalPerWisata',
        'allText',
        'lastUpdate',
        'hasAnalisis',
        'destinasiList',
        'hasil',
        'evaluasi',
        'totalNegatif',
        'persenNegatif',
        'tingkatKepuasan',
        'labelKepuasan',
        'isuUtama',
        'isuDominan',
        'isuDominanPersen',
        'kataDominan',
        'saranPerbaikan',
        'prioritas',
        'periodeList',    // ← tambahan
        'periodeAktif',   // ← tambahan
    ));
}
}