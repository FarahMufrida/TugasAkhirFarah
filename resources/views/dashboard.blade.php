@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto">

    {{-- ALERT --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded whitespace-pre-line">
            {{ session('error') }}
        </div>
    @endif

    {{-- PESAN TIDAK ADA DATA --}}
    @if($noDataPesan ?? null)
        <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4 mb-6 rounded flex items-center gap-2">
            <span>📅</span> <span>{{ $noDataPesan }}</span>
        </div>
    @endif

    @if($periodeAktif && !$hasAnalisis)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
            Data ulasan sudah tersedia, tetapi belum dilakukan analisis sentimen.
        </div>
    @endif

    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600 font-medium">
                Periode aktif: {{ $periodeAktif->nama ?? 'Belum ada data' }}
            </span>
            <a href="{{ route('riwayat.index') }}" class="text-sm text-blue-600 hover:underline">
                Lihat riwayat
            </a>
        </div>
        <div class="flex items-center gap-3">
            @if($periodeAktif)
                <span class="text-xs text-gray-400">
                    Terakhir update: {{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('d M Y H:i') : '-' }}
                </span>
            @endif
            <form method="GET"
                action="{{ route('dashboard') }}"
                class="flex flex-col md:flex-row gap-3 md:items-center">

                {{-- HAPUS hidden periode_id, pakai periode_bulan saja --}}

                <input type="month"
                    name="periode_bulan"
                    value="{{ request('periode_bulan', now()->format('Y-m')) }}"
                    max="{{ now()->format('Y-m') }}"
                    onchange="this.form.submit()"
                    class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-[150px]">

               

            </form>
        </div>
    </div>

    <div x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'dashboard' }" class="mb-6">

        <!-- TAB -->
        <div class="bg-blue-700 rounded-xl p-1 flex space-x-2 shadow mb-6">
            <button @click="tab='dashboard'"
                :class="tab==='dashboard' ? 'bg-white text-blue-700 shadow' : 'text-white hover:bg-blue-600'"
                class="flex-1 py-3 rounded-lg text-sm font-medium transition">
                Dashboard Sentara
            </button>
            <button @click="tab='analisis'"
                :class="tab==='analisis' ? 'bg-white text-blue-700 shadow' : 'text-white hover:bg-blue-600'"
                class="flex-1 py-3 rounded-lg text-sm font-medium transition">
                Hasil Analisis Sentimen
            </button>
            <button @click="tab='rekomendasi'"
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

                {{-- BAR CHART --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="font-semibold mb-4 text-gray-700">Sentimen per Destinasi</h3>
                    <div class="h-[300px]">
                        <canvas id="destinationChart"></canvas>
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
                    <div id="wordCloud" class="w-full h-[240px] overflow-hidden flex flex-wrap content-center justify-center gap-3 text-center">
                        <p class="text-sm text-gray-400">Belum ada kata dominan.</p>
                    </div>
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

    // BAR CHART SENTIMEN PER DESTINASI
    const destinationData = @json($chartDestinasi);
    const destinationCanvas = document.getElementById('destinationChart');
    if (destinationCanvas) {
        if (window.destinationChartInstance) window.destinationChartInstance.destroy();
        window.destinationChartInstance = new Chart(destinationCanvas, {
            type: 'bar',
            data: {
                labels: destinationData.map(item => item.wisata),
                datasets: [
                    {
                        label: 'Positif',
                        data: destinationData.map(item => Number(item.positif || 0)),
                        backgroundColor: 'rgba(96, 165, 250, 0.88)',
                        borderColor: '#3B82F6',
                        borderWidth: 1,
                        borderRadius: 3,
                    },
                    {
                        label: 'Negatif',
                        data: destinationData.map(item => Number(item.negatif || 0)),
                        backgroundColor: 'rgba(248, 113, 113, 0.9)',
                        borderColor: '#EF4444',
                        borderWidth: 1,
                        borderRadius: 3,
                    },
                    {
                        label: 'Netral',
                        data: destinationData.map(item => Number(item.netral || 0)),
                        backgroundColor: 'rgba(251, 191, 36, 0.72)',
                        borderColor: '#F59E0B',
                        borderWidth: 1,
                        borderRadius: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'center',
                        labels: {
                            boxWidth: 38,
                            boxHeight: 10,
                            color: '#4B5563',
                            font: { size: 12 },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.parsed.y} ulasan`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(156, 163, 175, 0.22)' },
                        ticks: {
                            color: '#4B5563',
                            maxRotation: 14,
                            minRotation: 14,
                            font: { size: 12 },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#4B5563',
                            precision: 0,
                        },
                        grid: { color: 'rgba(156, 163, 175, 0.28)' },
                    },
                },
                categoryPercentage: 0.72,
                barPercentage: 0.82,
            },
        });
    }

    // WORD CLOUD tanpa library eksternal agar tetap tampil stabil.
    const text = @json($allText);
    const wordCloud = document.getElementById('wordCloud');
    const stopwords = new Set([
        'yang','dan','dari','untuk','dengan','ini','itu','tidak','ada','juga',
        'sangat','lebih','sudah','bisa','tapi','karena','pada','atau','saya',
        'kami','mereka','nya','jadi','kalau','dalam','akan','saat','tempat'
    ]);

    if (wordCloud && text.trim().length > 0) {
        const wordCount = {};
        text.toLowerCase().split(/\s+/).forEach(word => {
            const cleanWord = word.replace(/[^a-z]/g, '');
            if (cleanWord.length > 3 && !stopwords.has(cleanWord)) {
                wordCount[cleanWord] = (wordCount[cleanWord] || 0) + 1;
            }
        });

        const words = Object.entries(wordCount)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 28);

        if (words.length > 0) {
            const max = words[0][1] || 1;
            const colors = ['text-blue-700', 'text-green-600', 'text-red-500', 'text-yellow-600', 'text-indigo-600'];
            wordCloud.innerHTML = words.map(([word, count], index) => {
                const size = 13 + Math.round((count / max) * 20);
                const weight = count === max ? 800 : 600;
                return `<span class="${colors[index % colors.length]} inline-block" style="font-size:${size}px;font-weight:${weight}" title="${count}x">${word}</span>`;
            }).join('');
        }
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