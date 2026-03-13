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

    <!-- Isu Utama -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-gray-700 font-semibold mb-4">
            Isu Utama
        </h3>

        <div class="text-sm text-gray-600 space-y-2">
            <span class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium">
                Kebersihan
            </span>
            <span class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium">
                Parkir
            </span>
        </div>
    </div>

    <!-- Kata Kunci Dominan -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-gray-700 font-semibold mb-4">
            Kata Kunci Dominan
        </h3>

        <ul class="text-sm text-gray-600 space-y-2">
            <li class="flex justify-between">
                <span>kotor</span>
                <span class="font-semibold">15x</span>
            </li>
            <li class="flex justify-between">
                <span>parkir</span>
                <span class="font-semibold">10x</span>
            </li>
            <li class="flex justify-between">
                <span>mahal</span>
                <span class="font-semibold">7x</span>
            </li>
        </ul>
    </div>

    <!-- Saran -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-gray-700 font-semibold mb-4">
            Saran Perbaikan
        </h3>

        <p class="text-sm text-gray-600 leading-relaxed">
            Perlu peningkatan kebersihan area wisata serta evaluasi sistem
            pengelolaan parkir untuk meningkatkan kenyamanan pengunjung.
        </p>
    </div>

</div>

</div>

@endsection
