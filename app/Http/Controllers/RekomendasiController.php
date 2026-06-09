<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilAnalisis;
use Illuminate\Support\Facades\DB;

class RekomendasiController extends Controller
{
    /**
     * Rule-based: mapping kategori isu ke keyword terkait
     */
    private array $issueRules = [
        'Kebersihan' => [
            'keywords' => ['kotor', 'sampah', 'bersih', 'jorok', 'kumuh', 'toilet', 'wc', 'kamar mandi', 'limbah', 'bau'],
            'color'    => 'red',
            'icon'     => '🧹',
            'saran'    => [
                'actions' => ['Tambah tempat sampah', 'Jadwal pembersihan rutin', 'Petugas kebersihan tambahan'],
                'dampak'  => 'Kepuasan meningkat signifikan',
                'tip'     => 'Tambah tempat sampah & jadwal pembersihan rutin.',
            ],
        ],
        'Aksesibilitas' => [
            // FIX: diperluas — sebelumnya hanya mencakup parkir,
            // sekarang mencakup akses jalan, transportasi, dan kemudahan mencapai lokasi
            'keywords' => [
                'parkir', 'lahan parkir', 'tempat parkir', 'parkiran', 'motor', 'mobil',
                'jalan', 'akses', 'transportasi', 'jalur', 'tanjakan',
                'turunan', 'sempit', 'jauh', 'susah', 'sulit', 'capek',
                'angkutan', 'ojek', 'kendaraan', 'macet',
            ],
            'color'    => 'orange',
            'icon'     => '🚗',
            'saran'    => [
                'actions' => ['Penataan area parkir', 'Perbaikan akses jalan', 'Penambahan transportasi umum'],
                'dampak'  => 'Mengurangi keluhan aksesibilitas',
                'tip'     => 'Sistem parkir lebih rapi dan akses jalan diperbaiki.',
            ],
        ],
        // FIX: nama diganti dari 'Harga / Tiket' → 'HTM'
        // agar konsisten dengan parameter TA (Harga Tiket Masuk)
        'HTM' => [
            'keywords' => [
                'mahal', 'harga', 'tiket', 'bayar', 'biaya', 'tarif',
                'murah', 'terjangkau', 'htm', 'retribusi', 'pungli',
            ],
            'color'    => 'yellow',
            'icon'     => '🎟️',
            'saran'    => [
                'actions' => ['Evaluasi harga tiket masuk', 'Buat paket promo wisata', 'Transparansi retribusi'],
                'dampak'  => 'Daya tarik wisatawan meningkat',
                'tip'     => 'Penyesuaian HTM agar lebih terjangkau dan transparan.',
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
    ];

    public function index(Request $request)
    {
        // --- 1. Dropdown destinasi ---
        $destinasiList = DB::table('hasil_analisis')
            ->select('wisata')
            ->distinct()
            ->pluck('wisata');

        // --- 2. Query dengan filter destinasi & periode ---
        $queryAll    = HasilAnalisis::query();
        $queryFilter = HasilAnalisis::query();

        // Resolve periode dari request (periode_bulan atau periode_id)
        $periodeId = null;
        if ($request->filled('periode_id')) {
            $periodeId = (int) $request->periode_id;
        } elseif ($request->filled('periode_bulan')) {
            [$tahunParam, $bulanParam] = explode('-', $request->periode_bulan);
            $periodeRow = DB::table('periode_analisis')
                ->where('bulan', (int) $bulanParam)
                ->where('tahun', (int) $tahunParam)
                ->first();
            $periodeId = $periodeRow?->id;
        }

        // Kalau tidak ada periode di request → pakai periode bulan ini
        if (!$periodeId) {
            $periodeRow = DB::table('periode_analisis')
                ->where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->first();
            $periodeId = $periodeRow?->id;
        }

        if ($periodeId) {
            $queryAll->where('periode_id', $periodeId);
            $queryFilter->where('periode_id', $periodeId);
        }

        if ($request->filled('destinasi')) {
            $queryFilter->where('wisata', $request->destinasi);
        }

        // --- 3. Hitung total & sentimen ---
        $totalUlasan   = $queryFilter->count();
        $totalNegatif  = (clone $queryFilter)->where('sentimen', 'negatif')->count();
        $totalPositif  = (clone $queryFilter)->where('sentimen', 'positif')->count();
        $totalNetral   = (clone $queryFilter)->where('sentimen', 'netral')->count();

        $persenNegatif     = $totalUlasan > 0 ? round(($totalNegatif / $totalUlasan) * 100, 2) : 0;
        $persenPositif     = $totalUlasan > 0 ? round(($totalPositif / $totalUlasan) * 100) : 0;
        $persenNetral      = $totalUlasan > 0 ? round(($totalNetral  / $totalUlasan) * 100) : 0;
        $tingkatKepuasan   = $persenPositif;

        // --- 3a. Statistik destinasi ---
        $totalDestinasiAnalisis   = $queryAll->distinct('wisata')->count('wisata');
        $totalDestinasiBerkeluhan = (clone $queryAll)->where('sentimen', 'negatif')
            ->distinct('wisata')->count('wisata');

        // --- 3b. Periode aktif ---
        $periodeAktif = $periodeId
            ? DB::table('periode_analisis')->find($periodeId)
            : null;

        // Label kepuasan
        $labelKepuasan = match(true) {
            $tingkatKepuasan >= 80 => 'Baik',
            $tingkatKepuasan >= 60 => 'Sedang',
            default                => 'Kurang',
        };

        // --- 4. Ambil ulasan negatif & bersihkan teks ---
        // FIX: fallback ke kolom 'ulasan' jika 'ulasan_bersih' tidak tersedia
        $ulasanNegatif = (clone $queryFilter)
            ->where('sentimen', 'negatif')
            ->get(['ulasan_bersih', 'ulasan'])
            ->map(fn($r) => $r->ulasan_bersih ?? $r->ulasan ?? '')
            ->filter()
            ->toArray();

        $text  = strtolower(implode(' ', $ulasanNegatif));
        $words = array_filter(preg_split('/\s+/', $text));

        // --- 5. Rule-based: hitung skor tiap kategori isu ---
        $issueSkor  = [];
        $issueWords = [];  // kata dominan per kategori

        foreach ($this->issueRules as $kategori => $rule) {
            $skor       = 0;
            $matchWords = [];

            foreach ($rule['keywords'] as $kw) {
                $count = substr_count($text, $kw);
                if ($count > 0) {
                    $skor += $count;
                    $matchWords[$kw] = $count;
                }
            }

            $issueSkor[$kategori]  = $skor;
            $issueWords[$kategori] = $matchWords;
        }

        // Urutkan berdasarkan skor tertinggi
        arsort($issueSkor);

        $totalSkorIsu = array_sum($issueSkor) ?: 1; // hindari division by zero

        // --- 6. Isu utama (top 5) dengan persentase ---
        $isuUtama = [];
        foreach (array_slice($issueSkor, 0, 5, true) as $nama => $skor) {
            $isuUtama[] = [
                'nama'    => $nama,
                'skor'    => $skor,
                'persen'  => round(($skor / $totalSkorIsu) * 100),
                'color'   => $this->issueRules[$nama]['color'],
                'icon'    => $this->issueRules[$nama]['icon'],
            ];
        }

        // --- 6a. Isu negatif (untuk kolom tabel negatif di tampilan) ---
        $isuNegatif = [];
        foreach (array_slice($issueSkor, 0, 5, true) as $nama => $skor) {
            $isuNegatif[] = [
                'nama'   => $nama,
                'skor'   => $skor,
                'persen' => round(($skor / $totalSkorIsu) * 100),
                'color'  => $this->issueRules[$nama]['color'],
                'icon'   => $this->issueRules[$nama]['icon'],
            ];
        }

        // --- 6b. Isu netral (rule-based dari ulasan netral) ---
        $ulasanNetral = (clone $queryFilter)
            ->where('sentimen', 'netral')
            ->get(['ulasan_bersih', 'ulasan'])
            ->map(fn($r) => $r->ulasan_bersih ?? $r->ulasan ?? '')
            ->filter()
            ->toArray();

        $textNetral  = strtolower(implode(' ', $ulasanNetral));
        $skorNetral  = [];
        foreach ($this->issueRules as $kategori => $rule) {
            $skor = 0;
            foreach ($rule['keywords'] as $kw) {
                $skor += substr_count($textNetral, $kw);
            }
            $skorNetral[$kategori] = $skor;
        }
        arsort($skorNetral);
        $totalSkorNetral = array_sum($skorNetral) ?: 1;

        $isuNetral = [];
        foreach (array_slice($skorNetral, 0, 5, true) as $nama => $skor) {
            $isuNetral[] = [
                'nama'   => $nama,
                'skor'   => $skor,
                'persen' => round(($skor / $totalSkorNetral) * 100),
                'color'  => $this->issueRules[$nama]['color'],
                'icon'   => $this->issueRules[$nama]['icon'],
            ];
        }

        // --- 6c. Isu positif (rule-based dari ulasan positif) ---
        $ulasanPositif = (clone $queryFilter)
            ->where('sentimen', 'positif')
            ->get(['ulasan_bersih', 'ulasan'])
            ->map(fn($r) => $r->ulasan_bersih ?? $r->ulasan ?? '')
            ->filter()
            ->toArray();

        $textPositif  = strtolower(implode(' ', $ulasanPositif));
        $skorPositif  = [];
        foreach ($this->issueRules as $kategori => $rule) {
            $skor = 0;
            foreach ($rule['keywords'] as $kw) {
                $skor += substr_count($textPositif, $kw);
            }
            $skorPositif[$kategori] = $skor;
        }
        arsort($skorPositif);
        $totalSkorPositif = array_sum($skorPositif) ?: 1;

        $isuPositif = [];
        foreach (array_slice($skorPositif, 0, 5, true) as $nama => $skor) {
            $isuPositif[] = [
                'nama'   => $nama,
                'skor'   => $skor,
                'persen' => round(($skor / $totalSkorPositif) * 100),
                'color'  => $this->issueRules[$nama]['color'],
                'icon'   => $this->issueRules[$nama]['icon'],
            ];
        }

        // --- 7. Isu dominan (ranking 1) ---
        // FIX: guard jika tidak ada ulasan negatif sama sekali
        if (empty($isuUtama) || $isuUtama[0]['skor'] === 0) {
            $isuDominan       = 'Tidak ada keluhan';
            $isuDominanPersen = 0;
            $prioritas        = [];
            $saranPerbaikan   = [];
        } else {
            $isuDominan       = $isuUtama[0]['nama'];
            $isuDominanPersen = $isuUtama[0]['persen'];

            // --- 9. Prioritas rekomendasi (top 3 isu) ---
            $prioritas = [];
            $rank       = 1;
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

            // --- 10. Saran perbaikan (top 3) ---
            $saranPerbaikan = [];
            foreach (array_slice($issueSkor, 0, 3, true) as $nama => $skor) {
                $saranPerbaikan[] = [
                    'nama' => 'Perbaikan ' . $nama,
                    'tip'  => $this->issueRules[$nama]['saran']['tip'],
                    'icon' => $this->issueRules[$nama]['icon'],
                ];
            }
        }

        // --- 8. Kata kunci dominan (global, top 10, filter stopword) ---
        $stopwords = [
            'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan',
            'ini', 'itu', 'tidak', 'ada', 'juga', 'sangat', 'lebih',
            'sudah', 'bisa', 'tapi', 'karena', 'pada', 'akan', 'atau',
            'saya', 'kami', 'mereka', 'nya', 'ga', 'gak', 'udah', 'pas',
            'kita', 'sih', 'deh', 'aja', 'ya', 'yg', 'tp', 'bgt',
        ];

        $freq = array_count_values($words);
        foreach ($stopwords as $sw) {
            unset($freq[$sw]);
        }
        // filter kata pendek (< 3 karakter)
        foreach (array_keys($freq) as $w) {
            if (mb_strlen($w) < 3) unset($freq[$w]);
        }

        arsort($freq);
        $kataDominan = array_slice($freq, 0, 10, true);

        // --- 11. Filter destinasi yang sedang aktif ---
        $destinasiAktif = $request->input('destinasi', 'Semua Destinasi');

        return view('rekomendasi.index', compact(
            'destinasiList',
            'destinasiAktif',
            'periodeId',
            'totalUlasan',
            'totalNegatif',
            'totalPositif',
            'totalNetral',
            'persenNegatif',
            'persenPositif',
            'persenNetral',
            'tingkatKepuasan',
            'labelKepuasan',
            'totalDestinasiAnalisis',
            'totalDestinasiBerkeluhan',
            'periodeAktif',
            'isuUtama',
            'isuNegatif',
            'isuNetral',
            'isuPositif',
            'isuDominan',
            'isuDominanPersen',
            'kataDominan',
            'saranPerbaikan',
            'prioritas',
        ));
    }
}