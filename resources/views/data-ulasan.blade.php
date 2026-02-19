@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto space-y-8">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">
            Data Ulasan Mentah
        </h2>

        <select
            class="bg-white border border-gray-300 text-sm rounded-lg px-4 py-2 shadow-sm
                   focus:ring-2 focus:ring-blue-400 focus:outline-none">
            <option>Semua Destinasi</option>
            <option>Pantai Papuma</option>
            <option>Sukorambi</option>
            <option>Watu Ulo</option>
        </select>
    </div>

    {{-- BUTTON SCRAPING --}}
    <div>
        <a href="{{ route('scrape.ulasan') }}"
           class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2 rounded-lg shadow">
            Ambil Data Terbaru
        </a>
    </div>

    {{-- DATA MENTAH TABLE --}}
    <div class="bg-white rounded-xl shadow p-6">

        <h3 class="font-semibold text-gray-700 mb-4">
            Hasil Data Scraping
        </h3>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-4 py-3">Destinasi</th>
                        <th class="px-4 py-3">Reviewer</th>
                        <th class="px-4 py-3">Ulasan</th>
                        <th class="px-4 py-3">Tanggal</th>
                    </tr>
                </thead>
                <tbody>

                    @forelse($ulasan as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3 font-medium">
                                {{ $item->nama_wisata }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $item->reviewer_name }}
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ Str::limit($item->ulasan, 80) }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $item->tanggal }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-500">
                                Data belum tersedia. Silakan scraping dulu.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

    </div>


    {{-- BUTTON ANALISIS --}}
    <!-- <div>
        <form action="{{ route('analisis.ulasan') }}" method="POST">
            @csrf
            <button type="submit"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg shadow">
                Analisis Data
            </button>
        </form>
    </div> -->


    {{-- HASIL ANALISIS --}}
    @if(isset($hasilAnalisis))

    <!-- <div class="bg-white rounded-xl shadow p-6">

        <h3 class="font-semibold text-gray-700 mb-4">
            Hasil Analisis Sentimen
        </h3>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-4 py-3">Destinasi</th>
                        <th class="px-4 py-3">Ulasan</th>
                        <th class="px-4 py-3">Sentimen</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($hasilAnalisis as $hasil)
                        <tr class="border-b">
                            <td class="px-4 py-3 font-medium">
                                {{ $hasil->nama_wisata }}
                            </td>

                            <td class="px-4 py-3">
                                {{ Str::limit($hasil->ulasan, 80) }}
                            </td>

                            <td class="px-4 py-3">
                                @if($hasil->sentimen == "positif")
                                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                        Positif
                                    </span>
                                @elseif($hasil->sentimen == "negatif")
                                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                        Negatif
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
                                        Netral
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </div> -->

    @endif

</div>

@endsection
