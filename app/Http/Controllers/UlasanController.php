<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ulasan;
use App\Models\PreprocessingData;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UlasanController extends Controller
{
    public function index(Request $request)
    {
         $periodeList = DB::table('periode_analisis')->orderBy('id', 'desc')->get();
        $query = Ulasan::query();

        // 🔍 SEARCH (ulasan + wisata)
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('ulasan', 'LIKE', "%$search%")
                  ->orWhere('wisata', 'LIKE', "%$search%");
            });
        }

        // 🔍 FILTER WISATA
        if ($request->filled('wisata')) {
            $query->where('wisata', 'LIKE', '%' . $request->wisata . '%');
        }

        // ← filter periode
         if ($request->filled('periode_id')) {
        $query->where('periode_id', $request->periode_id);
        }

        $mentah = $query->orderBy('tanggal', 'desc')->paginate(20)->withQueryString();

        // OPTIONAL
        $preprocessing = PreprocessingData::latest()->paginate(20, ['*'], 'pre_page');

        // 🔥 RINGKASAN DATA
       
    $totalUlasan = Ulasan::when($request->filled('periode_id'), fn($q) => $q->where('periode_id', $request->periode_id))->count();
    $totalWisata = Ulasan::when($request->filled('periode_id'), fn($q) => $q->where('periode_id', $request->periode_id))->distinct('wisata')->count('wisata');
    $lastUpdate  = Ulasan::when($request->filled('periode_id'), fn($q) => $q->where('periode_id', $request->periode_id))->latest()->value('created_at');

    return view('ulasan.index', compact(
        'mentah',
        'totalUlasan',
        'totalWisata',
        'lastUpdate',
        'periodeList'
    ));
    }

   public function upload(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:csv,txt,xlsx,xls|max:10240'
    ]);

    // ✅ OTOMATIS BUAT / AMBIL PERIODE BULAN INI
    $bulan = now()->month;
    $tahun = now()->year;
    $namaBulan = now()->translatedFormat('F Y'); // contoh: "Mei 2026"

    $periode = DB::table('periode_analisis')
        ->where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->first();

    if (!$periode) {
        $periodeId = DB::table('periode_analisis')->insertGetId([
            'nama'       => $namaBulan,
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'created_at' => now(),
        ]);
    } else {
        $periodeId = $periode->id;
    }

    // ✅ PROSES FILE
    $file = $request->file('file');
    $extension = strtolower($file->getClientOriginalExtension());

    if (in_array($extension, ['xlsx', 'xls'])) {
        $data = Excel::toArray(null, $file);
        $rows = $data[0] ?? [];
        unset($rows[0]);

        foreach ($rows as $row) {
            if (empty($row) || count($row) < 4) continue;

            DB::table('ulasan')->insert([
                'wisata'      => $row[0] ?? null,
                'rating'      => isset($row[1]) ? (float) $row[1] : 0,
                'ulasan'      => $row[2] ?? '',
                'tanggal'     => isset($row[3]) ? date('Y-m-d H:i:s', strtotime($row[3])) : now(),
                'sentimen'    => null,
                'reviewer'    => 'anonymous',
                'periode_id'  => $periodeId,  // ← tag periode
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    } else {
        $data = array_map(fn($line) => str_getcsv($line, ';'), file($file));
        unset($data[0]);

        foreach ($data as $row) {
            if (empty($row) || count($row) < 4) continue;

            DB::table('ulasan')->insert([
                'wisata'      => $row[0] ?? null,
                'rating'      => isset($row[1]) ? (float) $row[1] : 0,
                'ulasan'      => $row[2] ?? '',
                'tanggal'     => isset($row[3]) ? date('Y-m-d H:i:s', strtotime($row[3])) : now(),
                'sentimen'    => null,
                'reviewer'    => 'anonymous',
                'periode_id'  => $periodeId,  // ← tag periode
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    return redirect()->route('ulasan.index')
        ->with('success', 'Upload berhasil! Data masuk ke periode ' . $namaBulan);
}

   public function analisisData()
{
    $pythonPath = "C:\\Users\\Mufrida Farah\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
    $scriptPath = base_path('scraper/analisis.py');

    $command = "\"$pythonPath\" \"$scriptPath\" 2>&1";
    $output = shell_exec($command);

    return redirect()->route('analisis.index')
        ->with('success', 'Analisis berhasil dijalankan');
}
}