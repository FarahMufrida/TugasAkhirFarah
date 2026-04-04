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
        <button class="bg-blue-600 text-white px-5 py-2 rounded">Ambil Data Terbaru</button>
    </form>

    <!-- ================= TABLE ================= -->
    <div class="bg-white rounded-xl shadow p-6">

        <h3 class="font-semibold mb-4">Data Ulasan Mentah</h3>

        <div class="border rounded overflow-hidden">

            <!-- HEADER -->
            <table class="w-full table-fixed text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 w-1/4">Wisata</th>
                        <th class="px-4 py-3 w-1/6">Rating</th>
                        <th class="px-4 py-3 w-2/4">Ulasan</th>
                        <th class="px-4 py-3 w-1/6">Tanggal</th>
                    </tr>
                </thead>
            </table>

            <!-- BODY SCROLL -->
            <div class="max-h-[300px] overflow-y-auto">
                <table class="w-full table-fixed text-sm">
                    <tbody>
                        @forelse($mentah as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $item->wisata }}</td>
                            <td class="px-4 py-2">{{ $item->rating }}</td>
                            <td class="px-4 py-2 break-words line-clamp-3">
                                {{ $item->ulasan }}
                            </td>
                            <td class="px-4 py-2">{{ $item->tanggal }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-400">
                                Tidak ada data
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <!-- PAGINATION SIMPLE -->
        <div class="flex justify-center mt-6 gap-2 text-sm">

            @if($mentah->onFirstPage())
                <span class="text-gray-400">Prev</span>
            @else
                <a href="{{ $mentah->previousPageUrl() }}">Prev</a>
            @endif

            @for ($i = 1; $i <= $mentah->lastPage(); $i++)
                @if ($i == $mentah->currentPage())
                    <span class="font-bold text-blue-600">{{ $i }}</span>
                @elseif ($i <= 5 || $i == $mentah->lastPage())
                    <a href="{{ $mentah->url($i) }}">{{ $i }}</a>
                @elseif ($i == 6)
                    <span>...</span>
                @endif
            @endfor

            @if($mentah->hasMorePages())
                <a href="{{ $mentah->nextPageUrl() }}">Next</a>
            @else
                <span class="text-gray-400">Next</span>
            @endif

        </div>

    </div>

    <!-- BUTTON ANALISIS -->
    <form action="{{ route('ulasan.analisis') }}" method="POST">
        @csrf
        <button class="bg-green-600 text-white px-5 py-2 rounded">Proses Analisis</button>
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
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection