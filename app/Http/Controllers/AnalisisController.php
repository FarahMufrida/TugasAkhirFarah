<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilAnalisis;
use Illuminate\Support\Facades\DB;

class AnalisisController extends Controller
{
    public function index(Request $request)
    {
        $periodeList = DB::table('periode_analisis as p')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('hasil_analisis as h')
                    ->whereColumn('h.periode_id', 'p.id');
            })
            ->orderBy('p.id', 'desc')
            ->select('p.*')
            ->get();

        $availablePeriodeIds = $periodeList->pluck('id')->map(fn($id) => (string) $id);
        $periodeAktif = $request->periode_id && $availablePeriodeIds->contains((string) $request->periode_id)
            ? $periodeList->firstWhere('id', (int) $request->periode_id)
            : $periodeList->first();
        $periodeId = $periodeAktif->id ?? null;

        // Evaluasi ini berbasis pseudo-label dari rating/rule otomatis.
        $evaluasi = DB::table('evaluasi_model')
            ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->latest()
            ->first();

        $jumlahKelasAnalisis = DB::table('hasil_analisis')
            ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->distinct('sentimen')
            ->count('sentimen');

        $totalHasilAnalisis = DB::table('hasil_analisis')
            ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->count();

        // Dropdown wisata
        $wisataList = HasilAnalisis::select('wisata')
            ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->distinct()
            ->pluck('wisata');

        // Query hasil analisis dengan filter
        $query = HasilAnalisis::query();
        $query->when($periodeId, fn($q) => $q->where('periode_id', $periodeId));

        if ($request->filled('wisata')) {
            $query->where('wisata', $request->wisata);
        }

        $hasil = $query->orderBy('id', 'desc')->paginate(10);
        $standalone = true;

        return view('analisis.index', compact(
            'hasil',
            'wisataList',
            'evaluasi',
            'standalone',
            'jumlahKelasAnalisis',
            'totalHasilAnalisis'
        ));
    }
}