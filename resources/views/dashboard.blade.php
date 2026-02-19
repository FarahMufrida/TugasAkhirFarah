@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-semibold text-gray-800">
            Dashboard
        </h2>

        <!-- <select
            class="bg-white border border-gray-300 text-sm rounded-lg px-4 py-2 shadow-sm
                   focus:ring-2 focus:ring-blue-400 focus:outline-none">
            <option>Semua Destinasi</option>
            <option>Pantai Papuma</option>
            <option>Pantai Watu Ulo</option>
            <option>Taman Botani Sukorambi</option>
            <option>Rembangan</option>
        </select> -->
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
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

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

</div>



@endsection
