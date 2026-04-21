@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto">

    {{-- ALERT --}}
    @if(!$hasAnalisis)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
            Data ulasan sudah tersedia, tetapi belum dilakukan analisis sentimen.
        </div>
    @endif

<div x-data="{ tab: 'dashboard' }" class="mb-6">

    <!-- TAB -->
    <div class="bg-blue-700 rounded-xl p-1 flex space-x-2 shadow mb-6">

        <button @click="tab='dashboard'"
            :class="tab==='dashboard' 
                ? 'bg-white text-blue-700 shadow' 
                : 'text-white hover:bg-blue-600'"
            class="flex-1 py-3 rounded-lg text-sm font-medium transition">
            Dashboard Sentara
        </button>

        <button @click="tab='analisis'"
            :class="tab==='analisis' 
                ? 'bg-white text-blue-700 shadow' 
                : 'text-white hover:bg-blue-600'"
            class="flex-1 py-3 rounded-lg text-sm font-medium transition">
            Hasil Analisis Sentimen
        </button>

        <button @click="tab='rekomendasi'"
            :class="tab==='rekomendasi' 
                ? 'bg-white text-blue-700 shadow' 
                : 'text-white hover:bg-blue-600'"
            class="flex-1 py-3 rounded-lg text-sm font-medium transition">
            Rekomendasi Layanan
        </button>

    </div>
    
    <div x-show="tab === 'dashboard'">
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
            <h3 class="font-semibold mb-4 text-gray-700">Distribusi Sentimen</h3>
            <div style="height:300px;">
        <canvas id="pieChart"></canvas>
    </div>
        </div>

        <!-- Bar Chart -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-4 text-gray-700">Sentimen per Destinasi</h3>
            <div style="height:300px;">
        <canvas id="barChart"></canvas>
    </div>
        </div>

    </div>

    {{-- Section Bawah --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Total Data per Destinasi -->
        <div class="bg-white rounded-xl shadow p-6 h-[320px]">
            <h3 class="font-semibold mb-6 text-gray-700">
                Total Data per Destinasi
            </h3>

            <div class="space-y-4 text-sm text-gray-600">
                @foreach($totalPerWisata as $item)
                    <div class="flex justify-between border-b pb-3">
                        <span>{{ $item->wisata }}</span>
                        <span class="font-semibold text-gray-800">
                            {{ $item->total }} ulasan
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- WORD CLOUD -->
        <div class="bg-white rounded-xl shadow p-6 h-[320px]">
            <h3 class="font-semibold mb-6 text-gray-700">
                Word Cloud
            </h3>
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


{{-- ========================= --}}
{{-- SCRIPT SECTION --}}
{{-- ========================= --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/wordcloud2.js/1.1.2/wordcloud2.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // =============================
    // PIE CHART
    // =============================
    const pieData = JSON.parse('{!! json_encode($chartSentimen) !!}');

    const pieLabels = pieData.map(item => item.sentimen);
    const pieValues = pieData.map(item => item.total);

    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieValues
            }]
        }
    });

    // =============================
    // BAR CHART
    // =============================
    const barData = JSON.parse('{!! json_encode($chartDestinasi) !!}');

    const labelsBar = barData.map(item => item.wisata);
    const positif = barData.map(item => item.positif);
    const negatif = barData.map(item => item.negatif);
    const netral = barData.map(item => item.netral);

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: labelsBar,
            datasets: [
                { label: 'Positif', data: positif },
                { label: 'Negatif', data: negatif },
                { label: 'Netral', data: netral }
            ]
        }
    });

    // =============================
    // WORD CLOUD
    // =============================
    const text = `{!! $allText !!}`;

    if(text.length > 0){
        const words = text.split(" ");

        const wordCount = {};
        words.forEach(word => {
            if(word.length > 3){
                wordCount[word] = (wordCount[word] || 0) + 1;
            }
        });

        const list = Object.entries(wordCount);

        WordCloud(document.getElementById('wordCloud'), {
            list: list
        });
    }

});
</script>

<script>
    const tab = new URLSearchParams(window.location.search).get('tab');

    if (tab === 'analisis') {
        showTab('analisis');
    }
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const container = document.getElementById('analisis-content');

    // ================= FILTER AJAX =================
    document.addEventListener('submit', function(e){
        if(e.target.id === 'filter-form'){
            e.preventDefault();

            const formData = new FormData(e.target);
            const params = new URLSearchParams(formData).toString();

            fetch('/dashboard?' + params)
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newContent = doc.getElementById('analisis-content');
                    container.innerHTML = newContent.innerHTML;
                });
        }
    });

    // ================= PAGINATION AJAX =================
    document.addEventListener('click', function(e){
        const link = e.target.closest('a');

        if(link && link.href.includes('page=')){
            e.preventDefault();

            fetch(link.href)
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newContent = doc.getElementById('analisis-content');
                    container.innerHTML = newContent.innerHTML;
                });
        }
    });

});
</script>
@endsection