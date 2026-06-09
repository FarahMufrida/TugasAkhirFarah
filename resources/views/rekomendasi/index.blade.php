@php
    $destinasiList    ??= collect([]);
    $totalDestinasiAnalisis ??= 0;
    $totalDestinasiBerkeluhan ??= 0;
    $totalNegatif     ??= 0;
    $totalPositif     ??= 0;
    $totalNetral      ??= 0;
    $totalUlasan      ??= 0;
    $persenNegatif    ??= 0;
    $tingkatKepuasan  ??= 0;
    $labelKepuasan    ??= 'Kurang';
    $isuDominan       ??= '-';
    $isuDominanPersen ??= 0;
    $isuUtama         ??= [];
    $kataDominan      ??= [];
    $saranPerbaikan   ??= [];
    $prioritas        ??= [];
@endphp

@php
    // $persenPositif, $persenNetral, $isuNegatif, $isuNetral, $isuPositif sudah dikirim dari controller
    // Icon map untuk prioritas cards
    $iconMap = [
        'harga'     => '🎫',
        'tiket'     => '🎫',
        'kebersihan'=> '🧹',
        'parkir'    => '🚗',
        'fasilitas' => '🏗️',
        'keramaian' => '👥',
        'akses'     => '🛣️',
        'jalan'     => '🛣️',
        'default'   => '⚙️',
    ];
@endphp

