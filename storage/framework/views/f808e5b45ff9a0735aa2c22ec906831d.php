<?php $__env->startSection('content'); ?>

<div class="max-w-7xl mx-auto px-4 md:px-0 space-y-6">

    <!-- HEADER -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Ulasan</h2>
            <p class="text-sm text-gray-500">
                Periode aktif: <?php echo e($periodeAktif->nama ?? 'Belum ada data'); ?>

                <a href="<?php echo e(route('riwayat.index')); ?>" class="text-blue-600 hover:underline ml-2">Lihat riwayat</a>
            </p>
        </div>
        <div class="flex flex-col md:flex-row gap-3 md:items-center">
            <form action="<?php echo e(route('ulasan.ambil')); ?>" method="POST" class="flex flex-col md:flex-row gap-3 md:items-center">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="redirect_to" value="ulasan">
                <input type="month" name="periode_bulan" value="<?php echo e($periodeAktif
                ? $periodeAktif->tahun . '-' . str_pad($periodeAktif->bulan, 2, '0', STR_PAD_LEFT)
                : now()->format('Y-m')); ?>"
                    class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-[150px]">
                <select name="wisata" class="border rounded-lg pl-3 pr-9 py-2 text-sm bg-white shadow-sm min-w-[220px]">
                    <option value="">Semua Lokasi</option>
                    <?php $__currentLoopData = ($scraperDestinations ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scraperDestination): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($scraperDestination); ?>"><?php echo e($scraperDestination); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button class="border border-blue-500 text-blue-600 px-5 py-2.5 rounded-full hover:bg-blue-50 transition">
                    Ambil Data
                </button>
            </form>
            <form action="<?php echo e(route('proses.analisis')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="periode_id" value="<?php echo e($periodeId); ?>">
                <button
                    <?php echo e($totalUlasan <= 0 ? 'disabled' : ''); ?>

                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Proses Analisis
                </button>
            </form>
        </div>
    </div>

</div>

    <!-- SUMMARY -->
    <div class="bg-white rounded-3xl shadow p-6 mb-6 mt-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 text-blue-600 p-3 rounded-full">📄</div>
                <div>
                    <p class="text-xs text-gray-500">Total Ulasan</p>
                    <p class="font-bold text-lg"><?php echo e($totalUlasan); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-green-100 text-green-600 p-3 rounded-full">📍</div>
                <div>
                    <p class="text-xs text-gray-500">Total Wisata</p>
                    <p class="font-bold text-lg"><?php echo e($totalWisata); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-orange-100 text-orange-600 p-3 rounded-full">📅</div>
                <div>
                    <p class="text-xs text-gray-500">Terakhir Update</p>
                    <p class="text-sm font-semibold">
                        <?php echo e($lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('d M Y H:i') : '-'); ?>

                    </p>
                </div>
            </div>
        </div> 
    </div>

    <!-- ALERT -->
    <?php if(session('success')): ?>
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded-lg text-sm">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm whitespace-pre-line">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <!-- SEARCH -->
    <form method="GET" action="<?php echo e(route('ulasan.index')); ?>" class="flex flex-col md:flex-row gap-3 items-center">
        <?php if(request('periode_id')): ?>
            <input type="hidden" name="periode_id" value="<?php echo e(request('periode_id')); ?>">
        <?php endif; ?>

        
        <input type="text" name="search" value="<?php echo e(request('search')); ?>"
            placeholder="Cari ulasan atau wisata..."
            class="flex-1 border px-3 py-2 rounded-lg text-sm">

        <button class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm">Cari</button>

        <button type="button"
            onclick="if (confirm('Reset akan mengosongkan semua ulasan dan hasil analisis untuk periode ini. Lanjutkan?')) document.getElementById('resetPeriodeForm').submit();"
            class="bg-gray-200 px-4 py-2 rounded-lg text-sm">
            Reset
        </button>

    </form>

    <?php if($periodeId): ?>
        <form id="resetPeriodeForm" action="<?php echo e(route('ulasan.kosongkan-periode')); ?>" method="POST" class="hidden">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <input type="hidden" name="periode_id" value="<?php echo e($periodeId); ?>">
        </form>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 w-[5%]">No</th>
                        <th class="px-4 py-3 w-[20%]">Nama Wisata</th>
                        <th class="px-4 py-3 w-[10%] text-center">Rating</th>
                        <th class="px-4 py-3 w-[45%]">Ulasan</th>
                        <th class="px-4 py-3 w-[20%] text-center">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php $__empty_1 = true; $__currentLoopData = $mentah; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?php echo e($mentah->firstItem() + $i); ?></td>
                        <td class="px-4 py-3 font-medium"><?php echo e($item->wisata); ?></td>
                        <td class="px-4 py-3 text-center text-yellow-500">
                            <?php echo e(str_repeat('★', round($item->rating))); ?>

                            <span class="text-gray-500 ml-1"><?php echo e($item->rating); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="line-clamp-2 text-gray-600"><?php echo e($item->ulasan); ?></div>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500">
                            <?php
                                try {
                                    $tanggalUlasan = $item->tanggal
                                        ? \Carbon\Carbon::parse($item->tanggal)->format('d M Y')
                                        : '-';
                                } catch (\Throwable $e) {
                                    $tanggalUlasan = $item->tanggal ?: '-';
                                }
                            ?>
                            <?php echo e($tanggalUlasan); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="text-center py-6 text-gray-400">Belum ada data</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-4 flex flex-col md:flex-row gap-3 justify-between items-center text-sm text-gray-500">
            <div>
                Menampilkan <?php echo e($mentah->firstItem() ?? 0); ?> - <?php echo e($mentah->lastItem() ?? 0); ?>

                dari <?php echo e($mentah->total()); ?> data
            </div>
            <div class="flex items-center gap-1">
                <?php if($mentah->onFirstPage()): ?>
                    <span class="px-3 py-1 bg-gray-200 rounded-lg">«</span>
                <?php else: ?>
                    <a href="<?php echo e($mentah->previousPageUrl()); ?>" class="px-3 py-1 bg-gray-100 rounded-lg">«</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $mentah->lastPage(); $i++): ?>
                    <?php if($i == $mentah->currentPage()): ?>
                        <span class="px-3 py-1 bg-blue-600 text-white rounded-lg"><?php echo e($i); ?></span>
                    <?php elseif($i <= 3 || $i > $mentah->lastPage() - 2 || abs($i - $mentah->currentPage()) <= 1): ?>
                        <a href="<?php echo e($mentah->url($i)); ?>" class="px-3 py-1 bg-gray-100 rounded-lg"><?php echo e($i); ?></a>
                    <?php elseif($i == 4 || $i == $mentah->lastPage() - 3): ?>
                        <span class="px-2">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if($mentah->hasMorePages()): ?>
                    <a href="<?php echo e($mentah->nextPageUrl()); ?>" class="px-3 py-1 bg-gray-100 rounded-lg">»</a>
                <?php else: ?>
                    <span class="px-3 py-1 bg-gray-200 rounded-lg">»</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.sentara', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\SistemAnalisisSentimen\resources\views/ulasan/index.blade.php ENDPATH**/ ?>