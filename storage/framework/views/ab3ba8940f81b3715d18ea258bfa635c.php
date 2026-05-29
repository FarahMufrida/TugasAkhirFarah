<?php $__env->startSection('content'); ?>

<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                Riwayat Analisis
            </h2>

            <p class="text-sm text-gray-500">
                Periode tersimpan otomatis setiap kali Ambil Data dijalankan.
            </p>
        </div>

        

<form
    action="<?php echo e(route('laporan.tahunan')); ?>"
    method="GET"
    class="flex items-center gap-3">

    <div class="relative">

        <select
            name="tahun"
            required
            class="appearance-none
                   border-2 border-blue-500
                   rounded-2xl
                   pl-4 pr-9
                   py-2.5
                   text-sm font-medium
                   bg-white
                   shadow-sm
                   cursor-pointer
                   focus:outline-none
                   focus:ring-2
                   focus:ring-blue-300">

            <?php for($tahun = date('Y'); $tahun >= 2024; $tahun--): ?>
                <option value="<?php echo e($tahun); ?>"><?php echo e($tahun); ?></option>
            <?php endfor; ?>

        </select>

    </div>

    <button
        type="submit"
        class="bg-blue-600
               hover:bg-blue-700
               text-white
               px-5 py-2.5
               rounded-2xl
               text-sm font-medium
               shadow">
        Cetak Tahunan
    </button>

</form>

    </div>

    <?php if(session('success')): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded whitespace-pre-line">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow p-6">

        <h3 class="font-semibold text-gray-800 mb-1">
            Import Data Lama dari CSV
        </h3>

        <p class="text-sm text-gray-500 mb-4">
            Gunakan untuk memasukkan ulasan historis. Kolom wajib:
            wisata, rating, ulasan, tanggal.
            Kolom reviewer boleh ada.
        </p>

        <form
            action="<?php echo e(route('riwayat.import')); ?>"
            method="POST"
            enctype="multipart/form-data"
            class="grid grid-cols-1 md:grid-cols-[180px_1fr_auto] gap-3 md:items-center">

            <?php echo csrf_field(); ?>

            <input
                type="month"
                name="periode_bulan"
                value="<?php echo e(now()->format('Y-m')); ?>"
                class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-[150px]"
                required>

            <input
                type="file"
                name="file"
                accept=".csv,.txt"
                class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-0"
                required>

            <button
                class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm shadow">

                Import CSV

            </button>

        </form>

        <?php if($errors->any()): ?>
            <div class="mt-3 text-sm text-red-600">
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-left">

                    <tr>

                        <th class="px-4 py-3">
                            Periode
                        </th>

                        <th class="px-4 py-3 text-center">
                            Total Ulasan
                        </th>

                        <th class="px-4 py-3 text-center">
                            Hasil Analisis
                        </th>

                        <th class="px-4 py-3 text-center">
                            Wisata
                        </th>

                        <th class="px-4 py-3">
                            Terakhir Analisis
                        </th>

                        <th class="px-4 py-3 text-center">
                            Aksi
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y">

                    <?php $__empty_1 = true; $__currentLoopData = $riwayat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                        <tr class="hover:bg-gray-50">

                            <td class="px-4 py-3 font-semibold text-gray-800">
                                <?php echo e($item->nama); ?>

                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php echo e($item->total_ulasan); ?>

                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php echo e($item->total_hasil); ?>

                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php echo e($item->total_wisata); ?>

                            </td>

                            <td class="px-4 py-3 text-gray-500">

                                <?php echo e($item->terakhir_analisis
                                ? \Carbon\Carbon::parse(
                                $item->terakhir_analisis
                                )->format('d M Y H:i')
                                : '-'); ?>


                            </td>

                            <td class="px-4 py-3">

                                <div class="flex flex-wrap justify-center gap-2">

                                    <a
                                    href="<?php echo e(route('dashboard',
                                    ['periode_id'=>$item->id,
                                    'tab'=>'analisis'])); ?>"

                                    class="px-3 py-1 rounded-lg
                                    bg-green-50 text-green-600
                                    hover:bg-green-100">

                                        Analisis

                                    </a>

                                    <a
                                    href="<?php echo e(route('ulasan.index',
                                    ['periode_id'=>$item->id])); ?>"

                                    class="px-3 py-1 rounded-lg
                                    bg-gray-100 text-gray-700
                                    hover:bg-gray-200">

                                        Ulasan

                                    </a>

                                    
                                    <a
                                    href="<?php echo e(route('laporan.bulanan', $item->id)); ?>"
                                    class="px-3 py-1 rounded-lg
                                    bg-blue-50 text-blue-600
                                    hover:bg-blue-100">
                                        Cetak Laporan
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <tr>

                            <td
                            colspan="6"
                            class="px-4 py-8 text-center text-gray-400">

                                Belum ada riwayat analisis.
                                Jalankan Ambil Data terlebih dahulu.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        

    <?php if($riwayat->hasPages()): ?>
        <?php
            $current = $riwayat->currentPage();
            $last    = $riwayat->lastPage();
            $query   = http_build_query(request()->except('page'));
            $window  = collect(range(max(1, $current - 2), min($last, $current + 2)));
        ?>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600">

            
            <p>Menampilkan <?php echo e($riwayat->firstItem()); ?> – <?php echo e($riwayat->lastItem()); ?> dari <?php echo e($riwayat->total()); ?> data</p>

            
            <div class="flex items-center gap-1">

                
                <?php if($riwayat->onFirstPage()): ?>
                    <span class="px-3 py-1.5 rounded-lg border bg-gray-100 text-gray-400 cursor-not-allowed">&laquo;</span>
                <?php else: ?>
                    <a href="<?php echo e($riwayat->previousPageUrl()); ?>&<?php echo e($query); ?>" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition">&laquo;</a>
                <?php endif; ?>

                
                <?php if(!$window->contains(1)): ?>
                    <a href="<?php echo e($riwayat->url(1)); ?>&<?php echo e($query); ?>" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition">1</a>
                    <?php if($window->min() > 2): ?>
                        <span class="px-2 py-1.5 text-gray-400">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                
                <?php $__currentLoopData = $window; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($page == $current): ?>
                        <span class="px-3 py-1.5 rounded-lg border bg-blue-600 text-white font-semibold"><?php echo e($page); ?></span>
                    <?php else: ?>
                        <a href="<?php echo e($riwayat->url($page)); ?>&<?php echo e($query); ?>" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition"><?php echo e($page); ?></a>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                
                <?php if(!$window->contains($last)): ?>
                    <?php if($window->max() < $last - 1): ?>
                        <span class="px-2 py-1.5 text-gray-400">...</span>
                    <?php endif; ?>
                    <a href="<?php echo e($riwayat->url($last)); ?>&<?php echo e($query); ?>" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition"><?php echo e($last); ?></a>
                <?php endif; ?>

                
                <?php if($riwayat->hasMorePages()): ?>
                    <a href="<?php echo e($riwayat->nextPageUrl()); ?>&<?php echo e($query); ?>" class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-blue-600 transition">&raquo;</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 rounded-lg border bg-gray-100 text-gray-400 cursor-not-allowed">&raquo;</span>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

    </div>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.sentara', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\SistemAnalisisSentimen\resources\views/ulasan/riwayat.blade.php ENDPATH**/ ?>