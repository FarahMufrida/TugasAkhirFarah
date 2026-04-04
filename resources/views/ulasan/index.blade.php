@extends('layouts.sentara')

@section('content')

<div class="max-w-6xl mx-auto space-y-8">

    <!-- HEADER -->
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold text-gray-800">Data Ulasan</h2>

        <select id="filterWisata" class="border rounded px-3 py-2 text-sm">
            <option value="">Semua Destinasi</option>
            <option value="Pantai Papuma" {{ request('wisata')=='Pantai Papuma'?'selected':'' }}>Pantai Papuma</option>
            <option value="Pantai Watu Ulo" {{ request('wisata')=='Pantai Watu Ulo'?'selected':'' }}>Pantai Watu Ulo</option>
            <option value="Teluk Love (Payangan)" {{ request('wisata')=='Teluk Love (Payangan)'?'selected':'' }}>Teluk Love</option>
            <option value="Kebun Teh Gunung Gambir" {{ request('wisata')=='Kebun Teh Gunung Gambir'?'selected':'' }}>Kebun Teh Gunung Gambir</option>
        </select>
    </div>

    <script>
    document.getElementById("filterWisata").addEventListener("change", function () {
        window.location.href = "/ulasan?wisata=" + encodeURIComponent(this.value);
    });
    </script>

    <!-- BUTTON -->
    <form action="{{ route('ulasan.ambil') }}" method="POST">
        @csrf
        <button class="bg-blue-600 text-white px-5 py-2 rounded">
            Ambil Data Terbaru
        </button>
    </form>

    <!-- ================= TABLE ================= -->
    <div class="bg-white rounded-xl shadow p-6">

        <h3 class="font-semibold mb-4">Data Ulasan Mentah</h3>

        <div class="border rounded overflow-hidden">

            <!-- HEADER -->
             <table class="w-full text-sm text-left table-fixed border border-gray-200 rounded-t-lg overflow-hidden">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="px-4 py-3 w-[15%]">Wisata</th>
                <th class="px-4 py-3 w-[10%] text-center">Rating</th>
                <th class="px-4 py-3 w-[60%]">Ulasan</th>
                <th class="px-4 py-3 w-[15%] text-center">Tanggal</th>
            </tr>
        </thead>
    </table>

    <!-- BODY -->
    <div class="max-h-[300px] overflow-y-auto border-x border-b border-gray-200 rounded-b-lg">
        <table class="w-full text-sm table-fixed">

            <tbody class="divide-y divide-gray-200">

                @foreach($mentah as $item)
                <tr class="hover:bg-gray-50 transition">

                    <!-- WISATA -->
                    <td class="px-4 py-3 w-[15%] font-medium text-gray-700">
                        {{ $item->wisata }}
                    </td>

                    <!-- RATING -->
                    <td class="px-4 py-3 w-[10%] text-center">
                         {{ $item->rating }}
                    </td>

                    <!-- ULASAN -->
                    <td class="px-4 py-3 w-[60%] text-black-700 leading-relaxed">
                        <div class="line-clamp-3">
                            {{ $item->ulasan }}
                        </div>
                    </td>

                    <!-- TANGGAL -->
                    <td class="px-4 py-3 w-[15%] text-center text-black-700 leading-relaxed">
                        {{ $item->tanggal }}
                    </td>

                </tr>
                @endforeach

            </tbody>
        </table>
    </div>

