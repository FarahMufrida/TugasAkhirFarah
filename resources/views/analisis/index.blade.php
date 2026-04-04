@extends('layouts.sentara')

@section('content')

<div class="max-w-6xl mx-auto space-y-8">

    <!-- HEADER -->
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold text-gray-800">
            Hasil Analisis Sentimen
        </h2>

        <!-- Dropdown Filter -->
        <form method="GET" action="{{ route('analisis.index') }}">
    <select name="wisata"
        onchange="this.form.submit()"
        class="bg-white border border-gray-300 text-sm rounded-lg px-4 py-2 shadow-sm
               focus:ring-2 focus:ring-blue-400 focus:outline-none">

        <option value="">Semua Destinasi</option>

                 @foreach($wisataList as $dest)
            <option value="{{ $dest }}"
                {{ request('wisata') == $dest ? 'selected' : '' }}>
                {{ $dest }}
            </option>
         @endforeach
        </select>
    </form>
    </div>


    <!-- TABLE CARD -->
    <div class="bg-white rounded-xl shadow p-6">

        <h3 class="text-lg font-semibold text-gray-700 mb-4">
            Data Hasil Analisis
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-4 py-3">wisata</th>
                        <th class="px-4 py-3">Ulasan Terolah</th>
                        <th class="px-4 py-3">Sentimen</th>
                        <th class="px-4 py-3">Probabilitas</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($hasil as $row)
                        <tr class="border-b">

                            <!-- Destinasi -->
                            <td class="px-4 py-3 font-medium">
                                {{ $row->wisata }}
                            </td>

                            <!-- Ulasan -->
                            <td class="px-4 py-3 text-gray-700">
                                {{ $row->ulasan_terolah }}
                            </td>

                            <!-- Badge Sentimen -->
                            <td class="px-4 py-3">
                                @if($row->sentimen == 'positif')
                                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                        Positif
                                    </span>
                                @elseif($row->sentimen == 'negatif')
                                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                        Negatif
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
                                        Netral
                                    </span>
                                @endif
                            </td>

                            <!-- Probabilitas -->
                            <td class="px-4 py-3 font-semibold text-gray-700">
                                {{ number_format($row->probabilitas, 2) }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-400">
                                Belum ada hasil analisis sentimen.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <!-- PAGINATION -->
        <div class="mt-6">
            {{ $hasil->links() }}
        </div>

    </div>

</div>

@endsection
