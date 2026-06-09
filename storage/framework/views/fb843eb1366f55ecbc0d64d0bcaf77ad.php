<?php if($standalone ?? false): ?>
    
    <?php $__env->startSection('content'); ?>
<?php endif; ?>

<?php
    $totalHasilAnalisis ??= $hasil->total() ?? 0;
    $jumlahKelasAnalisis ??= 0;
    $evaluasiTersedia = $evaluasi && $jumlahKelasAnalisis >= 2;
?>

<div class="space-y-4">

    
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Hasil Analisis Sentimen</h2>
            <p class="text-gray-500 text-sm">
                Evaluasi memakai pseudo-label dari rating/rule otomatis, bukan anotasi manual.
            </p>
            <?php if($totalHasilAnalisis > 0 && !$evaluasiTersedia): ?>
                <p class="text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2 mt-3">
                    Semua hasil hanya memiliki <?php echo e($jumlahKelasAnalisis); ?> kelas sentimen. Precision, recall, F1, dan akurasi tidak dihitung karena evaluasi klasifikasi membutuhkan minimal 2 kelas. Detail hasil analisis tetap ditampilkan di bawah.
                </p>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Precision</p>
            <h3 class="text-2xl font-bold text-blue-600">
                <?php echo e($evaluasiTersedia ? number_format($evaluasi->precision ?? 0, 2) : '-'); ?>

            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Recall</p>
            <h3 class="text-2xl font-bold text-green-600">
                <?php echo e($evaluasiTersedia ? number_format($evaluasi->recall ?? 0, 2) : '-'); ?>

            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">F1 Score</p>
            <h3 class="text-2xl font-bold text-purple-600">
                <?php echo e($evaluasiTersedia ? number_format($evaluasi->f1_score ?? 0, 2) : '-'); ?>

            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-gray-500 text-sm">Akurasi</p>
            <h3 class="text-2xl font-bold text-indigo-600">
                <?php echo e($evaluasiTersedia ? number_format($evaluasi->accuracy ?? 0, 2) : '-'); ?>

            </h3>
        </div>

    </div>



    
<div class="flex items-end gap-3 mb-4">

    <form id="filter-form"
        method="GET"
        action="<?php echo e(route('dashboard')); ?>"
        class="flex items-end gap-2">

        <input type="hidden" name="tab" value="analisis">
        <input type="hidden" name="periode_id"
            value="<?php echo e($periodeAktif->id ?? ''); ?>">

        <select
            name="wisata"
            class="border rounded px-3 py-2 h-[46px]">

            <option value="">Semua Destinasi</option>

            <?php $__currentLoopData = $wisataList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($item); ?>"
                    <?php echo e(request('wisata') == $item ? 'selected' : ''); ?>>
                    <?php echo e($item); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        </select>

        <button
            type="submit"
            class="bg-blue-600 text-white px-4 rounded h-[46px]">
            Filter
        </button>

    </form>

    

</div>

    
    <div class="bg-white rounded-xl shadow p-4 h-fit">

        <h3 class="font-semibold mb-4">Detail Hasil Analisis Sentimen</h3>

        <table class="w-full text-sm">
            <thead class="border-b text-left">
                <tr>
                    <th>No</th>
                    <th>Wisata</th>
                    <th>Ulasan Asli</th>
                    <th>Ulasan Bersih</th>
                    <th>Sentimen</th>
                    <th>Probabilitas</th>
                </tr>
            </thead>

            <tbody>
                 <?php $__empty_1 = true; $__currentLoopData = $hasil; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-b">
                        <td><?php echo e(($hasil->currentPage() - 1) * $hasil->perPage() + $loop->iteration); ?></td>
                        <td><?php echo e($item->wisata); ?></td>
                        <td class="text-gray-500"><?php echo e(Str::limit($item->ulasan_asli ?? '-', 80)); ?></td>
                        <td><?php echo e(Str::limit($item->hasil_preprocessing ?? '-', 80)); ?></td>
                        <td>
                            <span class="px-2 py-1 rounded text-xs
                                <?php echo e(strtolower($item->sentimen) == 'positif' ? 'bg-green-100 text-green-600' : ''); ?>

                                <?php echo e(strtolower($item->sentimen) == 'negatif' ? 'bg-red-100 text-red-600' : ''); ?>

                                <?php echo e(strtolower($item->sentimen) == 'netral'  ? 'bg-yellow-100 text-yellow-600' : ''); ?>

                            ">
                                <?php echo e(ucfirst($item->sentimen)); ?>

                            </span>
                        </td>
                        <td><?php echo e(number_format($item->probabilitas ?? 0, 2)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-gray-500">Tidak ada data</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        
        <?php if($hasil->hasPages()): ?>
            <?php
                $current = $hasil->currentPage();
                $last    = $hasil->lastPage();
                $query   = http_build_query(request()->except('page'));
                // Window 2 halaman kiri & kanan dari current
                $window  = collect(range(max(1, $current - 2), min($last, $current + 2)));
            ?>

            <div class="mt-4 flex items-center justify-between text-sm text-gray-600">

                
                <p>Menampilkan <?php echo e($hasil->firstItem()); ?> - <?php echo e($hasil->lastItem()); ?> dari <?php echo e($hasil->total()); ?> data</p>

                
                <div class="flex items-center gap-1">

                    
                    <?php if($hasil->onFirstPage()): ?>
                        <span class="px-3 py-1 rounded border bg-gray-100 text-gray-400 cursor-not-allowed">&laquo;</span>
                    <?php else: ?>
                        <a href="<?php echo e($hasil->previousPageUrl()); ?>&<?php echo e($query); ?>" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600">&laquo;</a>
                    <?php endif; ?>

                    
                    <?php if(!$window->contains(1)): ?>
                        <a href="<?php echo e($hasil->url(1)); ?>&<?php echo e($query); ?>" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600">1</a>
                        <?php if($window->min() > 2): ?>
                            <span class="px-2 py-1 text-gray-400">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php $__currentLoopData = $window; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($page == $current): ?>
                            <span class="px-3 py-1 rounded border bg-blue-600 text-white font-semibold"><?php echo e($page); ?></span>
                        <?php else: ?>
                            <a href="<?php echo e($hasil->url($page)); ?>&<?php echo e($query); ?>" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600"><?php echo e($page); ?></a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    
                    <?php if(!$window->contains($last)): ?>
                        <?php if($window->max() < $last - 1): ?>
                            <span class="px-2 py-1 text-gray-400">...</span>
                        <?php endif; ?>
                        <a href="<?php echo e($hasil->url($last)); ?>&<?php echo e($query); ?>" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600"><?php echo e($last); ?></a>
                    <?php endif; ?>

                    
                    <?php if($hasil->hasMorePages()): ?>
                        <a href="<?php echo e($hasil->nextPageUrl()); ?>&<?php echo e($query); ?>" class="px-3 py-1 rounded border hover:bg-blue-50 text-blue-600">&raquo;</a>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded border bg-gray-100 text-gray-400 cursor-not-allowed">&raquo;</span>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php if($standalone ?? false): ?>
    <?php $__env->stopSection(); ?>
<?php endif; ?>

<?php echo $__env->make('layouts.sentara', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\SistemAnalisisSentimen\resources\views/analisis/index.blade.php ENDPATH**/ ?>