<style>
.gauge-ring {
    transform: rotate(-90deg);
    transform-origin: center;
}
.gauge-wrap { position: relative; display: inline-block; }
.gauge-inner {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -30%);
    text-align: center;
    line-height: 1.1;
}
</style>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rekomendasi Layanan</h2>
            <p class="text-sm text-gray-500">
                Rekomendasi dibuat dari hasil analisis sentimen untuk perbaikan layanan destinasi wisata.
            </p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $totalDestinasiAnalisis }} destinasi dianalisis, {{ $totalDestinasiBerkeluhan }} destinasi memiliki ulasan negatif.
            </p>
        </div>

        {{-- FILTER DESTINASI — submit ke /dashboard agar tab tidak reset --}}
        <form method="GET" action="{{ route('dashboard') }}" class="flex gap-3">
            <input type="hidden" name="tab" value="rekomendasi">
            <input type="hidden" name="periode_id" value="{{ $periodeId ?? $periodeAktif->id ?? request('periode_id') }}">
            @if(request('periode_bulan'))
                <input type="hidden" name="periode_bulan" value="{{ request('periode_bulan') }}">
            @endif
            <select name="destinasi" onchange="this.form.submit()"
                class="border rounded-lg pl-3 pr-9 py-2 text-sm min-w-[220px] focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Semua Destinasi</option>
                @foreach($destinasiList as $destinasi)
                    <option value="{{ $destinasi }}" {{ request('destinasi') == $destinasi ? 'selected' : '' }}>
                        {{ $destinasi }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- SUMMARY CARDS — 3 gauge cards seperti di screenshot --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Tingkat Positif --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-green-600">Tingkat Positif</p>
                <h3 class="text-4xl font-bold text-green-600 mt-1">{{ $persenPositif }}%</h3>
                <span class="inline-block mt-2 text-xs px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium">Positif</span>
                <p class="text-xs text-gray-400 mt-2">{{ $totalPositif }} ulasan</p>
            </div>
            <div class="gauge-wrap">
                <svg width="90" height="70" viewBox="0 0 90 70">
                    <path d="M 10 65 A 35 35 0 0 1 80 65" fill="none" stroke="#e5e7eb" stroke-width="8" stroke-linecap="round"/>
                    <path d="M 10 65 A 35 35 0 0 1 80 65" fill="none" stroke="#22c55e" stroke-width="8" stroke-linecap="round"
                        stroke-dasharray="{{ round($persenPositif * 1.099) }} 110"
                        style="transition: stroke-dasharray 0.8s ease"/>
                </svg>
                <div style="position:absolute;bottom:6px;left:50%;transform:translateX(-50%);font-size:1.5rem;">😊</div>
            </div>
        </div>

        {{-- Tingkat Negatif --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-red-500">Tingkat Negatif</p>
                <h3 class="text-4xl font-bold text-red-500 mt-1">{{ $persenNegatif }}%</h3>
                <span class="inline-block mt-2 text-xs px-3 py-1 rounded-full bg-red-100 text-red-600 font-medium">Negatif</span>
                <p class="text-xs text-gray-400 mt-2">{{ $totalNegatif }} ulasan</p>
            </div>
            <div class="gauge-wrap">
                <svg width="90" height="70" viewBox="0 0 90 70">
                    <path d="M 10 65 A 35 35 0 0 1 80 65" fill="none" stroke="#fee2e2" stroke-width="8" stroke-linecap="round"/>
                    <path d="M 10 65 A 35 35 0 0 1 80 65" fill="none" stroke="#ef4444" stroke-width="8" stroke-linecap="round"
                        stroke-dasharray="{{ round($persenNegatif * 1.099) }} 110"
                        style="transition: stroke-dasharray 0.8s ease"/>
                </svg>
                <div style="position:absolute;bottom:6px;left:50%;transform:translateX(-50%);font-size:1.5rem;">😟</div>
            </div>
        </div>

        {{-- Tingkat Netral --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-500">Tingkat Netral</p>
                <h3 class="text-4xl font-bold text-gray-600 mt-1">{{ $persenNetral }}%</h3>
                <span class="inline-block mt-2 text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-600 font-medium">Netral</span>
                <p class="text-xs text-gray-400 mt-2">{{ $totalNetral }} ulasan</p>
            </div>
            <div class="gauge-wrap">
                <svg width="90" height="70" viewBox="0 0 90 70">
                    <path d="M 10 65 A 35 35 0 0 1 80 65" fill="none" stroke="#e5e7eb" stroke-width="8" stroke-linecap="round"/>
                    <path d="M 10 65 A 35 35 0 0 1 80 65" fill="none" stroke="#9ca3af" stroke-width="8" stroke-linecap="round"
                        stroke-dasharray="{{ round($persenNetral * 1.099) }} 110"
                        style="transition: stroke-dasharray 0.8s ease"/>
                </svg>
                <div style="position:absolute;bottom:6px;left:50%;transform:translateX(-50%);font-size:1.5rem;">😐</div>
            </div>
        </div>

    </div>



    {{-- TABEL ISU 3 KOLOM (Negatif / Netral / Positif) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ISU NEGATIF UTAMA --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-red-600">Isu Negatif Utama (Top 5)</h3>

            @forelse ($isuNegatif as $i => $isu)
                @if($i >= 5) @break @endif
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700">{{ $i + 1 }}. {{ $isu['nama'] }}</span>
                        <span class="font-semibold text-gray-800">
                            {{ $isu['persen'] }}%
                            @if(isset($isu['jumlah'])) ({{ $isu['jumlah'] }}) @endif
                        </span>
                    </div>
                    <div class="w-full bg-red-100 h-2 rounded">
                        <div class="h-2 rounded bg-red-500" style="width: {{ $isu['persen'] }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Belum ada data isu negatif.</p>
            @endforelse

            <p class="text-xs text-red-400 mt-2">
                Persentase berdasarkan dari total ulasan negatif ({{ $totalNegatif }} ulasan)
            </p>
        </div>

        {{-- ISU NETRAL UTAMA --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-600">Isu Netral Utama (Top 5)</h3>

            @forelse ($isuNetral as $i => $isu)
                @if($i >= 5) @break @endif
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700">{{ $i + 1 }}. {{ $isu['nama'] }}</span>
                        <span class="font-semibold text-gray-800">
                            {{ $isu['persen'] }}%
                            @if(isset($isu['jumlah'])) ({{ $isu['jumlah'] }}) @endif
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded">
                        <div class="h-2 rounded bg-gray-500" style="width: {{ $isu['persen'] }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Belum ada data isu netral.</p>
            @endforelse

            <p class="text-xs text-gray-400 mt-2">
                Persentase berdasarkan dari total ulasan netral ({{ $totalNetral }} ulasan)
            </p>
        </div>

        {{-- ASPEK POSITIF UTAMA --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-green-600">Aspek Positif Utama (Top 5)</h3>

            @forelse ($isuPositif as $i => $isu)
                @if($i >= 5) @break @endif
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700">{{ $i + 1 }}. {{ $isu['nama'] }}</span>
                        <span class="font-semibold text-gray-800">
                            {{ $isu['persen'] }}%
                            @if(isset($isu['jumlah'])) ({{ $isu['jumlah'] }}) @endif
                        </span>
                    </div>
                    <div class="w-full bg-green-100 h-2 rounded">
                        <div class="h-2 rounded bg-green-500" style="width: {{ $isu['persen'] }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Belum ada data isu positif.</p>
            @endforelse

            <p class="text-xs text-green-400 mt-2">
                Persentase berdasarkan dari total ulasan positif ({{ $totalPositif }} ulasan)
            </p>
        </div>

    </div>

    {{-- PRIORITAS REKOMENDASI --}}
    <div>
        <h3 class="text-lg font-bold text-gray-800 mb-1">Prioritas Rekomendasi Perbaikan Layanan</h3>
        <p class="text-sm text-gray-500 mb-4">Prioritas disusun berdasarkan frekuensi isu negatif dan dampaknya terhadap kepuasan pengunjung.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            @forelse ($prioritas as $p)
                @php
                    $border = match($p['color']) {
                        'red'    => 'border-red-200',
                        'orange' => 'border-orange-200',
                        'yellow' => 'border-yellow-200',
                        'green'  => 'border-green-200',
                        default  => 'border-blue-200',
                    };
                    $title = match($p['color']) {
                        'red'    => 'text-red-600',
                        'orange' => 'text-orange-500',
                        'yellow' => 'text-yellow-600',
                        'green'  => 'text-green-600',
                        default  => 'text-blue-600',
                    };
                    $dampak = match($p['color']) {
                        'red'    => 'bg-red-50 text-red-500 border border-red-200',
                        'orange' => 'bg-orange-50 text-orange-500 border border-orange-200',
                        'yellow' => 'bg-yellow-50 text-yellow-600 border border-yellow-200',
                        'green'  => 'bg-green-50 text-green-700 border border-green-200',
                        default  => 'bg-blue-50 text-blue-600 border border-blue-200',
                    };
                    $badgeBg = match($p['color']) {
                        'red'    => 'bg-red-500',
                        'orange' => 'bg-orange-400',
                        'yellow' => 'bg-yellow-400',
                        'green'  => 'bg-green-500',
                        default  => 'bg-blue-500',
                    };
                    $namaLower = strtolower($p['nama']);
                    $icon = '⚙️';
                    foreach ($iconMap as $key => $val) {
                        if (str_contains($namaLower, $key)) { $icon = $val; break; }
                    }
                @endphp
                <div class="border {{ $border }} rounded-xl p-5 bg-white">
                    <div class="flex items-start gap-4 mb-3">
                        <div class="{{ $badgeBg }} text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm flex-shrink-0">
                            {{ $p['rank'] }}
                        </div>
                        <div>
                            <p class="text-xs font-semibold {{ $title }}">
                                Prioritas {{ $p['rank'] === 1 ? 'Utama' : ($p['rank'] === 2 ? 'Kedua' : 'Ketiga') }}
                            </p>
                            <h3 class="text-base font-bold text-gray-800">{{ $p['nama'] }}</h3>
                        </div>
                        <div class="ml-auto text-3xl">{{ $icon }}</div>
                    </div>

                    <ul class="text-sm space-y-2 mb-4">
                        @foreach ($p['actions'] as $action)
                            <li class="flex items-start gap-2 text-gray-700">
                                <span class="text-blue-500 mt-0.5">✔</span> {{ $action }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="text-xs {{ $dampak }} p-2 rounded-lg">
                        Alasan: {{ $p['dampak'] }}
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center text-gray-400 text-sm py-6">
                    Belum ada data rekomendasi. Pastikan analisis sentimen sudah dijalankan.
                </div>
            @endforelse

        </div>
    </div>

    {{-- FOOTER NOTE --}}
    <div class="bg-blue-50 border border-blue-100 rounded-xl px-5 py-3 flex items-start gap-3 text-sm text-gray-600">
        <span class="text-blue-500 mt-0.5">ℹ️</span>
        <span>Rekomendasi dihasilkan secara otomatis berdasarkan hasil analisis sentimen dan kata kunci dominan dari ulasan wisatawan.</span>
    </div>

</div>