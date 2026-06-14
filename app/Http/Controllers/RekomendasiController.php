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
        'Parkir' => [
            'keywords' => [
                // FIX: hapus 'motor', 'mobil', 'kendaraan' — terlalu ambigu,
                // sering muncul dalam konteks "harga tiket kendaraan" bukan keluhan parkir.
                // Hanya gunakan frasa yang secara spesifik merujuk pada area/masalah parkir.
                'parkir', 'lahan parkir', 'tempat parkir', 'parkiran',
                'bayar parkir', 'tarif parkir', 'biaya parkir',
                'jukir', 'tukang parkir', 'penjaga parkir', 'petugas parkir',
                'palak parkir', 'pungli parkir',
            ],
            'color'    => 'orange',
            'icon'     => '🚗',
            'saran'    => [
                'actions' => ['Penataan area parkir', 'Tarif transparan', 'Petugas lebih ramah'],
                'dampak'  => 'Mengurangi keluhan',
                'tip'     => 'Sistem parkir lebih rapi dan tarif jelas.',
            ],
        ],
        'Harga / Tiket' => [
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
        'Keramaian' => [
            'keywords' => ['ramai', 'antri', 'penuh', 'sesak', 'macet', 'padat', 'berdesakan', 'keramaian', 'pengunjung banyak'],
            'color'    => 'blue',
            'icon'     => '👥',
            'saran'    => [
                'actions' => ['Terapkan sistem reservasi', 'Batasi kapasitas pengunjung', 'Tambah jalur masuk'],
                'dampak'  => 'Kenyamanan kunjungan meningkat',
                'tip'     => 'Manajemen kapasitas pengunjung lebih baik.',
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

        // --- 2. Resolve periode ---
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

        if (!$periodeId) {
            $periodeRow = DB::table('periode_analisis')
                ->where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->first();
            $periodeId = $periodeRow?->id;
        }

        // --- 3. Query builder ---
        $queryAll    = HasilAnalisis::query();
        $queryFilter = HasilAnalisis::query();

        if ($periodeId) {
            $queryAll->where('periode_id', $periodeId);
            $queryFilter->where('periode_id', $periodeId);
        }

        if ($request->filled('destinasi')) {
            $queryFilter->where('wisata', $request->destinasi);
        }

        // --- 4. Hitung total & sentimen ---
        $totalUlasan  = $queryFilter->count();
        $totalNegatif = (clone $queryFilter)->where('sentimen', 'negatif')->count();
        $totalPositif = (clone $queryFilter)->where('sentimen', 'positif')->count();
        $totalNetral  = (clone $queryFilter)->where('sentimen', 'netral')->count();

        $persenNegatif   = $totalUlasan > 0 ? round(($totalNegatif / $totalUlasan) * 100, 2) : 0;
        $persenPositif   = $totalUlasan > 0 ? round(($totalPositif / $totalUlasan) * 100) : 0;
        $persenNetral    = $totalUlasan > 0 ? round(($totalNetral  / $totalUlasan) * 100) : 0;
        $tingkatKepuasan = $persenPositif;

        // --- 5. Statistik destinasi & periode ---
        $totalDestinasiAnalisis   = (clone $queryAll)->distinct('wisata')->count('wisata');
        $totalDestinasiBerkeluhan = (clone $queryAll)->where('sentimen', 'negatif')
            ->distinct('wisata')->count('wisata');

        $periodeAktif = $periodeId
            ? DB::table('periode_analisis')->find($periodeId)
            : null;

        $labelKepuasan = match(true) {
            $tingkatKepuasan >= 80 => 'Baik',
            $tingkatKepuasan >= 60 => 'Sedang',
            default                => 'Kurang',
        };

        // =========================================================
        // PERBAIKAN UTAMA: Hitung isu per ulasan (bukan per keyword)
        // Logika: 1 ulasan dihitung 1x untuk setiap kategori yang
        // keyword-nya ditemukan dalam teks ulasan tersebut.
        // Persentase = jumlah_ulasan_mengandung_kategori / total_ulasan_sentimen × 100
        // =========================================================

        $isuNegatif = $this->hitungIsuPerUlasan(
            (clone $queryFilter)->where('sentimen', 'negatif')->get(['ulasan_bersih', 'ulasan_asli']),
            $totalNegatif
        );

        $isuNetral = $this->hitungIsuPerUlasan(
            (clone $queryFilter)->where('sentimen', 'netral')->get(['ulasan_bersih', 'ulasan_asli']),
            $totalNetral
        );

        $isuPositif = $this->hitungIsuPerUlasan(
            (clone $queryFilter)->where('sentimen', 'positif')->get(['ulasan_bersih', 'ulasan_asli']),
            $totalPositif
        );

        // isuUtama = sama dengan isuNegatif (untuk kompatibilitas variabel lama)
        $isuUtama = $isuNegatif;

        // --- 6. Isu dominan & prioritas rekomendasi ---
        if (empty($isuNegatif) || ($isuNegatif[0]['jumlah'] ?? 0) === 0) {
            $isuDominan       = 'Tidak ada keluhan';
            $isuDominanPersen = 0;
            $prioritas        = [];
            $saranPerbaikan   = [];
        } else {
            $isuDominan       = $isuNegatif[0]['nama'];
            $isuDominanPersen = $isuNegatif[0]['persen'];

            // Prioritas: top 3 isu negatif berdasarkan jumlah ulasan
            $prioritas = [];
            $rank = 1;
            foreach (array_slice($isuNegatif, 0, 3) as $isu) {
                if ($isu['jumlah'] === 0) break;
                $nama = $isu['nama'];
                $prioritas[] = [
                    'rank'    => $rank++,
                    'nama'    => $nama,
                    'icon'    => $this->issueRules[$nama]['icon'],
                    'actions' => $this->issueRules[$nama]['saran']['actions'],
                    'dampak'  => $this->issueRules[$nama]['saran']['dampak'],
                    'color'   => $this->issueRules[$nama]['color'],
                    'jumlah'  => $isu['jumlah'],
                    'persen'  => $isu['persen'],
                ];
            }

            $saranPerbaikan = [];
            foreach (array_slice($isuNegatif, 0, 3) as $isu) {
                if ($isu['jumlah'] === 0) break;
                $nama = $isu['nama'];
                $saranPerbaikan[] = [
                    'nama' => 'Perbaikan ' . $nama,
                    'tip'  => $this->issueRules[$nama]['saran']['tip'],
                    'icon' => $this->issueRules[$nama]['icon'],
                ];
            }
        }

        // --- 7. Kata kunci dominan dari ulasan negatif ---
        $ulasanNegatifTeks = (clone $queryFilter)
            ->where('sentimen', 'negatif')
            ->get(['ulasan_bersih', 'ulasan_asli'])
            ->map(fn($r) => $r->ulasan_bersih ?? $r->ulasan_asli ?? '')
            ->filter()
            ->toArray();

        $textNegatif = strtolower(implode(' ', $ulasanNegatifTeks));
        $words       = array_filter(preg_split('/\s+/', $textNegatif));

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
        foreach (array_keys($freq) as $w) {
            if (mb_strlen($w) < 3) unset($freq[$w]);
        }
        arsort($freq);
        $kataDominan = array_slice($freq, 0, 10, true);

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

    /**
     * Hitung isu per ulasan — BENAR.
     *
     * Setiap ulasan hanya dihitung SATU KALI per kategori meski
     * keyword-nya muncul berkali-kali dalam satu ulasan yang sama.
     * Persentase = (jumlah ulasan mengandung kategori / total ulasan) × 100
     *
     * @param  \Illuminate\Support\Collection  $ulasanCollection  hasil ->get(['ulasan_bersih','ulasan_asli'])
     * @param  int  $totalUlasan  total ulasan pada sentimen yang sama
     * @return array  top-5 isu, sudah diurutkan dari tertinggi
     */
    private function hitungIsuPerUlasan($ulasanCollection, int $totalUlasan): array
    {
        // Siapkan teks tiap ulasan (fallback ke ulasan_asli jika ulasan_bersih kosong)
        $teksUlasan = $ulasanCollection
            ->map(fn($r) => strtolower($r->ulasan_bersih ?? $r->ulasan_asli ?? ''))
            ->filter()
            ->values();

        // Hitung berapa ulasan yang menyebut tiap kategori
        $jumlahPerKategori = [];
        foreach ($this->issueRules as $kategori => $rule) {
            $jumlah = 0;
            foreach ($teksUlasan as $teks) {
                foreach ($rule['keywords'] as $kw) {
                    if (str_contains($teks, $kw)) {
                        $jumlah++;
                        break; // 1 ulasan dihitung 1x per kategori
                    }
                }
            }
            $jumlahPerKategori[$kategori] = $jumlah;
        }

        // Urutkan dari terbanyak
        arsort($jumlahPerKategori);

        // Bangun array hasil top-5
        $hasil = [];
        foreach (array_slice($jumlahPerKategori, 0, 5, true) as $nama => $jumlah) {
            $persen = $totalUlasan > 0 ? round(($jumlah / $totalUlasan) * 100) : 0;
            $hasil[] = [
                'nama'   => $nama,
                'jumlah' => $jumlah,
                'persen' => $persen,
                'color'  => $this->issueRules[$nama]['color'],
                'icon'   => $this->issueRules[$nama]['icon'],
            ];
        }

        return $hasil;
    }
}