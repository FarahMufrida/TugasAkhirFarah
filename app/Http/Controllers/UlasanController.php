<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ulasan;
// use App\Models\HasilSentimen;
use App\Models\PreprocessingData;


class UlasanController extends Controller
{
    // public function index()
    // {
    //     $mentah = Ulasan::where('is_processed', 0)->get();
    //     // $hasil  = HasilSentimen::latest()->get();

    //     // return view('ulasan.index', compact('mentah', 'hasil'));
    // }

public function index()
{
    // Data ulasan mentah (belum diproses)
    $mentah = Ulasan::all();

    // Data hasil preprocessing
    $preprocessing = PreprocessingData::latest()->get();

    return view('ulasan.index', compact('mentah', 'preprocessing'));
}


    // Tombol ambil data terbaru
  public function ambilData()
{
    $pythonPath = '"C:\\Users\\Mufrida Farah\\AppData\\Local\\Programs\\Python\\Python312\\python.exe"';
    $scriptPath = base_path('scraper/scraping_pipeline.py');

    $command = $pythonPath . " " . $scriptPath . " 2>&1";

    shell_exec($command);

    return redirect()->route('ulasan.index')
        ->with('success', 'Data berhasil diambil dari Google Maps');
}

    // Tombol analisis data
    public function analisisData()
{
    $pythonPath = '"C:\\Users\\Mufrida Farah\\AppData\\Local\\Programs\\Python\\Python312\\python.exe"';

    $scriptPath = base_path('scraper/preprocessing.py');

    $command = $pythonPath . " " . $scriptPath . " 2>&1";

    shell_exec($command);

    return redirect()->route('ulasan.index')
        ->with('success','Preprocessing berhasil dilakukan');
}
}
