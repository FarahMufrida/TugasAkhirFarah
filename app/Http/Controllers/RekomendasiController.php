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
            'keywords' => ['mahal', 'harga', 'tiket', 'bayar', 'biaya', 'tarif', 'murah', 'terjangkau'],
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
        
    ];

    public function index(Request $request)
    {
        // --- 1. Dropdown destinasi ---
        $destinasiList = DB::table('hasil_analisis')
            ->select('wisata')
            ->distinct()
            ->pluck('wisata');

        // --- 2. Query dengan filter destinasi ---
        $queryAll    = HasilAnalisis::query();
        $queryFilter = HasilAnalisis::query();

        if ($request->filled('destinasi')) {
            $queryFilter->where('wisata', $request->destinasi);
        }

        // --- 3. Hitung total & sentimen ---
        $totalUlasan   = $queryFilter->count();
        $totalNegatif  = (clone $queryFilter)->where('sentimen', 'negatif')->count();
        $totalPositif  = (clone $queryFilter)->where('sentimen', 'positif')->count();
        $totalNetral   = (clone $queryFilter)->where('sentimen', 'netral')->count();

        $persenNegatif     = $totalUlasan > 0 ? round(($totalNegatif / $totalUlasan) * 100, 2) : 0;
        $tingkatKepuasan   = $totalUlasan > 0 ? round(($totalPositif / $totalUlasan) * 100) : 0;

        // Label kepuasan
        $labelKepuasan = match(true) {
            $tingkatKepuasan >= 80 => 'Baik',
            $tingkatKepuasan >= 60 => 'Sedang',
            default                => 'Kurang',
        };

        // --- 4. Ambil ulasan negatif & bersihkan teks ---
        $ulasanNegatif = (clone $queryFilter)
            ->where('sentimen', 'negatif')
            ->pluck('ulasan_bersih')   // gunakan kolom yang sudah dipreproses
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

        // --- 7. Isu dominan (ranking 1) ---
        $isuDominan     = $isuUtama[0]['nama']   ?? 'Belum ada isu';
        $isuDominanPersen = $isuUtama[0]['persen'] ?? 0;

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

        // --- 11. Filter destinasi yang sedang aktif ---
        $destinasiAktif = $request->input('destinasi', 'Semua Destinasi');

        return view('rekomendasi.index', compact(
            'destinasiList',
            'destinasiAktif',
            'totalUlasan',
            'totalNegatif',
            'totalPositif',
            'totalNetral',
            'persenNegatif',
            'tingkatKepuasan',
            'labelKepuasan',
            'isuUtama',
            'isuDominan',
            'isuDominanPersen',
            'kataDominan',
            'saranPerbaikan',
            'prioritas',
        ));
    }
}