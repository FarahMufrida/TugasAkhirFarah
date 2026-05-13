@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto px-4 md:px-0 space-y-6">

    <!-- HEADER -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Ulasan</h2>
            <p class="text-sm text-gray-500">
                Periode aktif: {{ $periodeAktif->nama ?? 'Belum ada data' }}
                <a href="{{ route('riwayat.index') }}" class="text-blue-600 hover:underline ml-2">Lihat riwayat</a>
            </p>
        </div>
        <div class="flex flex-col md:flex-row gap-3 md:items-center">
            <form action="{{ route('ulasan.ambil') }}" method="POST" class="flex flex-col md:flex-row gap-3 md:items-center">
                @csrf
                <input type="hidden" name="redirect_to" value="ulasan">
                <input type="month" name="periode_bulan" value="{{ now()->format('Y-m') }}"
                    class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-[150px]">
                <select name="wisata" class="border rounded-lg pl-3 pr-9 py-2 text-sm bg-white shadow-sm min-w-[220px]">
                    <option value="">Semua Lokasi</option>
                    @foreach(($scraperDestinations ?? []) as $scraperDestination)
                        <option value="{{ $scraperDestination }}">{{ $scraperDestination }}</option>
                    @endforeach
                </select>
                <button class="border border-blue-500 text-blue-600 px-5 py-2.5 rounded-full hover:bg-blue-50 transition">
                    Ambil Data
                </button>
            </form>
            <form action="{{ route('proses.analisis') }}" method="POST">
                @csrf
                <input type="hidden" name="periode_id" value="{{ $periodeId }}">
                <button
                    {{ $totalUlasan <= 0 ? 'disabled' : '' }}
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Proses Analisis
                </button>
            </form>
        </div>
    </div>

</div>

    <!-- SUMMARY -->
    <div class="bg-white rounded-3xl shadow p-6 mb-6 mt-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 text-blue-600 p-3 rounded-full">📄</div>
                <div>
                    <p class="text-xs text-gray-500">Total Ulasan</p>
                    <p class="font-bold text-lg">{{ $totalUlasan }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-green-100 text-green-600 p-3 rounded-full">📍</div>
                <div>
                    <p class="text-xs text-gray-500">Total Wisata</p>
                    <p class="font-bold text-lg">{{ $totalWisata }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-orange-100 text-orange-600 p-3 rounded-full">📅</div>
                <div>
                    <p class="text-xs text-gray-500">Terakhir Update</p>
                    <p class="text-sm font-semibold">
                        {{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('d M Y H:i') : '-' }}
                    </p>
                </div>
            </div>
        </div> 
    </div>

    <!-- ALERT -->
    @if(session('success'))
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm whitespace-pre-line">
            {{ session('error') }}
        </div>
    @endif

    <!-- SEARCH -->
    <form method="GET" action="{{ route('ulasan.index') }}" class="flex flex-col md:flex-row gap-3 items-center">
        @if(request('periode_id'))
            <input type="hidden" name="periode_id" value="{{ request('periode_id') }}">
        @endif

        {{-- SEARCH --}}
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Cari ulasan atau wisata..."
            class="flex-1 border px-3 py-2 rounded-lg text-sm">

        <button class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm">Cari</button>

        <button type="button"
            onclick="if (confirm('Reset akan mengosongkan semua ulasan dan hasil analisis untuk periode ini. Lanjutkan?')) document.getElementById('resetPeriodeForm').submit();"
            class="bg-gray-200 px-4 py-2 rounded-lg text-sm">
            Reset
        </button>

    </form>

    @if($periodeId)
        <form id="resetPeriodeForm" action="{{ route('ulasan.kosongkan-periode') }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
            <input type="hidden" name="periode_id" value="{{ $periodeId }}">
        </form>
    @endif

    <!-- TABLE -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 w-[5%]">No</th>
                        <th class="px-4 py-3 w-[20%]">Nama Wisata</th>
                        <th class="px-4 py-3 w-[10%] text-center">Rating</th>
                        <th class="px-4 py-3 w-[45%]">Ulasan</th>
                        <th class="px-4 py-3 w-[20%] text-center">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($mentah as $i => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $mentah->firstItem() + $i }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->wisata }}</td>
                        <td class="px-4 py-3 text-center text-yellow-500">
                            {{ str_repeat('★', round($item->rating)) }}
                            <span class="text-gray-500 ml-1">{{ $item->rating }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="line-clamp-2 text-gray-600">{{ $item->ulasan }}</div>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500">
                            @php
                                try {
                                    $tanggalUlasan = $item->tanggal
                                        ? \Carbon\Carbon::parse($item->tanggal)->format('d M Y')
                                        : '-';
                                } catch (\Throwable $e) {
                                    $tanggalUlasan = $item->tanggal ?: '-';
                                }
                            @endphp
                            {{ $tanggalUlasan }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-6 text-gray-400">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 flex flex-col md:flex-row gap-3 justify-between items-center text-sm text-gray-500">
            <div>
                Menampilkan {{ $mentah->firstItem() ?? 0 }} - {{ $mentah->lastItem() ?? 0 }}
                dari {{ $mentah->total() }} data
            </div>
            <div class="flex items-center gap-1">
                @if ($mentah->onFirstPage())
                    <span class="px-3 py-1 bg-gray-200 rounded-lg">«</span>
                @else
                    <a href="{{ $mentah->previousPageUrl() }}" class="px-3 py-1 bg-gray-100 rounded-lg">«</a>
                @endif

                @for ($i = 1; $i <= $mentah->lastPage(); $i++)
                    @if ($i == $mentah->currentPage())
                        <span class="px-3 py-1 bg-blue-600 text-white rounded-lg">{{ $i }}</span>
                    @elseif ($i <= 3 || $i > $mentah->lastPage() - 2 || abs($i - $mentah->currentPage()) <= 1)
                        <a href="{{ $mentah->url($i) }}" class="px-3 py-1 bg-gray-100 rounded-lg">{{ $i }}</a>
                    @elseif ($i == 4 || $i == $mentah->lastPage() - 3)
                        <span class="px-2">...</span>
                    @endif
                @endfor

                @if ($mentah->hasMorePages())
                    <a href="{{ $mentah->nextPageUrl() }}" class="px-3 py-1 bg-gray-100 rounded-lg">»</a>
                @else
                    <span class="px-3 py-1 bg-gray-200 rounded-lg">»</span>
                @endif
            </div>
        </div>
    </div>

@endsection
