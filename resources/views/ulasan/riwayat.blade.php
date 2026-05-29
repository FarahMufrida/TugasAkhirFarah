@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                Riwayat Analisis
            </h2>

            <p class="text-sm text-gray-500">
                Periode tersimpan otomatis setiap kali Ambil Data dijalankan.
            </p>
        </div>

        {{-- TAMBAHAN BUTTON CETAK TAHUNAN --}}
{{-- TAMBAHAN BUTTON CETAK TAHUNAN --}}
<form
    action="{{ route('laporan.tahunan') }}"
    method="GET"
    class="flex items-center gap-3">

    <div class="relative">

        <select
            name="tahun"
            required
            class="appearance-none
                   border-2 border-blue-500
                   rounded-2xl
                   pl-4 pr-9
                   py-2.5
                   text-sm font-medium
                   bg-white
                   shadow-sm
                   cursor-pointer
                   focus:outline-none
                   focus:ring-2
                   focus:ring-blue-300">

            @for($tahun = date('Y'); $tahun >= 2024; $tahun--)
                <option value="{{ $tahun }}">{{ $tahun }}</option>
            @endfor

        </select>

    </div>

    <button
        type="submit"
        class="bg-blue-600
               hover:bg-blue-700
               text-white
               px-5 py-2.5
               rounded-2xl
               text-sm font-medium
               shadow">
        Cetak Tahunan
    </button>

</form>

    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded whitespace-pre-line">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow p-6">

        <h3 class="font-semibold text-gray-800 mb-1">
            Import Data Lama dari CSV
        </h3>

        <p class="text-sm text-gray-500 mb-4">
            Gunakan untuk memasukkan ulasan historis. Kolom wajib:
            wisata, rating, ulasan, tanggal.
            Kolom reviewer boleh ada.
        </p>

        <form
            action="{{ route('riwayat.import') }}"
            method="POST"
            enctype="multipart/form-data"
            class="grid grid-cols-1 md:grid-cols-[180px_1fr_auto] gap-3 md:items-center">

            @csrf

            <input
                type="month"
                name="periode_bulan"
                value="{{ now()->format('Y-m') }}"
                class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-[150px]"
                required>

            <input
                type="file"
                name="file"
                accept=".csv,.txt"
                class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-0"
                required>

            <button
                class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm shadow">

                Import CSV

            </button>

        </form>

        @if($errors->any())
            <div class="mt-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-left">

                    <tr>

                        <th class="px-4 py-3">
                            Periode
                        </th>

                        <th class="px-4 py-3 text-center">
                            Total Ulasan
                        </th>

                        <th class="px-4 py-3 text-center">
                            Hasil Analisis
                        </th>

                        <th class="px-4 py-3 text-center">
                            Wisata
                        </th>

                        <th class="px-4 py-3">
                            Terakhir Analisis
                        </th>

                        <th class="px-4 py-3 text-center">
                            Aksi
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y">

                    @forelse($riwayat as $item)

                        <tr class="hover:bg-gray-50">

                            <td class="px-4 py-3 font-semibold text-gray-800">
                                {{ $item->nama }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                {{ $item->total_ulasan }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                {{ $item->total_hasil }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                {{ $item->total_wisata }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">

                                {{ $item->terakhir_analisis
                                ? \Carbon\Carbon::parse(
                                $item->terakhir_analisis
                                )->format('d M Y H:i')
                                : '-' }}

                            </td>

                            <td class="px-4 py-3">

                                <div class="flex flex-wrap justify-center gap-2">

                                    <a
                                    href="{{ route('dashboard',
                                    ['periode_id'=>$item->id,
                                    'tab'=>'analisis']) }}"

                                    class="px-3 py-1 rounded-lg
                                    bg-green-50 text-green-600
                                    hover:bg-green-100">

                                        Analisis

                                    </a>

                                    <a
                                    href="{{ route('ulasan.index',
                                    ['periode_id'=>$item->id]) }}"

                                    class="px-3 py-1 rounded-lg
                                    bg-gray-100 text-gray-700
                                    hover:bg-gray-200">

                                        Ulasan

                                    </a>

                                    {{-- TAMBAHAN CETAK BULANAN --}}
                                    <a
                                    href="{{ route('laporan.bulanan', $item->id) }}"
                                    class="px-3 py-1 rounded-lg
                                    bg-blue-50 text-blue-600
                                    hover:bg-blue-100">
                                        Cetak Laporan
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                            colspan="6"
                            class="px-4 py-8 text-center text-gray-400">

                                Belum ada riwayat analisis.
                                Jalankan Ambil Data terlebih dahulu.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- ================= PAGINATION ================= --}}

    @if($riwayat->hasPages())
        @php
            $current = $riwayat->currentPage();
            $last    = $riwayat->lastPage();
            $query   = http_build_query(request()->except('page'));
            $window  = collect(range(max(1, $current - 2), min($last, $current + 2)));
        @endphp

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600">

            {{-- Info --}}
            <p>Menampilkan {{ $riwayat->firstItem() }} – {{ $riwayat->lastItem() }} dari {{ $riwayat->total() }} data</p>

            {{-- Tombol --}}
            <div class="flex items-center gap-1">

                {{-- « --}}
                @if($riwayat->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg border bg-gray-100 text-gray-400 cursor-not-allowed">&laquo;</span>
                @else
                    <a href="{{ $riwayat->previousPageUrl() }}&{{ $query }}" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition">&laquo;</a>
                @endif

                {{-- Halaman 1 --}}
                @if(!$window->contains(1))
                    <a href="{{ $riwayat->url(1) }}&{{ $query }}" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition">1</a>
                    @if($window->min() > 2)
                        <span class="px-2 py-1.5 text-gray-400">...</span>
                    @endif
                @endif

                {{-- Window --}}
                @foreach($window as $page)
                    @if($page == $current)
                        <span class="px-3 py-1.5 rounded-lg border bg-blue-600 text-white font-semibold">{{ $page }}</span>
                    @else
                        <a href="{{ $riwayat->url($page) }}&{{ $query }}" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Halaman terakhir --}}
                @if(!$window->contains($last))
                    @if($window->max() < $last - 1)
                        <span class="px-2 py-1.5 text-gray-400">...</span>
                    @endif
                    <a href="{{ $riwayat->url($last) }}&{{ $query }}" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition">{{ $last }}</a>
                @endif

                {{-- » --}}
                @if($riwayat->hasMorePages())
                    <a href="{{ $riwayat->nextPageUrl() }}&{{ $query }}" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition">&raquo;</a>
                @else
                    <span class="px-3 py-1.5 rounded-lg border bg-gray-100 text-gray-400 cursor-not-allowed">&raquo;</span>
                @endif

            </div>
        </div>
    @endif

    </div>

</div>

@endsection