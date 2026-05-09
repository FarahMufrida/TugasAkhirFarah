<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilAnalisis;
use Illuminate\Support\Facades\DB;

class AnalisisController extends Controller
{
    public function index(Request $request)
    {
        // Ambil data evaluasi model
        $evaluasi = DB::table('evaluasi_model')->latest()->first();

        // Dropdown wisata
        $wisataList = HasilAnalisis::select('wisata')
            ->distinct()
            ->pluck('wisata');

        // Query hasil analisis dengan filter
        $query = HasilAnalisis::query();

        if ($request->filled('wisata')) {
            $query->where('wisata', $request->wisata);
        }

        $hasil = $query->latest()->paginate(10);

        return view('analisis.index', compact('hasil', 'wisataList', 'evaluasi'));
    }
}