</div>

        <!-- INFO -->
        <div class="text-sm text-gray-500 mt-4 text-center">
            Showing {{ $mentah->firstItem() }} to {{ $mentah->lastItem() }} 
            of {{ $mentah->total() }} results
        </div>

        <!-- PAGINATION -->
        <div class="flex justify-center mt-6 items-center gap-2 text-sm">

    {{-- FIRST --}}
    @if ($mentah->currentPage() > 1)
        <a href="{{ $mentah->url(1) }}">«</a>
    @endif

    {{-- PREV --}}
    @if ($mentah->onFirstPage())
        <span class="text-gray-400">‹</span>
    @else
        <a href="{{ $mentah->previousPageUrl() }}">‹</a>
    @endif

    @php
        $start = max($mentah->currentPage() - 2, 1);
        $end = min($mentah->currentPage() + 2, $mentah->lastPage());
    @endphp

    {{-- NUMBER DINAMIS --}}
    @for ($i = $start; $i <= $end; $i++)
        @if ($i == $mentah->currentPage())
            <span class="px-3 py-1 bg-blue-600 text-white rounded-full">{{ $i }}</span>
        @else
            <a href="{{ $mentah->url($i) }}" class="px-3 py-1 hover:underline">
                {{ $i }}
            </a>
        @endif
    @endfor

    {{-- NEXT --}}
    @if ($mentah->hasMorePages())
        <a href="{{ $mentah->nextPageUrl() }}">›</a>
    @else
        <span class="text-gray-400">›</span>
    @endif

    {{-- LAST --}}
    @if ($mentah->currentPage() < $mentah->lastPage())
        <a href="{{ $mentah->url($mentah->lastPage()) }}">»</a>
    @endif

    </div>

    </div>

    <!-- BUTTON ANALISIS -->
    <form action="{{ route('ulasan.analisis') }}" method="POST">
        @csrf
        <button class="bg-green-600 text-white px-5 py-2 rounded">
            Proses Analisis
        </button>
    </form>

    <!-- PREPROCESSING -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold mb-4">Hasil Preprocessing</h3>

        <div class="max-h-[300px] overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="px-4 py-2">Wisata</th>
                        <th class="px-4 py-2">Ulasan Mentah</th>
                        <th class="px-4 py-2">Ulasan Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preprocessing as $row)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $row->wisata }}</td>
                        <td class="px-4 py-2">{{ $row->ulasan_asli }}</td>
                        <td class="px-4 py-2">{{ $row->stemming }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-4 text-gray-400">
                            Belum ada data
                        </td>
                    </tr>
                    <div class="flex justify-center mt-4 text-sm">
        
                    <!-- INFO -->
                        <div class="text-sm text-gray-500 mt-4 text-center">
                            Showing {{ $preprocessing->firstItem() }} to {{ $preprocessing->lastItem() }} 
                            of {{ $preprocessing->total() }} results
                        </div>

                        <!-- PAGINATION -->
                        <div class="flex justify-center mt-6 items-center gap-2 text-sm">

                    {{-- FIRST --}}
                    @if ($preprocessing->currentPage() > 1)
                        <a href="{{ $preprocessing->url(1) }}">«</a>
                    @endif

                    {{-- PREV --}}
                    @if ($preprocessing->onFirstPage())
                        <span class="text-gray-400">‹</span>
                    @else
                        <a href="{{ $preprocessing->previousPageUrl() }}">‹</a>
                    @endif

                    @php
                        $start = max($preprocessing->currentPage() - 2, 1);
                        $end = min($preprocessing->currentPage() + 2, $preprocessing->lastPage());
                    @endphp

                    {{-- NUMBER DINAMIS --}}
                    @for ($i = $start; $i <= $end; $i++)
                        @if ($i == $preprocessing->currentPage())
                            <span class="px-3 py-1 bg-blue-600 text-white rounded-full">{{ $i }}</span>
                        @else
                            <a href="{{ $preprocessing->url($i) }}" class="px-3 py-1 hover:underline">
                                {{ $i }}
                            </a>
                        @endif
                    @endfor

                    {{-- NEXT --}}
                    @if ($preprocessing->hasMorePages())
                        <a href="{{ $preprocessing->nextPageUrl() }}">›</a>
                    @else
                        <span class="text-gray-400">›</span>
                    @endif

                    {{-- LAST --}}
                    @if ($preprocessing->currentPage() < $preprocessing->lastPage())
                        <a href="{{ $preprocessing->url($preprocessing->lastPage()) }}">»</a>
                    @endif

                    </div>

                    </div>
                    <script>
                        document.getElementById("filterWisata").addEventListener("change", function () {
                            let wisata = this.value;

                            fetch(`/ulasan?wisata=${wisata}`)
                                .then(res => res.text())
                                .then(html => {
                                    document.querySelector("#tableContainer").innerHTML =
                                        new DOMParser().parseFromString(html, "text/html")
                                        .querySelector("#tableContainer").innerHTML;
                                });
                        });
                        </script>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection