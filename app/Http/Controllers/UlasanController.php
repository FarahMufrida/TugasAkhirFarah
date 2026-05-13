<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ulasan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

class UlasanController extends Controller
{
    public function index(Request $request)
    {
        $periodeList = DB::table('periode_analisis as p')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ulasan as u')
                    ->whereColumn('u.periode_id', 'p.id');
            })
            ->orWhereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('hasil_analisis as h')
                    ->whereColumn('h.periode_id', 'p.id');
            })
            ->orderBy('p.id', 'desc')
            ->select('p.*')
            ->get();

        $availablePeriodeIds = $periodeList->pluck('id')->map(fn($id) => (string) $id);
        $requestedPeriodeId = $request->filled('periode_id') ? (string) $request->periode_id : null;
        $latestFilledPeriodeId = $periodeList->first()->id ?? null;
        $periodeId = $request->filled('periode_id')
            && $availablePeriodeIds->contains($requestedPeriodeId)
            ? $requestedPeriodeId
            : ($latestFilledPeriodeId ?: optional($periodeList->first())->id);
        $periodeAktif = $periodeId ? $periodeList->firstWhere('id', (int) $periodeId) : null;

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

        // ← default/reset menampilkan periode terbaru
        if ($periodeId) {
            $query->where('periode_id', $periodeId);
        }

        $filteredQuery = clone $query;

        $totalUlasan = (clone $filteredQuery)->count();
        $totalWisata = (clone $filteredQuery)->distinct('wisata')->count('wisata');
        $lastUpdate = (clone $filteredQuery)->latest()->value('created_at');

        $mentah = $query->orderBy('tanggal', 'desc')->paginate(20)->withQueryString();

    return view('ulasan.index', compact(
        'mentah',
        'totalUlasan',
        'totalWisata',
        'lastUpdate',
        'periodeList',
        'periodeId',
        'periodeAktif',
        'latestFilledPeriodeId'
    ))->with([
        'scraperDestinations' => $this->scraperDestinations(),
    ]);
    }

    public function riwayat()
    {
        $riwayat = DB::table('periode_analisis as p')
            ->select(
                'p.id',
                'p.nama',
                'p.bulan',
                'p.tahun',
                'p.created_at',
                DB::raw('(SELECT COUNT(*) FROM ulasan u WHERE u.periode_id = p.id) as total_ulasan'),
                DB::raw('(SELECT COUNT(*) FROM hasil_analisis h WHERE h.periode_id = p.id) as total_hasil'),
                DB::raw('(SELECT COUNT(DISTINCT h.wisata) FROM hasil_analisis h WHERE h.periode_id = p.id) as total_wisata'),
                DB::raw('(SELECT MAX(h.created_at) FROM hasil_analisis h WHERE h.periode_id = p.id) as terakhir_analisis')
            )
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ulasan as u')
                    ->whereColumn('u.periode_id', 'p.id');
            })
            ->orWhereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('hasil_analisis as h')
                    ->whereColumn('h.periode_id', 'p.id');
            })
            ->orderBy('p.tahun', 'desc')
            ->orderBy('p.bulan', 'desc')
            ->paginate(12);

        return view('ulasan.riwayat', compact('riwayat'));
    }

    public function importRiwayat(Request $request)
    {
        $this->allowLongRunningRequest();

        $validated = $request->validate([
            'periode_bulan' => ['required', 'date_format:Y-m'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $periodeBulan = Carbon::createFromFormat('Y-m', $validated['periode_bulan']);
        $rows = $this->readCsvRows($request->file('file')->getRealPath());

        if (empty($rows)) {
            return redirect()->route('riwayat.index')
                ->with('error', 'Import gagal: CSV kosong atau header tidak terbaca.');
        }

        $requiredColumns = ['wisata', 'rating', 'ulasan', 'tanggal'];
        $headerColumns = array_keys($rows[0]);
        $missingColumns = array_values(array_diff($requiredColumns, $headerColumns));
        if (!empty($missingColumns)) {
            return redirect()->route('riwayat.index')
                ->with('error', 'Import gagal: kolom wajib tidak ada: ' . implode(', ', $missingColumns) . '.');
        }

        $inserted = 0;
        $skipped = 0;
        $periodeIds = [];

        DB::transaction(function () use ($rows, $periodeBulan, &$inserted, &$skipped, &$periodeIds) {
            foreach ($rows as $index => $row) {
                $wisata = trim((string) ($row['wisata'] ?? ''));
                $ulasan = trim((string) ($row['ulasan'] ?? ''));
                $tanggal = $this->normalizeCsvDate($row['tanggal'] ?? null);
                $rating = $this->normalizeRating($row['rating'] ?? null);
                $isRatingOnly = false;

                if ($ulasan === '' && $rating !== null) {
                    $ulasan = '[Tanpa teks]';
                    $isRatingOnly = true;
                }

                if ($wisata === '' || $ulasan === '' || !$tanggal) {
                    $skipped++;
                    continue;
                }

                $reviewer = trim((string) ($row['reviewer'] ?? 'anonymous')) ?: 'anonymous';
                if ($isRatingOnly && $reviewer === 'anonymous') {
                    $reviewer = 'anonymous-csv-' . ($index + 1);
                }
                $periodeTanggal = Carbon::parse($tanggal)->startOfMonth();
                $periodeId = $this->getOrCreatePeriode($periodeTanggal ?: $periodeBulan);

                $exists = DB::table('ulasan')
                    ->where('wisata', $wisata)
                    ->where('reviewer', $reviewer)
                    ->where('ulasan', $ulasan)
                    ->where('tanggal', $tanggal)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                DB::table('ulasan')->insert([
                    'wisata' => $wisata,
                    'reviewer' => $reviewer,
                    'rating' => $rating,
                    'ulasan' => $ulasan,
                    'tanggal' => $tanggal,
                    'scraping_date' => now(),
                    'periode_id' => $periodeId,
                    'sentimen' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $periodeIds[$periodeId] = $periodeId;
                $inserted++;
            }
        });

        if ($inserted === 0) {
            return redirect()->route('riwayat.index')
                ->with('error', 'Import selesai, tetapi tidak ada data baru yang masuk. Baris kosong/duplikat dilewati: ' . $skipped . '.');
        }

        foreach ($periodeIds as $periodeId) {
            $analisis = $this->runPythonScript(base_path('scraper/analisis.py'), 900, [
                '--periode-id', (string) $periodeId,
            ]);

            if (!$analisis['success']) {
                return redirect()->route('riwayat.index')
                    ->with('error', 'Import berhasil, tetapi analisis gagal: ' . $analisis['output']);
            }
        }

        $periodeLabels = DB::table('periode_analisis')
            ->whereIn('id', array_values($periodeIds))
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->pluck('nama')
            ->implode(', ');

        return redirect()->route('riwayat.index')
            ->with('success', 'Import CSV historis berhasil. Data masuk: ' . $inserted . ', dilewati: ' . $skipped . ', periode: ' . $periodeLabels . '.');
    }

    public function ambilData(Request $request)
    {
        $this->allowLongRunningRequest();

        $validated = $request->validate([
            'periode_bulan' => ['nullable', 'date_format:Y-m'],
            'wisata' => ['nullable', 'string'],
            'redirect_to' => ['nullable', 'in:dashboard,ulasan'],
        ]);

        $periodeBulan = \Carbon\Carbon::createFromFormat(
            'Y-m',
            $validated['periode_bulan'] ?? now()->format('Y-m')
        );
        $startDate = $periodeBulan->copy()->startOfMonth()->toDateString();
        $endDate = $periodeBulan->copy()->endOfMonth()->toDateString();

        $scrapingArguments = [
            '--start-date', $startDate,
            '--end-date', $endDate,
        ];

        if (!empty($validated['wisata'])) {
            $scrapingArguments[] = '--wisata';
            $scrapingArguments[] = $validated['wisata'];
        }

        $scraping = $this->runPythonScript(base_path('scraper/scraping_pipeline.py'), 1800, $scrapingArguments);

        if (!$scraping['success']) {
            return redirect()->route('ulasan.index')
                ->with('error', 'Scraping gagal: ' . $scraping['output']);
        }

        $periode = DB::table('periode_analisis')
            ->where('bulan', \Carbon\Carbon::parse($startDate)->month)
            ->where('tahun', \Carbon\Carbon::parse($startDate)->year)
            ->first();

        $analisisArguments = $periode ? ['--periode-id', (string) $periode->id] : [];
        $analisis = $this->runPythonScript(base_path('scraper/analisis.py'), 900, $analisisArguments);

        if (!$analisis['success']) {
            return redirect()->route('ulasan.index')
                ->with('error', 'Analisis gagal: ' . $analisis['output']);
        }

        $lokasiLabel = empty($validated['wisata']) ? 'semua lokasi' : $validated['wisata'];
        $redirectParameters = [
            'periode_id' => $periode->id ?? null,
        ];

        if (!empty($validated['wisata'])) {
            $redirectParameters['wisata'] = $validated['wisata'];
        }

        if (($validated['redirect_to'] ?? 'dashboard') === 'ulasan') {
            return redirect()->route('ulasan.index', array_filter($redirectParameters))
                ->with('success', 'Data berhasil diambil dan dianalisis untuk ' . $lokasiLabel . ' periode ' . $periodeBulan->translatedFormat('F Y') . '.');
        }

        if (!empty($validated['wisata'])) {
            $redirectParameters['tab'] = 'analisis';
        }

        return redirect()->route('dashboard', array_filter($redirectParameters))
            ->with('success', 'Data berhasil diambil dan dianalisis untuk ' . $lokasiLabel . ' periode ' . $periodeBulan->translatedFormat('F Y') . '.');
    }

    public function kosongkanPeriode(Request $request)
    {
        $periodeId = $request->validate([
            'periode_id' => ['required', 'integer', 'exists:periode_analisis,id'],
        ])['periode_id'];

        DB::transaction(function () use ($periodeId) {
            DB::table('hasil_analisis')->where('periode_id', $periodeId)->delete();
            DB::table('evaluasi_model')->where('periode_id', $periodeId)->delete();
            DB::table('ulasan')->where('periode_id', $periodeId)->delete();
        });

        return redirect()->route('ulasan.index', ['periode_id' => $periodeId])
            ->with('success', 'Data ulasan, hasil analisis, dan evaluasi untuk periode ini sudah dikosongkan.');
    }

    public function analisisData(Request $request)
    {
        $this->allowLongRunningRequest();

        $periodeId = $request->input('periode_id')
            ?: DB::table('ulasan')->max('periode_id');

        if (!$periodeId) {
            return redirect()->route('ulasan.index')
                ->with('error', 'Analisis gagal: belum ada ulasan. Jalankan Ambil Data terlebih dahulu.');
        }

        $totalUlasan = DB::table('ulasan')->where('periode_id', $periodeId)->count();
        if ($totalUlasan === 0) {
            return redirect()->route('ulasan.index', ['periode_id' => $periodeId])
                ->with('error', 'Analisis gagal: belum ada ulasan pada periode yang dipilih.');
        }

        $analisis = $this->runPythonScript(base_path('scraper/analisis.py'), 900, [
            '--periode-id', (string) $periodeId,
        ]);

        if (!$analisis['success']) {
            return redirect()->route('ulasan.index')
                ->with('error', 'Analisis gagal: ' . $analisis['output']);
        }

        return redirect()->route('dashboard', ['periode_id' => $periodeId, 'tab' => 'analisis'])
            ->with('success', 'Analisis berhasil dijalankan.');
    }

    // private function runPythonScript(string $scriptPath, int $timeout, array $arguments = []): array
    // {
    //     $this->allowLongRunningRequest();

    //     $pythonPath = env('PYTHON_PATH', 'python3');
    //     $process = new Process(array_merge([$pythonPath, $scriptPath], $arguments), base_path());
    //     $process->setTimeout($timeout);

    //     try {
    //         $process->run();
    //         $output = trim($process->getOutput() . PHP_EOL . $process->getErrorOutput());

    //         return [
    //             'success' => $process->isSuccessful(),
    //             'output' => $output !== '' ? $output : 'Tidak ada output dari proses Python.',
    //         ];
    //     } catch (Throwable $exception) {
    //         return [
    //             'success' => false,
    //             'output' => $exception->getMessage(),
    //         ];
    //     }
    // }
    private function runPythonScript(string $scriptPath, int $timeout, array $arguments = []): array
    {
        $this->allowLongRunningRequest();

        // 1. Pastikan pakai 'python' bukan 'python3' untuk Windows
        $pythonPath = env('PYTHON_PATH', 'python'); 

        // 2. Suntikkan variabel sistem Windows agar Python tidak eror 10106 saat dijalankan dari web
        // $envVars = array_merge($_SERVER, $_ENV, [
        //     'PATH' => getenv('PATH') ?: 'C:\\Windows\\System32;C:\\Windows',
        //     'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
        //     'windir' => getenv('windir') ?: 'C:\\Windows',
        // ]);
        // 2. Suntikkan variabel sistem Windows secara lengkap
        $envVars = array_merge($_SERVER, $_ENV, [
            'PATH' => getenv('PATH') ?: 'C:\\Windows\\System32;C:\\Windows',
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'windir' => getenv('windir') ?: 'C:\\Windows',
            'USERPROFILE' => getenv('USERPROFILE') ?: 'C:\\Users\\Mufrida Farah',
            'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: 'C:\\Users\\Mufrida Farah\\AppData\\Local',
            'TEMP' => getenv('TEMP') ?: 'C:\\Users\\Mufrida Farah\\AppData\\Local\\Temp',
            'TMP' => getenv('TMP') ?: 'C:\\Users\\Mufrida Farah\\AppData\\Local\\Temp',
        ]);

        // 3. Masukkan $envVars ke parameter ketiga Process
        $process = new Process(array_merge([$pythonPath, $scriptPath], $arguments), base_path(), $envVars);
        $process->setTimeout($timeout);

        try {
            $process->run();
            $output = trim($process->getOutput() . PHP_EOL . $process->getErrorOutput());

            return [
                'success' => $process->isSuccessful(),
                'output' => $output !== '' ? $output : 'Tidak ada output dari proses Python.',
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'output' => $exception->getMessage(),
            ];
        }
    }

    private function allowLongRunningRequest(): void
    {
        ignore_user_abort(true);
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('default_socket_timeout', '1800');
    }

    private function getOrCreatePeriode(Carbon $periodeBulan): int
    {
        $periode = DB::table('periode_analisis')
            ->where('bulan', $periodeBulan->month)
            ->where('tahun', $periodeBulan->year)
            ->first();

        if ($periode) {
            return (int) $periode->id;
        }

        return DB::table('periode_analisis')->insertGetId([
            'nama' => $periodeBulan->translatedFormat('F Y'),
            'bulan' => $periodeBulan->month,
            'tahun' => $periodeBulan->year,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return [];
        }

        $header = array_map(function ($value) {
            $column = strtolower(trim((string) $value));
            $column = preg_replace('/^\xEF\xBB\xBF/', '', $column);

            return match ($column) {
                'destinasi', 'nama_wisata', 'nama wisata' => 'wisata',
                'review', 'komentar' => 'ulasan',
                'nama', 'user', 'author' => 'reviewer',
                default => $column,
            };
        }, $header);
        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($data, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = $data[$index] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function normalizeCsvDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeRating($value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        preg_match('/[1-5]/', (string) $value, $matches);
        return isset($matches[0]) ? (int) $matches[0] : null;
    }

    private function scraperDestinations(): array
    {
        return [
            'Pantai Papuma',
            'Pantai Watu Ulo',
            'Teluk Love',
            'Kebun Teh Gunung Gambir',
        ];
    }
}
