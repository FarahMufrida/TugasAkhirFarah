<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo e($judulLaporan); ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
* { box-sizing: border-box; }

body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1f2937;
    margin: 0;
    padding: 24px;
}

/* ── Sticky action bar pojok kanan atas ── */
.action-bar {
    position: fixed;
    top: 16px;
    right: 24px;
    display: flex;
    gap: 8px;
    z-index: 999;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: opacity .2s;
}
.btn:hover { opacity: .85; }
.btn-primary { background: #1d4ed8; color: white; }
.btn-secondary { background: white; color: #374151; border: 1px solid #d1d5db; }

.container {
    max-width: 960px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* LOGO */
.logo { font-size: 26px; font-weight: 800; color: #1e3a8a; margin-bottom: 20px; }

/* HERO */
.hero {
    border: 1px solid #dbeafe;
    background: linear-gradient(135deg, #eff6ff, #f7f8ff);
    border-radius: 12px;
    padding: 22px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.hero-title { font-size: 22px; font-weight: 800; color: #132b70; line-height: 1.3; }
.hero-sub { margin-top: 6px; color: #374151; font-size: 13px; }

.hero-right {
    min-width: 160px;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    padding: 14px 16px;
    background: white;
    font-size: 13px;
    line-height: 1.8;
}

/* SECTION TITLE */
.section-title {
    font-weight: 700;
    font-size: 13px;
    color: #1e3a8a;
    letter-spacing: .5px;
    margin: 24px 0 14px;
    text-transform: uppercase;
}

/* CARDS */
.cards { display: flex; gap: 12px; margin-bottom: 24px; }

.card {
    flex: 1;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
}

.card .card-label { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
.card .card-value { font-size: 26px; font-weight: 800; margin: 0; }
.card .card-unit  { font-size: 12px; color: #9ca3af; margin-top: 4px; }

.blue   { background: #eff6ff; border-color: #bfdbfe; }
.green  { background: #f0fdf4; border-color: #bbf7d0; }
.yellow { background: #fffbeb; border-color: #fde68a; }
.purple { background: #faf5ff; border-color: #e9d5ff; }

/* CHARTS */
.chart-row { display: flex; gap: 16px; margin-bottom: 24px; align-items: flex-start; }

.box-pie {
    width: 260px;
    flex-shrink: 0;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.box-bar {
    flex: 1;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    min-width: 0;
}

.box-pie h4, .box-bar h4 {
    margin: 0 0 12px;
    font-size: 12px;
    color: #1e3a8a;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    width: 100%;
}

/* TABLE */
table { width: 100%; border-collapse: collapse; margin-top: 8px; }
table th { background: #f8fafc; font-size: 12px; font-weight: 600; }
table th, table td { border: 1px solid #e5e7eb; padding: 9px 11px; font-size: 12px; }

.badge {
    background: #dcfce7;
    color: #15803d;
    border-radius: 20px;
    padding: 3px 10px;
    font-weight: 600;
    font-size: 11px;
}

/* KESIMPULAN */
.kesimpulan {
    border: 1px solid #dbeafe;
    background: #eff6ff;
    border-radius: 10px;
    padding: 16px 18px;
    margin-top: 20px;
    font-size: 13px;
}

/* FOOTER */
.footer {
    margin-top: 24px;
    padding-top: 14px;
    border-top: 1px solid #e5e7eb;
    font-size: 12px;
    display: flex;
    justify-content: space-between;
    color: #6b7280;
}

/* Sembunyikan action bar saat print */
@media print {
    body { background: white; padding: 0; }
    .action-bar { display: none !important; }
}
</style>
</head>
<body>


<div class="action-bar" id="actionBar">
    <button class="btn btn-secondary" onclick="cetakHalaman()">🖨️ Cetak</button>
</div>

<div class="container" id="isiLaporan">

    
    <div class="logo">SENTARA</div>

    
    <div class="hero">
        <div class="hero-left">
            <div class="hero-title">LAPORAN ANALISIS SENTIMEN<br>PER BULAN</div>
            <div class="hero-sub">SISTEM ANALISIS SENTIMEN WISATA JEMBER</div>
        </div>
        <div class="hero-right">
            <b>Periode</b><br>
            <?php echo e($periode->nama ?? '-'); ?><br><br>
            <b>Tanggal Cetak</b><br>
            <?php echo e(now()->format('d M Y H:i')); ?>

        </div>
    </div>

    
    <div class="section-title">Ringkasan</div>

    <div class="cards">
        <div class="card blue">
            <div class="card-label">Total Ulasan</div>
            <p class="card-value"><?php echo e($totalUlasanKotor); ?></p>
            <div class="card-unit">Ulasan Masuk</div>
        </div>
        <div class="card green">
            <div class="card-label">Hasil Analisis</div>
            <p class="card-value"><?php echo e($totalUlasan); ?></p>
            <div class="card-unit">Data Bersih</div>
        </div>
        <div class="card yellow">
            <div class="card-label">Wisata Dibahas</div>
            <p class="card-value"><?php echo e(count($perWisata)); ?></p>
            <div class="card-unit">Destinasi</div>
        </div>
        <div class="card purple">
            <div class="card-label">Akurasi Analisis</div>
            <p class="card-value"><?php echo e(number_format(($evaluasi->accuracy ?? 0) * 100, 2)); ?>%</p>
            <div class="card-unit">Model</div>
        </div>
    </div>

    
    <div class="section-title">Visualisasi</div>

    <div class="chart-row">
        <div class="box-pie">
            <h4>Distribusi Sentimen</h4>
            <canvas id="pieChart" width="220" height="220"></canvas>
        </div>
        <div class="box-bar">
            <h4>Sentimen per Destinasi</h4>
            <canvas id="barChart"></canvas>
        </div>
    </div>

    
    <div class="section-title">Top Wisata Berdasarkan Sentimen Positif</div>

    <table>
        <tr>
            <th>No</th>
            <th>Wisata</th>
            <th>Total</th>
            <th>Positif</th>
            <th>Netral</th>
            <th>Negatif</th>
            <th>% Positif</th>
        </tr>
        <?php $__currentLoopData = $perWisata; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($i + 1); ?></td>
            <td><?php echo e($w->wisata); ?></td>
            <td><?php echo e($w->total); ?></td>
            <td><?php echo e($w->positif); ?></td>
            <td><?php echo e($w->netral); ?></td>
            <td><?php echo e($w->negatif); ?></td>
            <td><span class="badge"><?php echo e($w->total > 0 ? round(($w->positif / $w->total) * 100, 2) : 0); ?>%</span></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </table>

    
    <div class="kesimpulan">
        <b>KESIMPULAN</b>
        <p style="margin:8px 0 0">
            Pada periode <b><?php echo e($periode->nama ?? ''); ?></b>,
            terdapat <b><?php echo e($totalUlasanKotor); ?></b> ulasan masuk dengan
            <b><?php echo e($totalUlasan); ?></b> data berhasil dianalisis.
            Sentimen positif mendominasi dengan persentase <b><?php echo e($persen['positif'] ?? 0); ?>%</b>,
            diikuti netral <b><?php echo e($persen['netral'] ?? 0); ?>%</b>
            dan negatif <b><?php echo e($persen['negatif'] ?? 0); ?>%</b>.
        </p>
    </div>

    
    <div class="footer">
        <div>SENTARA — Sistem Analisis Sentimen Wisata Jember</div>
        <div>Dicetak oleh: Admin &nbsp;|&nbsp; <?php echo e(now()->format('d M Y H:i')); ?></div>
    </div>

</div>

<script>
// ── PIE / DOUGHNUT CHART ──
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Positif', 'Netral', 'Negatif'],
        datasets: [{
            data: [<?php echo e($totalPositif); ?>, <?php echo e($totalNetral); ?>, <?php echo e($totalNegatif); ?>],
            backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: false,
        animation: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } }
        }
    }
});

// ── BAR CHART SENTIMEN PER DESTINASI ──
const wisataLabels = <?php echo json_encode($perWisata->pluck('wisata'), 15, 512) ?>;
const wisataPos    = <?php echo json_encode($perWisata->pluck('positif'), 15, 512) ?>;
const wisataNet    = <?php echo json_encode($perWisata->pluck('netral'), 15, 512) ?>;
const wisataNeg    = <?php echo json_encode($perWisata->pluck('negatif'), 15, 512) ?>;

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: wisataLabels,
        datasets: [
            { label: 'Positif', data: wisataPos, backgroundColor: '#22c55e' },
            { label: 'Netral',  data: wisataNet, backgroundColor: '#f59e0b' },
            { label: 'Negatif', data: wisataNeg, backgroundColor: '#ef4444' }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        animation: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { font: { size: 11 } } },
            x: { ticks: { font: { size: 10 } } }
        }
    }
});

// ── CETAK ──
function cetakHalaman() {
    window.print();
}

// ── SIMPAN PDF ──
function simpanPDF() {
    const el  = document.getElementById('isiLaporan');
    const bar = document.getElementById('actionBar');
    bar.style.display = 'none';

    html2pdf().set({
        margin:      10,
        filename:    'Laporan-<?php echo e(Str::slug($periode->nama ?? "bulanan")); ?>.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(el).save().then(() => {
        bar.style.display = 'flex';
    });
}
</script>

</body>
</html><?php /**PATH C:\laragon\www\SistemAnalisisSentimen\resources\views/laporan/bulanan.blade.php ENDPATH**/ ?>