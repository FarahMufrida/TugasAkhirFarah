@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex justify-between items-start mb-8">
    <div>
        <h2 class="text-2xl font-semibold text-gray-800">
            Dashboard
        </h2>

        <!-- <p class="text-sm text-gray-500 mt-1">
            Terakhir diperbarui: 27 Februari 2026, 22:45
        </p>

        <p class="text-xs text-gray-400">
            Periode Data: Februari 2026
        </p> -->
    </div>
    
     <!-- Filter Destinasi -->
    <form method="GET" action="" class="ml-4">
        <select name="destinasi"
            class="bg-white border border-gray-300 text-sm rounded-lg px-4 py-2 shadow-sm
                   focus:ring-2 focus:ring-blue-400 focus:outline-none">

            <option value="">Semua Destinasi</option>
            <option value="papuma">Pantai Papuma</option>
            <option value="watuulo">Pantai Watu Ulo</option>
            <option value="teluklove">Teluk Love (Payangan)</option>
            <option value="gununggambir">Kebun Teh Gunung Gambir </option>

        </select>
    </form>
    </div>

    {{-- Statistik Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500 text-sm">Total Ulasan</p>
            <h3 class="text-2xl font-bold mt-2 text-gray-900">
                {{ $stats['total'] }}
            </h3>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500 text-sm">Sentimen Positif</p>
            <h3 class="text-2xl font-bold mt-2 text-green-600">
                {{ $stats['positif_persen'] }}%
            </h3>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500 text-sm">Sentimen Negatif</p>
            <h3 class="text-2xl font-bold mt-2 text-red-600">
                {{ $stats['negatif_persen'] }}%
            </h3>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500 text-sm">Sentimen Netral</p>
            <h3 class="text-2xl font-bold mt-2 text-yellow-500">
                {{ $stats['netral_persen'] }}%
            </h3>
        </div>

    </div>

 {{-- Chart Section --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- Pie Chart -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold mb-4 text-gray-700">
            Distribusi Sentimen
        </h3>
        <div class="h-[280px] flex justify-center items-center">
            <canvas id="pieChart"></canvas>
        </div>
    </div>

    <!-- Bar Chart -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold mb-4 text-gray-700">
            Sentimen per Destinasi
        </h3>
        <div class="h-[280px]">
            <canvas id="barChart"></canvas>
        </div>
    </div>

</div> {{-- TUTUP GRID CHART --}}


{{-- Section Bawah --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Total Data per Destinasi -->
    <div class="bg-white rounded-xl shadow p-6 h-[320px]">
        <h3 class="font-semibold mb-6 text-gray-700">
            Total Data per Destinasi
        </h3>

        <div class="space-y-4 text-sm text-gray-600">
            <div class="flex justify-between border-b pb-3">
                <span>Pantai Papuma</span>
                <span class="font-semibold text-gray-800">120 ulasan</span>
            </div>
            <div class="flex justify-between border-b pb-3">
                <span>Pantai Watu Ulo</span>
                <span class="font-semibold text-gray-800">85 ulasan</span>
            </div>
            <div class="flex justify-between border-b pb-3">
                <span>Teluk Love (Payangan)</span>
                <span class="font-semibold text-gray-800">95 ulasan</span>
            </div>
            <div class="flex justify-between">
                <span>Kebun Teh Gunung Gambir</span>
                <span class="font-semibold text-gray-800">60 ulasan</span>
            </div>
        </div>
    </div>

    <!-- WORD CLOUD -->
    <div class="bg-white rounded-xl shadow p-6 h-[320px]">
        <h3 class="font-semibold mb-6 text-gray-700">
            Word Cloud
        </h3>

        <div id="wordCloud" class="w-full h-[240px]"></div>
    </div>

</div>

</div>


</div>
</div>



@endsection