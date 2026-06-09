<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function downloadBulanan($periode)
    {
        $periodeData = DB::table('periode_analisis')
            ->where('id', $periode)
            ->first();

        if (!$periodeData) {
            return back()->with('error', 'Periode tidak ditemukan');
        }

        $data = $this->collectData($periode);

        $data['periode'] = $periodeData;

        $data['judulLaporan'] =
            'Laporan Analisis Sentimen - ' .
            ($periodeData->nama ?? '-');

        $data['evaluasi'] =
            DB::table('evaluasi_model')
            ->where('periode_id', $periode)  
            ->orderByDesc('id')
            ->first()
            ?? (object)['accuracy' => 0];

        // TOTAL ULASAN KOTOR
        $data['totalUlasanKotor'] =
            DB::table('ulasan')
            ->where('periode_id', $periode)
            ->count();

        // TREND HARIAN
        $data['trendHarian'] =
            DB::table('hasil_analisis')
            ->select(
                DB::raw('DATE(created_at) as tanggal'),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='positif'
                    THEN 1 ELSE 0 END
                    ) as positif
                "),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='netral'
                    THEN 1 ELSE 0 END
                    ) as netral
                "),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='negatif'
                    THEN 1 ELSE 0 END
                    ) as negatif
                ")
            )
            ->where('periode_id', $periode)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get();

        return view(
            'laporan.bulanan',
            $data
        );
    }


    public function downloadTahunan(Request $request)
    {
        $tahun = $request->tahun;

        $periodeList =
            DB::table('periode_analisis')
            ->where('tahun', $tahun)
            ->orderBy('bulan')
            ->get();

        if ($periodeList->isEmpty()) {

            return back()->with(
                'error',
                'Tidak ada data tahun tersebut'
            );
        }

        $periodeIds =
            $periodeList->pluck('id');

        // TOTAL ULASAN
        $totalUlasan =
            DB::table('hasil_analisis')
            ->whereIn(
                'periode_id',
                $periodeIds
            )
            ->count();

        // TOTAL SENTIMEN
        $positif =
            DB::table('hasil_analisis')
            ->whereIn(
                'periode_id',
                $periodeIds
            )
            ->where(
                'sentimen',
                'positif'
            )
            ->count();

        $netral =
            DB::table('hasil_analisis')
            ->whereIn(
                'periode_id',
                $periodeIds
            )
            ->where(
                'sentimen',
                'netral'
            )
            ->count();

        $negatif =
            DB::table('hasil_analisis')
            ->whereIn(
                'periode_id',
                $periodeIds
            )
            ->where(
                'sentimen',
                'negatif'
            )
            ->count();

        // PERSEN POSITIF
        $persenPositif =
            $totalUlasan > 0
            ? round(
                ($positif / $totalUlasan) * 100,
                2
            )
            : 0;

        // AKURASI
        $akurasi =
            DB::table('evaluasi_model')
            ->whereIn('periode_id', $periodeIds)
            ->avg('accuracy') ?? 0;
          
            
        // TOTAL WISATA
        $totalWisata =
            DB::table('hasil_analisis')
            ->whereIn(
                'periode_id',
                $periodeIds
            )
            ->distinct()
            ->count('wisata');

        // REKAP BULANAN
        $rekapBulanan =
            DB::table('hasil_analisis as h')
            ->join(
                'periode_analisis as p',
                'h.periode_id',
                '=',
                'p.id'
            )

            ->select(

                'p.nama as bulan',

                DB::raw(
                    'COUNT(*) as total'
                ),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='positif'
                    THEN 1 ELSE 0 END
                    ) as positif
                "),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='netral'
                    THEN 1 ELSE 0 END
                    ) as netral
                "),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='negatif'
                    THEN 1 ELSE 0 END
                    ) as negatif
                ")
            )

            ->whereIn(
                'periode_id',
                $periodeIds
            )

            ->groupBy(
                'p.nama',
                'p.bulan'
            )

            ->orderBy(
                'p.bulan'
            )

            ->get();

        // WISATA POPULER
        $wisataPopuler =
            DB::table('hasil_analisis')

            ->select(

                'wisata',

                DB::raw(
                    'COUNT(*) as jumlah'
                ),

                DB::raw("
                    ROUND(
                        (
                            SUM(
                                CASE
                                WHEN sentimen='positif'
                                THEN 1 ELSE 0 END
                            )
                            /
                            COUNT(*)
                        ) * 100
                    ,2)
                    as persen
                ")
            )

            ->whereIn(
                'periode_id',
                $periodeIds
            )

            ->groupBy(
                'wisata'
            )

            ->orderByDesc(
                'persen'
            )

            ->limit(5)

            ->get();

        return view(

            'laporan.tahunan',

            compact(

                'tahun',
                'periodeList',
                'totalUlasan',
                'akurasi',
                'totalWisata',
                'rekapBulanan',
                'wisataPopuler',
                'positif',
                'netral',
                'negatif',
                'persenPositif'

            )
        );
    }


    private function collectData($periodeId)
    {
        $totalUlasan =
            DB::table('hasil_analisis')
            ->where(
                'periode_id',
                $periodeId
            )
            ->count();

        $totalPositif =
            DB::table('hasil_analisis')
            ->where(
                'periode_id',
                $periodeId
            )
            ->where(
                'sentimen',
                'positif'
            )
            ->count();

        $totalNetral =
            DB::table('hasil_analisis')
            ->where(
                'periode_id',
                $periodeId
            )
            ->where(
                'sentimen',
                'netral'
            )
            ->count();

        $totalNegatif =
            DB::table('hasil_analisis')
            ->where(
                'periode_id',
                $periodeId
            )
            ->where(
                'sentimen',
                'negatif'
            )
            ->count();

        $persen = [

            'positif' =>
                $totalUlasan
                ? round(
                    ($totalPositif /
                    $totalUlasan) * 100,
                    2
                )
                : 0,

            'netral' =>
                $totalUlasan
                ? round(
                    ($totalNetral /
                    $totalUlasan) * 100,
                    2
                )
                : 0,

            'negatif' =>
                $totalUlasan
                ? round(
                    ($totalNegatif /
                    $totalUlasan) * 100,
                    2
                )
                : 0,
        ];

        $perWisata =
            DB::table('hasil_analisis')

            ->select(

                'wisata',

                DB::raw(
                    'COUNT(*) as total'
                ),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='positif'
                    THEN 1 ELSE 0 END
                    ) as positif
                "),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='netral'
                    THEN 1 ELSE 0 END
                    ) as netral
                "),

                DB::raw("
                    SUM(
                    CASE
                    WHEN sentimen='negatif'
                    THEN 1 ELSE 0 END
                    ) as negatif
                ")
            )

            ->where(
                'periode_id',
                $periodeId
            )

            ->groupBy(
                'wisata'
            )

            ->orderByDesc(
                'positif'
            )

            ->get();

        return [

            'totalUlasan' =>
                $totalUlasan,

            'totalPositif' =>
                $totalPositif,

            'totalNetral' =>
                $totalNetral,

            'totalNegatif' =>
                $totalNegatif,

            'persen' =>
                $persen,

            'perWisata' =>
                $perWisata,
        ];
    }
}