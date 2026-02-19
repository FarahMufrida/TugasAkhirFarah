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
        // nanti scraping otomatis disini (sementara dummy)
        return redirect()->route('ulasan.index');
    }

    // Tombol analisis data
    public function analisisData()
    {
        // jalankan python naive bayes
        shell_exec("python sentimen.py");

        return redirect()->route('ulasan.index');
    }
}
