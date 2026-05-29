<?php
    $destinasiList    ??= collect([]);
    $totalDestinasiAnalisis ??= 0;
    $totalDestinasiBerkeluhan ??= 0;
    $totalNegatif     ??= 0;
    $totalUlasan      ??= 0;
    $persenNegatif    ??= 0;
    $tingkatKepuasan  ??= 0;
    $labelKepuasan    ??= 'Kurang';
    $isuDominan       ??= '-';
    $isuDominanPersen ??= 0;
    $isuUtama         ??= [];
    $kataDominan      ??= [];
    $saranPerbaikan   ??= [];
    $prioritas        ??= [];
?>

<div class="space-y-6">

    
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rekomendasi Layanan</h2>
            <p class="text-sm text-gray-500">
                Rekomendasi dibuat dari ulasan negatif agar saran perbaikan fokus pada keluhan pengunjung.
            </p>
            <p class="text-xs text-gray-400 mt-1">
                <?php echo e($totalDestinasiAnalisis); ?> destinasi dianalisis, <?php echo e($totalDestinasiBerkeluhan); ?> destinasi memiliki ulasan negatif.
            </p>
        </div>

        
        <form method="GET" action="<?php echo e(route('dashboard')); ?>" class="flex gap-3">
            <input type="hidden" name="tab" value="rekomendasi">
            <input type="hidden" name="periode_id" value="<?php echo e($periodeAktif->id ?? request('periode_id')); ?>">
            <select name="destinasi" onchange="this.form.submit()"
                class="border rounded-lg pl-3 pr-9 py-2 text-sm min-w-[220px] focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Semua Destinasi</option>
                <?php $__currentLoopData = $destinasiList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $destinasi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($destinasi); ?>" <?php echo e(request('destinasi') == $destinasi ? 'selected' : ''); ?>>
                        <?php echo e($destinasi); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>
    </div>

    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full text-xl">📍</div>
            <div>
                <p class="text-sm text-gray-500">Destinasi Dianalisis</p>
                <h3 class="text-xl font-bold"><?php echo e($totalDestinasiAnalisis); ?></h3>
                <p class="text-xs text-gray-500"><?php echo e($totalDestinasiBerkeluhan); ?> punya keluhan</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-red-100 text-red-500 p-3 rounded-full text-xl">😟</div>
            <div>
                <p class="text-sm text-gray-500">Total Ulasan Negatif</p>
                <h3 class="text-xl font-bold"><?php echo e($totalNegatif); ?></h3>
                <p class="text-xs text-red-500"><?php echo e($persenNegatif); ?>% dari total</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-green-100 text-green-600 p-3 rounded-full text-xl">😊</div>
            <div>
                <p class="text-sm text-gray-500">Tingkat Kepuasan</p>
                <h3 class="text-xl font-bold"><?php echo e($tingkatKepuasan); ?>%</h3>
                <span class="text-xs px-2 py-1 rounded
                    <?php echo e($labelKepuasan === 'Baik'   ? 'bg-green-100 text-green-700'  :
                      ($labelKepuasan === 'Sedang' ? 'bg-yellow-100 text-yellow-700' :
                                                     'bg-red-100 text-red-700')); ?>">
                    <?php echo e($labelKepuasan); ?>

                </span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-purple-100 text-purple-600 p-3 rounded-full text-xl">❗</div>
            <div>
                <p class="text-sm text-gray-500">Isu Dominan</p>
                <h3 class="text-xl font-bold"><?php echo e($isuDominan); ?></h3>
                <p class="text-xs text-gray-500"><?php echo e($isuDominanPersen); ?>% dari total isu</p>
            </div>
        </div>

    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Isu Utama (Top 5)</h3>

            <?php $__empty_1 = true; $__currentLoopData = $isuUtama; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $isu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $warnaClass = match($isu['color']) {
                        'red'    => 'bg-red-500',
                        'orange' => 'bg-orange-500',
                        'yellow' => 'bg-yellow-400',
                        'green'  => 'bg-green-500',
                        default  => 'bg-blue-500',
                    };
                ?>
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span><?php echo e($i + 1); ?>. <?php echo e($isu['nama']); ?></span>
                        <span class="font-semibold"><?php echo e($isu['persen']); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded">
                        <div class="h-2 rounded <?php echo e($warnaClass); ?>" style="width: <?php echo e($isu['persen']); ?>%"></div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-400 text-center py-4">Belum ada data isu.</p>
            <?php endif; ?>
        </div>

        
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Kata Kunci Dominan</h3>

            <?php $maxFreq = !empty($kataDominan) ? max($kataDominan) : 1; ?>

            <?php $__empty_1 = true; $__currentLoopData = array_slice($kataDominan, 0, 5, true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kata => $jumlah): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="mb-3">
                    <div class="flex justify-between text-sm">
                        <span><?php echo e($kata); ?></span>
                        <span class="font-semibold text-blue-600"><?php echo e($jumlah); ?>x</span>
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded mt-1">
                        <div class="bg-blue-500 h-2 rounded" style="width: <?php echo e(round(($jumlah / $maxFreq) * 100)); ?>%"></div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-400 text-center py-4">Belum ada kata kunci.</p>
            <?php endif; ?>
        </div>

        
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold mb-4 text-gray-700">Saran Perbaikan</h3>

            <div class="space-y-4 text-sm">
                <?php $__empty_1 = true; $__currentLoopData = $saranPerbaikan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $saran): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex gap-3">
                        <div class="text-xl"><?php echo e($saran['icon']); ?></div>
                        <div>
                            <p class="font-semibold"><?php echo e($saran['nama']); ?></p>
                            <p class="text-gray-600"><?php echo e($saran['tip']); ?></p>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-sm text-gray-400 text-center py-4">Belum ada saran.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <?php $__empty_1 = true; $__currentLoopData = $prioritas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $border = match($p['color']) {
                    'red'    => 'border-red-300',
                    'orange' => 'border-orange-300',
                    'yellow' => 'border-yellow-300',
                    'green'  => 'border-green-300',
                    default  => 'border-blue-300',
                };
                $title = match($p['color']) {
                    'red'    => 'text-red-600',
                    'orange' => 'text-orange-500',
                    'yellow' => 'text-yellow-600',
                    'green'  => 'text-green-600',
                    default  => 'text-blue-600',
                };
                $dampak = match($p['color']) {
                    'red'    => 'bg-red-100 text-red-600',
                    'orange' => 'bg-orange-100 text-orange-600',
                    'yellow' => 'bg-yellow-100 text-yellow-600',
                    'green'  => 'bg-green-100 text-green-700',
                    default  => 'bg-blue-100 text-blue-600',
                };
            ?>
            <div class="border <?php echo e($border); ?> rounded-xl p-5 bg-white">
                <h4 class="font-semibold <?php echo e($title); ?> mb-2">Prioritas <?php echo e($p['rank']); ?></h4>
                <h3 class="text-lg font-bold mb-3"><?php echo e($p['nama']); ?></h3>

                <ul class="text-sm space-y-2 mb-4">
                    <?php $__currentLoopData = $p['actions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-center gap-2">
                            <span class="text-blue-500">✔</span> <?php echo e($action); ?>

                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>

                <div class="text-xs <?php echo e($dampak); ?> p-2 rounded">
                    Dampak: <?php echo e($p['dampak']); ?>

                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-span-3 text-center text-gray-400 text-sm py-6">
                Belum ada data rekomendasi. Pastikan analisis sentimen sudah dijalankan.
            </div>
        <?php endif; ?>

    </div>

</div><?php /**PATH C:\laragon\www\SistemAnalisisSentimen\resources\views/rekomendasi/index.blade.php ENDPATH**/ ?>