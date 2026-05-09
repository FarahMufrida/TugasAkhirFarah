@php
    $destinasiList    ??= collect([]);
    $totalNegatif     ??= 0;
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

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rekomendasi Layanan</h2>
            <p class="text-sm text-gray-500">
                Rekomendasi dibuat berdasarkan hasil analisis sentimen ulasan wisata.
            </p>
        </div>

        {{-- FILTER DESTINASI — submit ke /dashboard agar tab tidak reset --}}
        <form method="GET" action="{{ route('dashboard') }}" class="flex gap-3">
            <input type="hidden" name="tab" value="rekomendasi">
            <select name="destinasi" onchange="this.form.submit()"
                class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Semua Destinasi</option>
                <option value="Pantai Papuma"                  {{ request('destinasi') == 'Pantai Papuma'                  ? 'selected' : '' }}>Pantai Papuma</option>
                <option value="Pantai Watu Ulo"                {{ request('destinasi') == 'Pantai Watu Ulo'                ? 'selected' : '' }}>Pantai Watu Ulo</option>
                <option value="Teluk Love"                     {{ request('destinasi') == 'Teluk Love'                     ? 'selected' : '' }}>Teluk Love</option>
                <option value="Wisata Kebun Teh Gunung Gambir" {{ request('destinasi') == 'Wisata Kebun Teh Gunung Gambir' ? 'selected' : '' }}>Wisata Kebun Teh Gunung Gambir</option>
            </select>
        </form>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-red-100 text-red-500 p-3 rounded-full text-xl">😟</div>
            <div>
                <p class="text-sm text-gray-500">Total Ulasan Negatif</p>
                <h3 class="text-xl font-bold">{{ $totalNegatif }}</h3>
                <p class="text-xs text-red-500">{{ $persenNegatif }}% dari total</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-green-100 text-green-600 p-3 rounded-full text-xl">😊</div>
            <div>
                <p class="text-sm text-gray-500">Tingkat Kepuasan</p>
                <h3 class="text-xl font-bold">{{ $tingkatKepuasan }}%</h3>
                <span class="text-xs px-2 py-1 rounded
                    {{ $labelKepuasan === 'Baik'   ? 'bg-green-100 text-green-700'  :
                      ($labelKepuasan === 'Sedang' ? 'bg-yellow-100 text-yellow-700' :
                                                     'bg-red-100 text-red-700') }}">
                    {{ $labelKepuasan }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-purple-100 text-purple-600 p-3 rounded-full text-xl">❗</div>
            <div>
                <p class="text-sm text-gray-500">Isu Dominan</p>
                <h3 class="text-xl font-bold">{{ $isuDominan }}</h3>
                <p class="text-xs text-gray-500">{{ $isuDominanPersen }}% dari total isu</p>
            </div>
        </div>

    </div>

    {{-- GRID ANALISIS --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ISU UTAMA --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Isu Utama (Top 5)</h3>

            @forelse ($isuUtama as $i => $isu)
                @php
                    $warnaClass = match($isu['color']) {
                        'red'    => 'bg-red-500',
                        'orange' => 'bg-orange-500',
                        'yellow' => 'bg-yellow-400',
                        'green'  => 'bg-green-500',
                        default  => 'bg-blue-500',
                    };
                @endphp
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $i + 1 }}. {{ $isu['nama'] }}</span>
                        <span class="font-semibold">{{ $isu['persen'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded">
                        <div class="h-2 rounded {{ $warnaClass }}" style="width: {{ $isu['persen'] }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Belum ada data isu.</p>
            @endforelse
        </div>

        {{-- KATA KUNCI DOMINAN --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Kata Kunci Dominan</h3>

            @php $maxFreq = !empty($kataDominan) ? max($kataDominan) : 1; @endphp

            @forelse (array_slice($kataDominan, 0, 5, true) as $kata => $jumlah)
                <div class="mb-3">
                    <div class="flex justify-between text-sm">
                        <span>{{ $kata }}</span>
                        <span class="font-semibold text-blue-600">{{ $jumlah }}x</span>
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded mt-1">
                        <div class="bg-blue-500 h-2 rounded" style="width: {{ round(($jumlah / $maxFreq) * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Belum ada kata kunci.</p>
            @endforelse
        </div>

        {{-- SARAN PERBAIKAN --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Saran Perbaikan</h3>

            <div class="space-y-4 text-sm">
                @forelse ($saranPerbaikan as $saran)
                    <div class="flex gap-3">
                        <div class="text-xl">{{ $saran['icon'] }}</div>
                        <div>
                            <p class="font-semibold">{{ $saran['nama'] }}</p>
                            <p class="text-gray-600">{{ $saran['tip'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">Belum ada saran.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- PRIORITAS REKOMENDASI --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        @forelse ($prioritas as $p)
            @php
                $border = match($p['color']) {
                    'red'    => 'border-red-300',
                    'orange' => 'border-orange-300',
                    'yellow' => 'border-yellow-300',
                    'green'  => 'border-green-300',
                    default  => 'border-blue-300',
                };
                $title = match($p['color']) {
                    'red'    => 'text-red-600',
                    'orange' => 'text-orange-500',
                    'yellow' => 'text-yellow-600',
                    'green'  => 'text-green-600',
                    default  => 'text-blue-600',
                };
                $dampak = match($p['color']) {
                    'red'    => 'bg-red-100 text-red-600',
                    'orange' => 'bg-orange-100 text-orange-600',
                    'yellow' => 'bg-yellow-100 text-yellow-600',
                    'green'  => 'bg-green-100 text-green-700',
                    default  => 'bg-blue-100 text-blue-600',
                };
            @endphp
            <div class="border {{ $border }} rounded-xl p-5 bg-white">
                <h4 class="font-semibold {{ $title }} mb-2">Prioritas {{ $p['rank'] }}</h4>
                <h3 class="text-lg font-bold mb-3">{{ $p['nama'] }}</h3>

                <ul class="text-sm space-y-2 mb-4">
                    @foreach ($p['actions'] as $action)
                        <li class="flex items-center gap-2">
                            <span class="text-blue-500">✔</span> {{ $action }}
                        </li>
                    @endforeach
                </ul>

                <div class="text-xs {{ $dampak }} p-2 rounded">
                    Dampak: {{ $p['dampak'] }}
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center text-gray-400 text-sm py-6">
                Belum ada data rekomendasi. Pastikan analisis sentimen sudah dijalankan.
            </div>
        @endforelse

    </div>

</div>