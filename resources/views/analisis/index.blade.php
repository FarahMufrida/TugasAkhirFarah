<div class="space-y-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Hasil Analisis Sentimen</h2>
            <p class="text-gray-500 text-sm">
                Berikut adalah hasil evaluasi model dan detail analisis sentimen terhadap ulasan wisata.
            </p>
        </div>
        <!-- <div class="text-sm text-gray-500">
            {{ now()->format('d F Y - H:i') }}
        </div> -->
    </div>

    {{-- ================= METRICS ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Precision</p>
            <h3 class="text-2xl font-bold text-blue-600">
                {{ $evaluasi->precision ?? 0 }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Recall</p>
            <h3 class="text-2xl font-bold text-green-600">
                {{ $evaluasi->recall ?? 0 }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">F1 Score</p>
            <h3 class="text-2xl font-bold text-purple-600">
                {{ $evaluasi->f1_score ?? 0 }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Akurasi</p>
            <h3 class="text-2xl font-bold text-indigo-600">
                {{ $evaluasi->accuracy ?? 0 }}
            </h3>
        </div>

    </div>

    {{-- ================= MATRIX + PERFORMA ================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- CONFUSION MATRIX --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="font-semibold mb-4">Confusion Matrix</h3>

            <table class="w-full text-center border rounded-lg overflow-hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th></th>
                        <th>Positif</th>
                        <th>Negatif</th>
                        <th>Netral</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th class="bg-gray-50">Positif</th>
                        <td class="bg-green-100">{{ $evaluasi->tp ?? 0 }}</td>
                        <td class="bg-red-100">{{ $evaluasi->fn ?? 0 }}</td>
                        <td class="bg-yellow-100">0</td>
                    </tr>
                    <tr>
                        <th class="bg-gray-50">Negatif</th>
                        <td class="bg-red-100">{{ $evaluasi->fp ?? 0 }}</td>
                        <td class="bg-green-100">{{ $evaluasi->tn ?? 0 }}</td>
                        <td class="bg-yellow-100">0</td>
                    </tr>
                    <tr>
                        <th class="bg-gray-50">Netral</th>
                        <td class="bg-yellow-100">0</td>
                        <td class="bg-yellow-100">0</td>
                        <td class="bg-green-100">0</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- PERFORMA --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="font-semibold mb-4">Performa per Kelas</h3>

            <table class="w-full text-sm border">
                <thead class="bg-gray-100">
                    <tr>
                        <th>Kelas</th>
                        <th>Precision</th>
                        <th>Recall</th>
                        <th>F1</th>
                        <th>Support</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-green-600 font-semibold">Positif</td>
                        <td>{{ $evaluasi->precision ?? 0 }}</td>
                        <td>{{ $evaluasi->recall ?? 0 }}</td>
                        <td>{{ $evaluasi->f1_score ?? 0 }}</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td class="text-red-600 font-semibold">Negatif</td>
                        <td>{{ $evaluasi->precision ?? 0 }}</td>
                        <td>{{ $evaluasi->recall ?? 0 }}</td>
                        <td>{{ $evaluasi->f1_score ?? 0 }}</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td class="text-yellow-600 font-semibold">Netral</td>
                        <td>{{ $evaluasi->precision ?? 0 }}</td>
                        <td>{{ $evaluasi->recall ?? 0 }}</td>
                        <td>{{ $evaluasi->f1_score ?? 0 }}</td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    {{-- ================= FILTER ================= --}}
  <form id="filter-form" class="flex items-center gap-3">

    <input type="hidden" name="tab" value="analisis">

    <select name="wisata" class="border rounded px-3 py-2">
        <option value="">Semua Destinasi</option>

        @foreach($destinasiList as $d)
            <option value="{{ $d }}" {{ request('wisata') == $d ? 'selected' : '' }}>
                {{ $d }}
            </option>
        @endforeach
    </select>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
        Filter
    </button>

</form>

    {{-- ================= TABLE ================= --}}
    <div class="bg-white rounded-xl shadow p-4">

        <h3 class="font-semibold mb-4">Detail Hasil Analisis Sentimen</h3>

        <table class="w-full text-sm">
            <thead class="border-b text-left">
                <tr>
                    <th>No</th>
                    <th>Wisata</th>
                    <th>Ulasan</th>
                    <th>Sentimen</th>
                    <th>Probabilitas</th>
                </tr>
            </thead>

            <tbody>
                @forelse($hasil as $index => $item)
                    <tr class="border-b">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->wisata }}</td>
                        <td>{{ Str::limit($item->ulasan_terolah, 80) }}</td>

                        <td>
                            <span class="
                                px-2 py-1 rounded text-xs
                                {{ $item->sentimen == 'Positif' ? 'bg-green-100 text-green-600' : '' }}
                                {{ $item->sentimen == 'Negatif' ? 'bg-red-100 text-red-600' : '' }}
                                {{ $item->sentimen == 'Netral' ? 'bg-yellow-100 text-yellow-600' : '' }}
                            ">
                                {{ $item->sentimen }}
                            </span>
                        </td>

                        <td>{{ $item->probabilitas ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-gray-500">
                            Tidak ada data
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- PAGINATION --}}
        <div class="mt-6 flex items-center justify-between text-sm text-gray-500">

    {{-- INFO --}}
    <div>
        Menampilkan {{ $hasil->firstItem() }} - {{ $hasil->lastItem() }} 
        dari {{ $hasil->total() }} data
    </div>

    {{-- PAGINATION --}}
    {{-- PAGINATION --}}
<div class="flex items-center gap-1">

    {{-- PREV --}}
    @if ($hasil->onFirstPage())
        <span class="px-3 py-1 rounded border text-gray-300">‹</span>
    @else
        <a href="{{ $hasil->appends(request()->query())->previousPageUrl() }}"
           class="px-3 py-1 rounded border hover:bg-gray-100">‹</a>
    @endif

    {{-- PAGE NUMBERS --}}
    @foreach ($hasil->getUrlRange(1, $hasil->lastPage()) as $page => $url)

        @php
            $query = request()->query();
            $query['page'] = $page;
            $customUrl = url()->current() . '?' . http_build_query($query);
        @endphp

        @if ($page == $hasil->currentPage())
            <span class="px-3 py-1 rounded bg-blue-600 text-white">
                {{ $page }}
            </span>

        @elseif ($page <= 3 || $page > $hasil->lastPage()-3 || abs($page - $hasil->currentPage()) <= 1)

            <a href="{{ $customUrl }}"
               class="px-3 py-1 rounded border hover:bg-gray-100">
                {{ $page }}
            </a>

        @elseif ($page == 4 || $page == $hasil->lastPage()-3)
            <span class="px-2">...</span>
        @endif

    @endforeach

    {{-- NEXT --}}
    @if ($hasil->hasMorePages())
        <a href="{{ $hasil->appends(request()->query())->nextPageUrl() }}"
           class="px-3 py-1 rounded border hover:bg-gray-100">›</a>
    @else
        <span class="px-3 py-1 rounded border text-gray-300">›</span>
    @endif

</div>

</div>

    </div>

</div>