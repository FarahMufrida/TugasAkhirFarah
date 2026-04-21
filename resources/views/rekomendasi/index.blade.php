<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rekomendasi Layanan</h2>
            <p class="text-sm text-gray-500">
                Rekomendasi dibuat berdasarkan hasil analisis sentimen ulasan wisata.
            </p>
        </div>

        <div class="flex gap-3">
            <select class="border rounded-lg px-3 py-2 text-sm">
                <option>Semua Destinasi</option>
            </select>

            <!-- <select class="border rounded-lg px-3 py-2 text-sm">
                <option>2020 - 2025</option>
            </select> -->
        </div>
    </div>

    {{-- SUMMARY --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-red-100 text-red-500 p-3 rounded-full text-xl">😟</div>
            <div>
                <p class="text-sm text-gray-500">Total Ulasan Negatif</p>
                <h3 class="text-xl font-bold">312</h3>
                <p class="text-xs text-red-500">25,06% dari total</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-green-100 text-green-600 p-3 rounded-full text-xl">😊</div>
            <div>
                <p class="text-sm text-gray-500">Tingkat Kepuasan</p>
                <h3 class="text-xl font-bold">68%</h3>
                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">
                    Sedang
                </span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-purple-100 text-purple-600 p-3 rounded-full text-xl">❗</div>
            <div>
                <p class="text-sm text-gray-500">Isu Dominan</p>
                <h3 class="text-xl font-bold">Kebersihan</h3>
                <p class="text-xs text-gray-500">45% dari total isu</p>
            </div>
        </div>

        <!-- <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full text-xl">📅</div>
            <div>
                <p class="text-sm text-gray-500">Periode Data</p>
                <h3 class="text-xl font-bold">2020 - 2025</h3>
                <p class="text-xs text-gray-500">6 tahun data</p>
            </div>
        </div> -->

    </div>

    {{-- GRID ANALISIS --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ISU UTAMA --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Isu Utama (Top 5)</h3>

            @php
                $isu = [
                    ['nama'=>'Kebersihan','persen'=>45,'warna'=>'red'],
                    ['nama'=>'Parkir','persen'=>30,'warna'=>'orange'],
                    ['nama'=>'Harga / Tiket','persen'=>15,'warna'=>'yellow'],
                    ['nama'=>'Fasilitas','persen'=>6,'warna'=>'green'],
                    ['nama'=>'Keramaian','persen'=>4,'warna'=>'blue'],
                ];
            @endphp

            @foreach($isu as $i => $item)
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $i+1 }}. {{ $item['nama'] }}</span>
                        <span>{{ $item['persen'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded">
                                            <div class="h-2 rounded 
                        @if($item['warna'] == 'red') bg-red-500 
                        @elseif($item['warna'] == 'orange') bg-orange-500 
                        @elseif($item['warna'] == 'yellow') bg-yellow-500 
                        @elseif($item['warna'] == 'green') bg-green-500 
                        @else bg-blue-500 
                        @endif"
                        style="width: {{ $item['persen'] }}%">
                    </div>
                    </div>
                </div>
            @endforeach

        </div>

        {{-- KEYWORD --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Kata Kunci Dominan</h3>

            @php
                $keywords = [
                    ['kotor',15],
                    ['parkir',10],
                    ['mahal',7],
                    ['sampah',6],
                    ['toilet',5],
                ];
            @endphp

            @foreach($keywords as $k)
                <div class="mb-3">
                    <div class="flex justify-between text-sm">
                        <span>{{ $k[0] }}</span>
                        <span>{{ $k[1] }}x</span>
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded mt-1">
                        <div class="bg-blue-500 h-2 rounded"
                             style="width: {{ $k[1]*5 }}%"></div>
                    </div>
                </div>
            @endforeach

        </div>

        {{-- SARAN --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Saran Perbaikan</h3>

            <div class="space-y-4 text-sm">

                <div class="flex gap-3">
                    <div class="text-green-500 text-xl">🧹</div>
                    <div>
                        <p class="font-semibold">Perbaikan Kebersihan</p>
                        <p class="text-gray-600">
                            Tambah tempat sampah & jadwal pembersihan rutin.
                        </p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <div class="text-orange-500 text-xl">🚗</div>
                    <div>
                        <p class="font-semibold">Penataan Parkir</p>
                        <p class="text-gray-600">
                            Sistem parkir lebih rapi dan transparan.
                        </p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <div class="text-yellow-500 text-xl">🏷️</div>
                    <div>
                        <p class="font-semibold">Evaluasi Harga</p>
                        <p class="text-gray-600">
                            Penyesuaian harga tiket agar lebih terjangkau.
                        </p>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- PRIORITAS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- CARD 1 --}}
        <div class="border border-red-300 rounded-xl p-5 bg-white">
            <h4 class="font-semibold text-red-600 mb-2">Prioritas 1</h4>
            <h3 class="text-lg font-bold mb-3">Kebersihan</h3>

            <ul class="text-sm space-y-2">
                <li>✔ Tambah tempat sampah</li>
                <li>✔ Jadwal pembersihan rutin</li>
                <li>✔ Petugas kebersihan tambahan</li>
            </ul>

            <div class="mt-4 text-xs bg-red-100 text-red-600 p-2 rounded">
                Dampak: Kepuasan meningkat signifikan
            </div>
        </div>

        {{-- CARD 2 --}}
        <div class="border border-orange-300 rounded-xl p-5 bg-white">
            <h4 class="font-semibold text-orange-600 mb-2">Prioritas 2</h4>
            <h3 class="text-lg font-bold mb-3">Parkir</h3>

            <ul class="text-sm space-y-2">
                <li>✔ Penataan area parkir</li>
                <li>✔ Tarif transparan</li>
                <li>✔ Petugas lebih ramah</li>
            </ul>

            <div class="mt-4 text-xs bg-orange-100 text-orange-600 p-2 rounded">
                Dampak: Mengurangi keluhan
            </div>
        </div>

        {{-- CARD 3 --}}
        <div class="border border-yellow-300 rounded-xl p-5 bg-white">
            <h4 class="font-semibold text-yellow-600 mb-2">Prioritas 3</h4>
            <h3 class="text-lg font-bold mb-3">Harga</h3>

            <ul class="text-sm space-y-2">
                <li>✔ Evaluasi harga tiket</li>
                <li>✔ Promo wisata</li>
                <li>✔ Diskon tertentu</li>
            </ul>

            <div class="mt-4 text-xs bg-yellow-100 text-yellow-600 p-2 rounded">
                Dampak: Daya tarik meningkat
            </div>
        </div>

    </div>

</div>