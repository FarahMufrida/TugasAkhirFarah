<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilAnalisis;

class AnalisisController extends Controller
{
    public function index(Request $request)
    {
        // Dropdown list destinasi unik
        $wisataList = HasilAnalisis::select('wisata')
            ->distinct()
            ->pluck('wisata');

        // Query utama tabel
        $query = HasilAnalisis::query();

        // Filter kalau user pilih destinasi tertentu
        if ($request->filled('wisata')) {
            $query->where('wisata', $request->wisata);
        }

        // Data hasil analisis (pakai paginate biar rapi)
        $hasil = $query->latest()->paginate(5);

        return view('analisis.index', compact(
            'hasil',
            'wisataList'
        ));
    }
}
