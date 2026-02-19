@extends('layouts.sentara')

@section('content')

<div class="max-w-6xl mx-auto space-y-8">

    <!-- HEADER -->
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold text-gray-800">
            Rekomendasi Layanan
        </h2>

        <!-- Dropdown -->
        <form method="GET">
            <select name="destinasi"
                onchange="this.form.submit()"
                class="bg-white border border-gray-300 text-sm rounded-lg px-4 py-2 shadow-sm
                       focus:ring-2 focus:ring-blue-400 focus:outline-none">

                <option value="">Semua Destinasi</option>

                @foreach($destinasiList as $d)
                    <option value="{{ $d }}">
                        {{ $d }}
                    </option>
                @endforeach

            </select>
        </form>
    </div>

    <!-- CARD GRID -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- ISU UTAMA -->
        <div class="bg-white rounded-xl shadow p-6 text-center">
            <h3 class="text-gray-600 font-semibold mb-2">
                Isu Utama:
            </h3>
            <p class="text-lg font-bold text-gray-800">
                {{ $isuUtama }}
            </p>
        </div>

        <!-- KEYWORD -->
        <div class="bg-white rounded-xl shadow p-6 text-center">
            <h3 class="text-gray-600 font-semibold mb-2">
                Kata Kunci Dominan:
            </h3>
            <p class="text-lg font-bold text-gray-800">
                {{ $kataDominan }}
            </p>
        </div>

        <!-- SARAN -->
        <div class="bg-white rounded-xl shadow p-6 text-center">
            <h3 class="text-gray-600 font-semibold mb-2">
                Saran:
            </h3>
            <p class="text-lg font-bold text-gray-800">
                {{ $saran }}
            </p>
        </div>

    </div>

</div>

@endsection
