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

        $mentah = $query->latest()->paginate(20)->withQueryString();

        // OPTIONAL
        $preprocessing = PreprocessingData::latest()->paginate(30, ['*'], 'pre_page');

        // 🔥 RINGKASAN DATA
        $totalUlasan = Ulasan::count();
        $totalWisata = Ulasan::distinct('wisata')->count('wisata');
        $lastUpdate = \App\Models\Ulasan::latest()->value('created_at');

        return view('ulasan.index', compact(
            'mentah',
            'preprocessing',
            'totalUlasan',
            'totalWisata',
            'lastUpdate'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx,xls|max:10240'
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'])) {

            $data = Excel::toArray(null, $file);
            $rows = $data[0] ?? [];
            unset($rows[0]);

            foreach ($rows as $row) {
                if (empty($row) || count($row) < 4) continue;

                DB::table('ulasan')->insert([
                    'wisata' => $row[0] ?? null,
                    'rating' => isset($row[1]) ? (float) $row[1] : 0,
                    'ulasan' => $row[2] ?? '',
                    'tanggal' => isset($row[3]) ? date('Y-m-d H:i:s', strtotime($row[3])) : now(),
                    'sentimen' => null,
                    'reviewer' => 'anonymous',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {

            $data = array_map(fn($line) => str_getcsv($line, ';'), file($file));
            unset($data[0]);

            foreach ($data as $row) {
                if (empty($row) || count($row) < 4) continue;

                DB::table('ulasan')->insert([
                    'wisata' => $row[0] ?? null,
                    'rating' => isset($row[1]) ? (float) $row[1] : 0,
                    'ulasan' => $row[2] ?? '',
                    'tanggal' => isset($row[3]) ? date('Y-m-d H:i:s', strtotime($row[3])) : now(),
                    'sentimen' => null,
                    'reviewer' => 'anonymous',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('ulasan.index')
            ->with('success', 'Upload berhasil dan data masuk!');
    }

    public function analisisData()
    {
        $pythonPath = '"C:\\Users\\Mufrida Farah\\AppData\\Local\\Programs\\Python\\Python312\\python.exe"';
        $scriptPath = '"C:\\laragon\\www\\Sentara\\scraper\\preprocessing.py"';

        shell_exec($pythonPath . " " . $scriptPath . " 2>&1");

        return redirect()->route('ulasan.index')
            ->with('success', 'Analisis berhasil dijalankan');
    }
}