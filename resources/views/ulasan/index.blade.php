@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto px-4 md:px-0 space-y-6">

    <!-- HEADER -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">Data Ulasan</h2>
        <div class="flex gap-3">
            <button class="border border-blue-500 text-blue-600 px-5 py-2.5 rounded-full hover:bg-blue-50 transition">
                Ambil Data Terbaru
            </button>
            <form action="{{ route('proses.analisis') }}" method="POST">
                @csrf
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full shadow-md transition">
                    Proses Analisis
                </button>
            </form>
        </div>
    </div>

</div>

    <!-- UPLOAD + FORMAT + SUMMARY -->
    <div class="bg-white rounded-3xl shadow p-6 mb-6 mt-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">

            <!-- UPLOAD -->
            <div class="border-2 border-dashed border-blue-300 rounded-xl p-6 text-center">
                <div class="flex justify-center mb-3">
                    <div class="bg-blue-100 text-blue-600 p-3 rounded-full text-xl">☁️</div>
                </div>
                <p class="text-gray-600 text-sm mb-2">Upload file CSV / XLSX</p>
                <p class="text-gray-400 text-xs mb-3">klik tombol di bawah</p>
                <form id="uploadForm" action="{{ route('ulasan.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" id="fileInput" accept=".csv,.xlsx,.xls" class="hidden">
                    <button type="button" onclick="document.getElementById('fileInput').click()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm shadow">
                        Pilih File
                    </button>
                </form>
            </div>

            <!-- FORMAT -->
            <div class="bg-blue-50 rounded-xl p-5">
                <h4 class="font-semibold text-blue-700 mb-3">Format CSV yang Diperlukan</h4>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li>✔ wisata, rating, ulasan, tanggal</li>
                    <li>✔ Format: YYYY-MM-DD HH:MM:SS</li>
                    <li>✔ Maks 10MB</li>
                </ul>
            </div>

            <!-- SUMMARY -->
            <div class="space-y-4">
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
    </div>

    <!-- ALERT -->
    @if(session('success'))
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- FILTER PERIODE + SEARCH -->
    <form method="GET" action="{{ route('ulasan.index') }}" class="flex flex-col md:flex-row gap-3 items-center">

        {{-- DROPDOWN PERIODE --}}
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600 font-medium whitespace-nowrap">Periode:</span>
            <select name="periode_id" onchange="this.form.submit()"
                class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm">
                <option value="">Semua Periode</option>
                @foreach($periodeList as $p)
                    <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->nama }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- SEARCH --}}
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Cari ulasan atau wisata..."
            class="flex-1 border px-3 py-2 rounded-lg text-sm">

        <button class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm">Cari</button>

        <a href="{{ route('ulasan.index') }}" class="bg-gray-200 px-4 py-2 rounded-lg text-sm">Reset</a>

    </form>

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
                            {{ \Carbon\Carbon::parse($item->tanggal)->diffForHumans() }}
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("fileInput");
    const form = document.getElementById("uploadForm");
    input.addEventListener("change", function () {
        form.submit();
    });
});
</script>

@endsection