@extends('layouts.sentara')

@section('content')

<div class="max-w-6xl mx-auto space-y-8">

    <!-- HEADER -->
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold text-gray-800">
            Data Ulasan
        </h2>

        <select
            class="bg-white border border-gray-300 text-sm rounded-lg px-4 py-2 shadow-sm
                   focus:ring-2 focus:ring-blue-400 focus:outline-none">
            <option>Semua Destinasi</option>
            <option>Pantai Papuma</option>
            <option>Pantai Watu Ulo</option>
            <option>Taman Botani Sukorambi</option>
            <option>Rembangan</option>
        </select>
    </div>

    <!-- BUTTON AMBIL DATA -->
    <div>
        <form action="{{ route('ulasan.ambil') }}" method="POST">
            @csrf
            <button
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold
                       px-6 py-2 rounded-lg shadow">
                Ambil Data Terbaru
            </button>
        </form>
    </div>

    <!-- TABLE DATA MENTAH -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">
            Data Ulasan Mentah
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-4 py-3">Destinasi</th>
                        <th class="px-4 py-3">Reviewer</th>
                        <th class="px-4 py-3">Ulasan</th>
                        <th class="px-4 py-3">Tanggal</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($mentah as $row)
                        <tr class="border-b">
                            <td class="px-4 py-3">{{ $row->nama_wisata }}</td>
                            <td class="px-4 py-3">{{ $row->reviewer_name }}</td>
                            <td class="px-4 py-3">{{ $row->ulasan }}</td>
                            <td class="px-4 py-3">{{ $row->tanggal }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-400">
                                Belum ada data mentah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    <!-- BUTTON PREPROCESSING -->
    <div>
        <form action="{{ route('ulasan.analisis') }}" method="POST">
            @csrf
            <button
                class="bg-green-600 hover:bg-green-700 text-white font-semibold
                       px-6 py-2 rounded-lg shadow">
                Preprocessing Data
            </button>
        </form>
    </div>

    <!-- HASIL PREPROCESSING -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">
            Hasil Preprocessing Data
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-4 py-3">Ulasan Asli</th>
                        <th class="px-4 py-3">Cleaning + Case Folding</th>
                        <th class="px-4 py-3">Ulasan Bersih Final (Stemming)</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($preprocessing as $row)
                        <tr class="border-b">
                        <td class="px-4 py-3">{{ $row->ulasan_asli }}</td>

                        <!-- gabungan cleaning + lowercase -->
                        <td class="px-4 py-3">{{ $row->cleaning }}</td>

                        <!-- hasil akhir setelah stopword + slang + stemming -->
                        <td class="px-4 py-3">{{ $row->stemming }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-6 text-gray-400">
                                Belum ada hasil preprocessing.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>

@endsection
