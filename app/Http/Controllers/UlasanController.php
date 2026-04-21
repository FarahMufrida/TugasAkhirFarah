<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ulasan;
use App\Models\PreprocessingData;

class UlasanController extends Controller
{
    public function index(Request $request)
    {
        $wisata = $request->wisata;

        $query = Ulasan::query();
        if ($request->filled('search')) {
         $query->where('ulasan', 'LIKE', '%' . $request->search . '%');
}

        if ($wisata) {
            $query->where('wisata', 'LIKE', '%' . $wisata . '%');
        }

        // ✅ PAGINATION 50 DATA
        $mentah = $query->latest()->paginate(50)->withQueryString();

        // preprocessing
        $preprocessing = PreprocessingData::latest()->paginate(30, ['*'], 'pre_page');

        return view('ulasan.index', compact('mentah', 'preprocessing'));
    }

    public function ambilData()
    {
        $pythonPath = "C:\\Users\\Mufrida Farah\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
        $scriptPath = base_path('scraper/scraping_pipeline.py');

        shell_exec($pythonPath . " " . $scriptPath);

        return redirect()->route('ulasan.index')
            ->with('success','Data berhasil diambil');
    }

   public function analisisData()
{
    $pythonPath = '"C:\\Users\\Mufrida Farah\\AppData\\Local\\Programs\\Python\\Python312\\python.exe"';
    $scriptPath = '"C:\\laragon\\www\\Sentara\\scraper\\preprocessing.py"';

    $output = shell_exec($pythonPath . " " . $scriptPath . " 2>&1");

    return redirect()->route('ulasan.index')
        ->with('success','Analisis berhasil');
}
}