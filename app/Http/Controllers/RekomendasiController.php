<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilAnalisis;

class RekomendasiController extends Controller
{
    public function index(Request $request)
    {
        // dropdown destinasi unik
        $destinasiList = HasilAnalisis::select('nama_wisata')
            ->distinct()
            ->pluck('nama_wisata');

        // query utama
        $query = HasilAnalisis::query();

        // filter destinasi jika dipilih
        if ($request->filled('destinasi')) {
            $query->where('nama_wisata', $request->destinasi);
        }

        // ambil ulasan negatif
        $negatif = $query->where('sentimen', 'negatif')
            ->pluck('ulasan_terolah')
            ->toArray();

        // gabungkan semua ulasan jadi 1 string
        $text = strtolower(implode(" ", $negatif));

        // token kata sederhana
        $words = array_filter(explode(" ", $text));

        // hitung frekuensi kata
        $freq = array_count_values($words);

        // urutkan kata paling sering
        arsort($freq);

        // ambil top 5 keyword
        $topKeywords = array_slice(array_keys($freq), 0, 5);

        // buat output card
        $isuUtama = $topKeywords[0] ?? "Belum ada isu";
        $kataDominan = implode(", ", $topKeywords);

        // saran otomatis sederhana
        $saran = "Perlu peningkatan layanan terkait: " . $isuUtama;

        return view('rekomendasi.index', compact(
            'destinasiList',
            'isuUtama',
            'kataDominan',
            'saran'
        ));
    }
}
