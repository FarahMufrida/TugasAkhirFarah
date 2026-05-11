@extends('layouts.sentara')

@section('content')

<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Analisis</h2>
            <p class="text-sm text-gray-500">
                Periode tersimpan otomatis setiap kali Ambil Data dijalankan.
            </p>
        </div>

        <a href="{{ route('ulasan.index') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm shadow text-center">
            Ambil Data Baru
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded whitespace-pre-line">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold text-gray-800 mb-1">Import Data Lama dari CSV</h3>
        <p class="text-sm text-gray-500 mb-4">
            Gunakan untuk memasukkan ulasan historis. Kolom wajib: wisata, rating, ulasan, tanggal. Kolom reviewer boleh ada.
        </p>

        <form action="{{ route('riwayat.import') }}" method="POST" enctype="multipart/form-data"
            class="grid grid-cols-1 md:grid-cols-[180px_1fr_auto] gap-3 md:items-center">
            @csrf
            <input type="month" name="periode_bulan" value="{{ now()->format('Y-m') }}"
                class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-[150px]" required>

            <input type="file" name="file" accept=".csv,.txt"
                class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-0" required>

            <button class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm shadow">
                Import CSV
            </button>
        </form>

        @if($errors->any())
            <div class="mt-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-center">Total Ulasan</th>
                        <th class="px-4 py-3 text-center">Hasil Analisis</th>
                        <th class="px-4 py-3 text-center">Wisata</th>
                        <th class="px-4 py-3">Terakhir Analisis</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($riwayat as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $item->nama }}</td>
                            <td class="px-4 py-3 text-center">{{ $item->total_ulasan }}</td>
                            <td class="px-4 py-3 text-center">{{ $item->total_hasil }}</td>
                            <td class="px-4 py-3 text-center">{{ $item->total_wisata }}</td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $item->terakhir_analisis ? \Carbon\Carbon::parse($item->terakhir_analisis)->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <a href="{{ route('dashboard', ['periode_id' => $item->id]) }}"
                                        class="px-3 py-1 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100">
                                        Dashboard
                                    </a>
                                    <a href="{{ route('dashboard', ['periode_id' => $item->id, 'tab' => 'analisis']) }}"
                                        class="px-3 py-1 rounded-lg bg-green-50 text-green-600 hover:bg-green-100">
                                        Analisis
                                    </a>
                                    <a href="{{ route('ulasan.index', ['periode_id' => $item->id]) }}"
                                        class="px-3 py-1 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
                                        Ulasan
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                Belum ada riwayat analisis. Jalankan Ambil Data terlebih dahulu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($riwayat->hasPages())
            <div class="p-4">
                {{ $riwayat->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
