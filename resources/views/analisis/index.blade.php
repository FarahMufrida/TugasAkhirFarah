@if($standalone ?? false)
    @extends('layouts.sentara')
    @section('content')
@endif

@php
    $totalHasilAnalisis ??= $hasil->total() ?? 0;
    $jumlahKelasAnalisis ??= 0;
    $evaluasiTersedia = $evaluasi && $jumlahKelasAnalisis >= 2;
@endphp

<div class="space-y-4">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Hasil Analisis Sentimen</h2>
            
            @if($totalHasilAnalisis > 0 && !$evaluasiTersedia)
                <p class="text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2 mt-3">
                    Semua hasil hanya memiliki {{ $jumlahKelasAnalisis }} kelas sentimen. Precision, recall, F1, dan akurasi tidak dihitung karena evaluasi klasifikasi membutuhkan minimal 2 kelas. Detail hasil analisis tetap ditampilkan di bawah.
                </p>
            @endif
        </div>
    </div>

    {{-- ================= METRICS ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Precision</p>
            <h3 class="text-2xl font-bold text-blue-600">
                {{ $evaluasiTersedia ? number_format($evaluasi->precision ?? 0, 2) : '-' }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Recall</p>
            <h3 class="text-2xl font-bold text-green-600">
                {{ $evaluasiTersedia ? number_format($evaluasi->recall ?? 0, 2) : '-' }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">F1 Score</p>
            <h3 class="text-2xl font-bold text-purple-600">
                {{ $evaluasiTersedia ? number_format($evaluasi->f1_score ?? 0, 2) : '-' }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Akurasi</p>
            <h3 class="text-2xl font-bold text-indigo-600">
                {{ $evaluasiTersedia ? number_format($evaluasi->accuracy ?? 0, 2) : '-' }}
            </h3>
        </div>

    </div>



    {{-- ================= FILTER ================= --}}
<div class="flex items-end gap-3 mb-4">

    <form id="filter-form"
        method="GET"
        action="{{ route('dashboard') }}"
        class="flex items-end gap-2">

        <input type="hidden" name="tab" value="analisis">
        <input type="hidden" name="periode_id"
            value="{{ $periodeAktif->id ?? '' }}">

        <select
            name="wisata"
            class="border rounded px-3 py-2 h-[46px]">

            <option value="">Semua Destinasi</option>

            @foreach($wisataList as $item)
                <option value="{{ $item }}"
                    {{ request('wisata') == $item ? 'selected' : '' }}>
                    {{ $item }}
                </option>
            @endforeach

        </select>

        <button
            type="submit"
            class="bg-blue-600 text-white px-4 rounded h-[46px]">
            Filter
        </button>

    </form>

    

</div>

    {{-- ================= TABLE ================= --}}
    <div class="bg-white rounded-xl shadow p-4 h-fit">

        <h3 class="font-semibold mb-4">Detail Hasil Analisis Sentimen</h3>

        <table class="w-full text-sm">
            <thead class="border-b text-left">
                <tr>
                    <th>No</th>
                    <th>Wisata</th>
                    <th>Ulasan Asli</th>
                    <th>Ulasan Bersih</th>
                    <th>Sentimen</th>
                    <th>Probabilitas</th>
                </tr>
            </thead>

            <tbody>
                 @forelse($hasil as $item)
                    <tr class="border-b">
                        <td>{{ ($hasil->currentPage() - 1) * $hasil->perPage() + $loop->iteration }}</td>
                        <td>{{ $item->wisata }}</td>
                        <td class="text-gray-500">{{ Str::limit($item->ulasan_asli ?? '-', 80) }}</td>
                        <td>{{ Str::limit($item->hasil_preprocessing ?? '-', 80) }}</td>
                        <td>
                            <span class="px-2 py-1 rounded text-xs
                                {{ strtolower($item->sentimen) == 'positif' ? 'bg-green-100 text-green-600' : '' }}
                                {{ strtolower($item->sentimen) == 'negatif' ? 'bg-red-100 text-red-600' : '' }}
                                {{ strtolower($item->sentimen) == 'netral'  ? 'bg-yellow-100 text-yellow-600' : '' }}
                            ">
                                {{ ucfirst($item->sentimen) }}
                            </span>
                        </td>
                        <td>{{ number_format($item->probabilitas ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-gray-500">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- ================= PAGINATION ================= --}}
        @if($hasil->hasPages())
            @php
                $current = $hasil->currentPage();
                $last    = $hasil->lastPage();
                $query   = http_build_query(request()->except('page'));
                // Window 2 halaman kiri & kanan dari current
                $window  = collect(range(max(1, $current - 2), min($last, $current + 2)));
            @endphp

            <div class="mt-4 flex items-center justify-between text-sm text-gray-600">

                {{-- Info --}}
                <p>Menampilkan {{ $hasil->firstItem() }} - {{ $hasil->lastItem() }} dari {{ $hasil->total() }} data</p>

                {{-- Tombol --}}
                <div class="flex items-center gap-1">

                    {{-- « --}}
                    @if($hasil->onFirstPage())
                        <span class="px-3 py-1 rounded border bg-gray-100 text-gray-400 cursor-not-allowed">&laquo;</span>
                    @else
                        <a href="{{ $hasil->previousPageUrl() }}&{{ $query }}" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600">&laquo;</a>
                    @endif

                    {{-- Halaman 1 (jika tidak ada di window) --}}
                    @if(!$window->contains(1))
                        <a href="{{ $hasil->url(1) }}&{{ $query }}" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600">1</a>
                        @if($window->min() > 2)
                            <span class="px-2 py-1 text-gray-400">...</span>
                        @endif
                    @endif

                    {{-- Window --}}
                    @foreach($window as $page)
                        @if($page == $current)
                            <span class="px-3 py-1 rounded border bg-blue-600 text-white font-semibold">{{ $page }}</span>
                        @else
                            <a href="{{ $hasil->url($page) }}&{{ $query }}" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Halaman terakhir (jika tidak ada di window) --}}
                    @if(!$window->contains($last))
                        @if($window->max() < $last - 1)
                            <span class="px-2 py-1 text-gray-400">...</span>
                        @endif
                        <a href="{{ $hasil->url($last) }}&{{ $query }}" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600">{{ $last }}</a>
                    @endif

                    {{-- » --}}
                    @if($hasil->hasMorePages())
                        <a href="{{ $hasil->nextPageUrl() }}&{{ $query }}" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600">&raquo;</a>
                    @else
                        <span class="px-3 py-1 rounded border bg-gray-100 text-gray-400 cursor-not-allowed">&raquo;</span>
                    @endif

                </div>
            </div>
        @endif

    </div>

</div>

@if($standalone ?? false)
    @endsection
@endif
