@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto">

    {{-- ALERT --}}
    @if(!$hasAnalisis)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
            Data ulasan sudah tersedia, tetapi belum dilakukan analisis sentimen.
        </div>
    @endif

    {{-- DROPDOWN PERIODE --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600 font-medium">Periode Analisis:</span>
            <form method="GET" action="{{ route('dashboard') }}" id="periode-form">
                <input type="hidden" name="tab" id="tab-hidden" value="{{ request('tab', 'dashboard') }}">
                <input type="hidden" name="wisata" value="{{ request('wisata') }}">
                <input type="hidden" name="destinasi" value="{{ request('destinasi') }}">
                <select name="periode_id" onchange="document.getElementById('periode-form').submit()"
                    class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm">
                    @foreach($periodeList as $p)
                        <option value="{{ $p->id }}" {{ ($periodeAktif && $periodeAktif->id == $p->id) ? 'selected' : '' }}>
                            {{ $p->nama }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        @if($periodeAktif)
            <span class="text-xs text-gray-400">
                Data dianalisis: {{ \Carbon\Carbon::parse($periodeAktif->created_at)->format('d M Y') }}
            </span>
        @endif
    </div>

    <div x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'dashboard' }" class="mb-6">

        <!-- TAB -->
        <div class="bg-blue-700 rounded-xl p-1 flex space-x-2 shadow mb-6">
            <button @click="tab='dashboard'; document.getElementById('tab-hidden').value='dashboard'"
                :class="tab==='dashboard' ? 'bg-white text-blue-700 shadow' : 'text-white hover:bg-blue-600'"
                class="flex-1 py-3 rounded-lg text-sm font-medium transition">
                Dashboard Sentara
            </button>
            <button @click="tab='analisis'; document.getElementById('tab-hidden').value='analisis'"
                :class="tab==='analisis' ? 'bg-white text-blue-700 shadow' : 'text-white hover:bg-blue-600'"
                class="flex-1 py-3 rounded-lg text-sm font-medium transition">
                Hasil Analisis Sentimen
            </button>
            <button @click="tab='rekomendasi'; document.getElementById('tab-hidden').value='rekomendasi'"
                :class="tab==='rekomendasi' ? 'bg-white text-blue-700 shadow' : 'text-white hover:bg-blue-600'"
                class="flex-1 py-3 rounded-lg text-sm font-medium transition">
                Rekomendasi Layanan
            </button>
        </div>

        <!-- ================= DASHBOARD ================= -->
        <div x-show="tab === 'dashboard'">

            {{-- Statistik Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-5">
                    <p class="text-gray-500 text-sm">Total Ulasan</p>
                    <h3 class="text-2xl font-bold mt-2 text-gray-900">{{ $stats['total'] }}</h3>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <p class="text-gray-500 text-sm">Sentimen Positif</p>
                    <h3 class="text-2xl font-bold mt-2 text-green-600">{{ $stats['positif_persen'] }}%</h3>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <p class="text-gray-500 text-sm">Sentimen Negatif</p>
                    <h3 class="text-2xl font-bold mt-2 text-red-600">{{ $stats['negatif_persen'] }}%</h3>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <p class="text-gray-500 text-sm">Sentimen Netral</p>
                    <h3 class="text-2xl font-bold mt-2 text-yellow-500">{{ $stats['netral_persen'] }}%</h3>
                </div>
            </div>

            {{-- Chart Section --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                {{-- PIE CHART --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="font-semibold mb-4 text-gray-700">Distribusi Sentimen</h3>
                    <div style="height:300px;">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>

                {{-- BAR CHART - PURE HTML --}}
                {{-- BAR CHART - PURE HTML VERTICAL --}}
<div class="bg-white rounded-xl shadow p-6">
    <h3 class="font-semibold mb-4 text-gray-700">Sentimen per Destinasi</h3>
    {{-- Legend --}}
    <div class="flex gap-4 mb-4 text-xs">
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-500 inline-block"></span> Positif</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-400 inline-block"></span> Negatif</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-400 inline-block"></span> Netral</span>
    </div>
    @php
        $maxVal = 1;
        foreach($chartDestinasi as $item) {
            $maxVal = max($maxVal, $item->positif, $item->negatif, $item->netral);
        }
    @endphp
    <div class="flex items-end gap-4 overflow-x-auto" style="height:220px;">
        @foreach($chartDestinasi as $item)
        @php
            $hPos = round(($item->positif / $maxVal) * 180);
            $hNeg = round(($item->negatif / $maxVal) * 180);
            $hNet = round(($item->netral  / $maxVal) * 180);
            $label = strlen($item->wisata) > 12
                ? substr($item->wisata, 0, 12) . '...'
                : $item->wisata;
        @endphp
        <div class="flex flex-col items-center flex-1 min-w-[60px]">
            <div class="flex items-end gap-1 w-full justify-center" style="height:180px;">
                {{-- Positif --}}
                <div class="relative group flex-1" style="height:{{ $hPos }}px; background:#3B82F6; border-radius:4px 4px 0 0; min-width:10px;">
                    <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-xs text-blue-600 font-semibold">{{ $item->positif }}</span>
                </div>
                {{-- Negatif --}}
                <div class="relative group flex-1" style="height:{{ $hNeg }}px; background:#F87171; border-radius:4px 4px 0 0; min-width:10px;">
                    <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-xs text-red-500 font-semibold">{{ $item->negatif }}</span>
                </div>
                {{-- Netral --}}
                <div class="relative group flex-1" style="height:{{ $hNet }}px; background:#FBBF24; border-radius:4px 4px 0 0; min-width:10px;">
                    <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-xs text-yellow-500 font-semibold">{{ $item->netral }}</span>
                </div>
            </div>
            <p class="text-xs text-gray-500 text-center mt-2 leading-tight" style="max-width:70px; word-break:break-word;">{{ $label }}</p>
        </div>
        @endforeach
    </div>
</div>

            </div>

            {{-- Section Bawah --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow p-6 h-[320px]">
                    <h3 class="font-semibold mb-6 text-gray-700">Total Data per Destinasi</h3>
                    <div class="space-y-4 text-sm text-gray-600">
                        @foreach($totalPerWisata as $item)
                            <div class="flex justify-between border-b pb-3">
                                <span>{{ $item->wisata }}</span>
                                <span class="font-semibold text-gray-800">{{ $item->total }} ulasan</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 h-[320px]">
                    <h3 class="font-semibold mb-6 text-gray-700">Word Cloud</h3>
                    <div id="wordCloud" class="w-full h-[240px] overflow-hidden"></div>
                </div>
            </div>

        </div>

        <!-- ================= ANALISIS ================= -->
        <div x-show="tab === 'analisis'" id="analisis-content">
            @include('analisis.index')
        </div>

        <!-- ================= REKOMENDASI ================= -->
        <div x-show="tab === 'rekomendasi'" id="rekomendasi-content">
            @include('rekomendasi.index')
        </div>

    </div>
</div>

{{-- SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/wordcloud2.js/1.1.2/wordcloud2.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // PIE CHART
    const pieData = JSON.parse('{!! json_encode($chartSentimen) !!}');
    const pieCanvas = document.getElementById('pieChart');
    if (window.pieChartInstance) window.pieChartInstance.destroy();
    window.pieChartInstance = new Chart(pieCanvas, {
        type: 'pie',
        data: {
            labels: pieData.map(i => i.sentimen),
            datasets: [{
                data: pieData.map(i => i.total),
                backgroundColor: ['#3B82F6', '#EF4444', '#F59E0B']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false
        }
    });

    // WORD CLOUD
    const text = `{!! addslashes($allText) !!}`;
    if (text.length > 0) {
        const wordCount = {};
        text.split(" ").forEach(word => {
            if (word.length > 3) wordCount[word] = (wordCount[word] || 0) + 1;
        });
        WordCloud(document.getElementById('wordCloud'), { list: Object.entries(wordCount) });
    }

});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const container = document.getElementById('analisis-content');

    // FILTER AJAX
    document.addEventListener('submit', function(e){
        if(e.target.id === 'filter-form'){
            e.preventDefault();
            const params = new URLSearchParams(new FormData(e.target)).toString();
            fetch('/dashboard?' + params)
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    container.innerHTML = doc.getElementById('analisis-content').innerHTML;
                });
        }
    });

    // PAGINATION AJAX
    document.addEventListener('click', function(e){
        const link = e.target.closest('a');
        if(link && link.href.includes('page=')){
            e.preventDefault();
            fetch(link.href)
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    container.innerHTML = doc.getElementById('analisis-content').innerHTML;
                });
        }
    });

});
</script>

@endsection