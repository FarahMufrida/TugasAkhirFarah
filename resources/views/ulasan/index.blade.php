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
            <option>Teluk Love (Payangan)</option>
            <option>Kebun Teh Gunung Gambir </option>
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

        <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-4 py-3">Destinasi</th>
                        <th class="px-4 py-3">Reviewer</th>
                        <th class="px-4 py-3">Rating</th>
                        <th class="px-4 py-3">Ulasan</th>
                        <th class="px-4 py-3">Tanggal</th>
                      
                    </tr>
                </thead>

                <tbody>
@forelse($mentah as $data)
<tr>
    <td class="px-4 py-3">{{ $data->destinasi }}</td>
    <td class="px-4 py-3">{{ $data->reviewer }}</td>
    <td class="px-4 py-3">{{ $data->rating }}</td>
    <td class="px-4 py-3">{{ $data->ulasan }}</td>
    <td class="px-4 py-3">{{ $data->tanggal }}</td>
  
</tr>
@empty
<tr>
    <td colspan="6" class="text-center py-6 text-gray-400">
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
                Proses Analisis
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
                <thead class="sticky top-0 bg-gray-100">
                <tr class="bg-gray-100 text-gray-600">
                    <th class="px-4 py-3">Destinasi</th>
                    <th class="px-4 py-3">Ulasan Mentah</th>
                    <th class="px-4 py-3">Ulasan Bersih</th>
                </tr>
            </thead>

            <tbody>
            @forelse($preprocessing as $row)
            <tr class="border-b">

            <td class="px-4 py-3">
                {{ $row->destinasi }}
            </td>

            <td class="px-4 py-3">
                {{ $row->ulasan_asli }}
            </td>

            <td class="px-4 py-3">
                {{ $row->stemming }}
            </td>

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
