<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilAnalisis;

class AnalisisController extends Controller
{
    public function index(Request $request)
    {
        // Dropdown list destinasi unik
        $destinasiList = HasilAnalisis::select('nama_wisata')
            ->distinct()
            ->pluck('nama_wisata');

        // Query utama tabel
        $query = HasilAnalisis::query();

        // Filter kalau user pilih destinasi tertentu
        if ($request->filled('destinasi')) {
            $query->where('nama_wisata', $request->destinasi);
        }

        // Data hasil analisis (pakai paginate biar rapi)
        $hasil = $query->latest()->paginate(5);

        return view('analisis.index', compact(
            'hasil',
            'destinasiList'
        ));
    }
}
