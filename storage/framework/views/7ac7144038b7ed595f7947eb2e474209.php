<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Laporan Tahunan</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, sans-serif;
    background: #f3f5fb;
    padding: 24px;
    color: #1f2937;
}

/* ACTION BAR */
.action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 960px;
    margin: 0 auto 16px;
}

.logo-text {
    font-size: 22px;
    font-weight: 900;
    color: #1e3a8a;
    letter-spacing: -1px;
}
.logo-text span { color: #ef4444; }

.btn-group { display: flex; gap: 8px; }

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
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}
.btn-blue { background: #1d4ed8; color: white; }
.btn-white { background: white; color: #374151; border: 1px solid #d1d5db; }
.btn:hover { opacity: .88; }

/* CONTAINER */
.container {
    max-width: 960px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
}

/* HERO */
.hero {
    border: 1px solid #dbeafe;
    background: linear-gradient(135deg, #eff6ff, #f7f8ff);
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.hero-left { display: flex; gap: 16px; align-items: center; }

.hero-icon {
    width: 52px;
    height: 52px;
    background: #1d4ed8;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.hero-icon svg { width: 28px; height: 28px; fill: white; }

.hero-title { font-size: 18px; font-weight: 900; color: #132b70; line-height: 1.3; }
.hero-sub { font-size: 11px; color: #6b7280; margin-top: 4px; }

.hero-right { font-size: 12px; text-align: right; line-height: 1.8; }
.hero-right .year { font-size: 22px; font-weight: 800; color: #1e3a8a; }

/* SECTION TITLE */
.section-title {
    font-size: 12px;
    font-weight: 700;
    color: #1e3a8a;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin: 20px 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-title::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 14px;
    background: #1d4ed8;
    border-radius: 2px;
}

/* CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.card-info .card-label { font-size: 11px; color: #6b7280; margin-bottom: 4px; }
.card-info .card-value { font-size: 28px; font-weight: 800; line-height: 1; }
.card-info .card-unit  { font-size: 11px; color: #9ca3af; margin-top: 4px; }
.card-icon {
    width: 36px; height: 36px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.blue   { background: #eff6ff; border-color: #bfdbfe; }
.green  { background: #f0fdf4; border-color: #bbf7d0; }
.yellow { background: #fffbeb; border-color: #fde68a; }
.purple { background: #faf5ff; border-color: #e9d5ff; }

.blue   .card-icon { background: #dbeafe; }
.green  .card-icon { background: #dcfce7; }
.yellow .card-icon { background: #fef3c7; }
.purple .card-icon { background: #ede9fe; }

/* REKAP + DONUT ROW */
.grid2 {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

/* TABLE */
.table-box {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}

table { width: 100%; border-collapse: collapse; }
table thead tr { background: #f8fafc; }
table th { padding: 10px 12px; font-size: 11px; font-weight: 700; color: #374151; text-align: left; }
table td { padding: 9px 12px; font-size: 11px; border-top: 1px solid #f1f5f9; }
table tbody tr:hover { background: #fafafa; }

.tfoot-row td { background: #f8fafc; font-weight: 700; border-top: 2px solid #e5e7eb; }

.pos { color: #16a34a; font-weight: 600; }
.net { color: #d97706; font-weight: 600; }
.neg { color: #dc2626; font-weight: 600; }

.badge-green {
    background: #dcfce7;
    color: #15803d;
    border-radius: 20px;
    padding: 2px 8px;
    font-weight: 700;
    font-size: 11px;
}

/* DONUT BOX */
.donut-box {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
}
.donut-box h4 {
    font-size: 11px;
    font-weight: 700;
    color: #1e3a8a;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 10px;
}
.donut-wrap {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.donut-center {
    position: absolute;
    text-align: center;
    pointer-events: none;
}
.donut-center .dc-num  { font-size: 20px; font-weight: 800; color: #1e3a8a; }
.donut-center .dc-label { font-size: 9px; color: #6b7280; }

/* TREND BOX */
.trend-box {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
}
.trend-box h4 {
    font-size: 11px;
    font-weight: 700;
    color: #1e3a8a;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 10px;
}

/* BOTTOM GRID */
.bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

/* KESIMPULAN */
.kesimpulan-box {
    border: 1px solid #dbeafe;
    background: #eff6ff;
    border-radius: 12px;
    padding: 16px 18px;
    font-size: 12px;
    line-height: 1.7;
}
.kesimpulan-box h4 {
    font-size: 12px;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 8px;
    text-transform: uppercase;
}

/* FOOTER */
.footer {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #9ca3af;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
}

@media print {
    body { background: white; padding: 0; }
    .action-bar { display: none !important; }
    .container { box-shadow: none; }
}
</style>
</head>
<body>


<div class="action-bar no-print" id="actionBar">
    <div></div>
    <div class="btn-group">
        <button class="btn btn-white" onclick="window.print()">🖨 Cetak</button>
    </div>
</div>

<div class="container" id="isiLaporan">

    
    <div class="hero">
        <div class="hero-left">
            <div class="hero-icon">
                <svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6M7 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2h-2M9 4a2 2 0 002 2h2a2 2 0 002-2M9 4a2 2 0 012-2h2a2 2 0 012 2" stroke="white" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
            </div>
            <div>
                
                <div style="display:inline-flex;align-items:center;gap:5px;background:#1e3a8a;border-radius:6px;padding:3px 10px;margin-bottom:7px;">
                    <span style="color:#fff;font-size:11px;font-weight:900;letter-spacing:1px;">-SENTARA-</span>
                    <span style="color:#93c5fd;font-size:9px;letter-spacing:2px;">JEMBER</span>
                </div>
                <div class="hero-title">LAPORAN ANALISIS SENTIMEN<br>PER TAHUN</div>
                <div class="hero-sub">SISTEM ANALISIS SENTIMEN WISATA JEMBER</div>
            </div>
        </div>
        <div class="hero-right">
            <div style="color:#6b7280;font-size:11px;">Tahun</div>
            <div class="year"><?php echo e($tahun); ?></div>
            <div style="color:#6b7280;font-size:11px;margin-top:6px;">Tanggal Cetak</div>
            <div style="font-weight:600;"><?php echo e(now()->format('d M Y H:i')); ?></div>
        </div>
    </div>

    
    <div class="section-title">Ringkasan Tahunan</div>

    <div class="cards">
        <div class="card blue">
            <div class="card-info">
                <div class="card-label">Total Ulasan</div>
                <div class="card-value"><?php echo e($totalUlasan); ?></div>
                <div class="card-unit">Ulasan</div>
            </div>
            <div class="card-icon">💬</div>
        </div>
        <div class="card green">
            <div class="card-info">
                <div class="card-label">Rata-rata Akurasi</div>
                <div class="card-value" style="font-size:20px;"><?php echo e($akurasi); ?>%</div>
                <div class="card-unit">Akurasi</div>
            </div>
            <div class="card-icon">✅</div>
        </div>
        <div class="card yellow">
            <div class="card-info">
                <div class="card-label">Total Wisata Dibahas</div>
                <div class="card-value"><?php echo e($totalWisata); ?></div>
                <div class="card-unit">Wisata</div>
            </div>
            <div class="card-icon">📍</div>
        </div>
        <div class="card purple">
            <div class="card-info">
                <div class="card-label">Bulan Dianalisis</div>
                <div class="card-value"><?php echo e(count($rekapBulanan)); ?></div>
                <div class="card-unit">Bulan</div>
            </div>
            <div class="card-icon">📅</div>
        </div>
    </div>

    
    <div class="section-title">Rekap Sentimen per Bulan</div>

    <div class="grid2">
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th>Total Ulasan</th>
                        <th>Positif</th>
                        <th>Netral</th>
                        <th>Negatif</th>
                        <th>Persentase Positif</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $totP=0; $totN=0; $totNt=0; $totAll=0; ?>
                    <?php $__currentLoopData = $rekapBulanan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $totP+=$r->positif; $totN+=$r->negatif; $totNt+=$r->netral; $totAll+=$r->total; ?>
                    <tr>
                        <td><?php echo e($r->bulan); ?></td>
                        <td><?php echo e($r->total); ?></td>
                        <td class="pos"><?php echo e($r->positif); ?></td>
                        <td class="net"><?php echo e($r->netral); ?></td>
                        <td class="neg"><?php echo e($r->negatif); ?></td>
                        <td><span class="badge-green"><?php echo e(number_format(($r->positif/max($r->total,1))*100,2)); ?>%</span></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot>
                    <tr class="tfoot-row">
                        <td>TOTAL / RATA-RATA</td>
                        <td><?php echo e($totAll); ?></td>
                        <td class="pos"><?php echo e($totP); ?></td>
                        <td class="net"><?php echo e($totNt); ?></td>
                        <td class="neg"><?php echo e($totN); ?></td>
                        <td><span class="badge-green"><?php echo e($totAll > 0 ? number_format(($totP/$totAll)*100,2) : 0); ?>%</span></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="donut-box">
            <h4>Distribusi Sentimen Tahun <?php echo e($tahun); ?></h4>
            <div class="donut-wrap" style="height:220px;">
                <canvas id="donut"></canvas>
            </div>
        </div>
    </div>

    
    <div class="section-title">Trend Sentimen per Bulan (<?php echo e($tahun); ?>)</div>

    <div class="trend-box">
        <canvas id="trend" height="90"></canvas>
    </div>

    
    <div class="section-title">Wisata Serpopuler & Kesimpulan</div>

    <div class="bottom-grid">
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Wisata</th>
                        <th>Total Ulasan</th>
                        <th>Persentase Positif</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $wisataPopuler; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($i+1); ?></td>
                        <td><?php echo e($w->wisata); ?></td>
                        <td><?php echo e($w->jumlah); ?></td>
                        <td><span class="badge-green"><?php echo e($w->persen); ?>%</span></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="kesimpulan-box">
            <h4>Kesimpulan Tahunan</h4>
            <p>
                Secara umum, sentimen positif mendominasi tahun <b><?php echo e($tahun); ?></b>
                dengan rata-rata <b><?php echo e($persenPositif); ?>%</b>.
                <?php if(isset($wisataPopuler[0])): ?>
                <?php echo e($wisataPopuler[0]->wisata); ?> menjadi destinasi paling populer
                dengan sentimen positif tertinggi.
                <?php endif; ?>
                Diperlukan peningkatan pada aspek fasilitas, kebersihan, dan pelayanan
                untuk menekan sentimen negatif.
            </p>
        </div>
    </div>

    
    <div class="footer">
        <div>SENTARA - Sistem Analisis Sentimen Wisata Jember</div>
        <div>Dicetak oleh: <?php echo e(auth()->user()->name); ?> (<?php echo e(ucfirst(auth()->user()->role ?? 'Admin')); ?>)</div>
    </div>

</div>

<script>
// ── DONUT ──
const donutCtx = document.getElementById('donut');
const totalUlasan = <?php echo e($totalUlasan > 0 ? $totalUlasan : 1); ?>;
const donutData   = [<?php echo e($positif); ?>, <?php echo e($netral); ?>, <?php echo e($negatif); ?>];

// Plugin: angka total di tengah (tidak numpuk)
const centerTextPlugin = {
    id: 'centerText',
    beforeDraw(chart) {
        const { ctx, chartArea: { left, top, right, bottom } } = chart;
        const cx = (left + right) / 2;
        const cy = (top + bottom) / 2;
        ctx.save();
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle    = '#1e3a8a';
        ctx.font         = 'bold 22px Arial';
        ctx.fillText(totalUlasan, cx, cy - 8);
        ctx.fillStyle = '#9ca3af';
        ctx.font      = '11px Arial';
        ctx.fillText('Total Ulasan', cx, cy + 12);
        ctx.restore();
    }
};

new Chart(donutCtx, {
    type: 'doughnut',
    data: {
        labels: ['Positif', 'Netral', 'Negatif'],
        datasets: [{
            data: donutData,
            backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        cutout: '62%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { size: 11 },
                    padding: 10,
                    generateLabels(chart) {
                        const d = chart.data;
                        return d.labels.map((lbl, i) => {
                            const val = d.datasets[0].data[i];
                            const pct = Math.round(val / totalUlasan * 1000) / 10;
                            return {
                                text: `${lbl}  ${val}  (${pct}%)`,
                                fillStyle: d.datasets[0].backgroundColor[i],
                                strokeStyle: '#fff',
                                lineWidth: 2,
                                index: i,
                            };
                        });
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label(ctx) {
                        const pct = Math.round(ctx.parsed / totalUlasan * 1000) / 10;
                        return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                    }
                }
            }
        }
    },
    plugins: [centerTextPlugin]
});

// ── TREND LINE ──
new Chart(document.getElementById('trend'), {
    type: 'line',
    data: {
        labels: [
            <?php $__currentLoopData = $rekapBulanan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            '<?php echo e($r->bulan); ?>',
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ],
        datasets: [
            {
                label: 'Positif',
                data: [<?php $__currentLoopData = $rekapBulanan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo e($r->positif); ?>,<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>],
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34,197,94,0.08)',
                tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#22c55e'
            },
            {
                label: 'Netral',
                data: [<?php $__currentLoopData = $rekapBulanan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo e($r->netral); ?>,<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>],
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,0.05)',
                tension: 0.4, fill: false, pointRadius: 4, pointBackgroundColor: '#f59e0b',
                borderDash: [4,3]
            },
            {
                label: 'Negatif',
                data: [<?php $__currentLoopData = $rekapBulanan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo e($r->negatif); ?>,<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>],
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.05)',
                tension: 0.4, fill: false, pointRadius: 4, pointBackgroundColor: '#ef4444'
            }
        ]
    },
    options: {
        responsive: true,
        animation: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' } },
            x: { ticks: { font: { size: 10 } }, grid: { display: false } }
        }
    }
});

// ── SIMPAN PDF ──
function simpanPDF() {
    const el  = document.getElementById('isiLaporan');
    const bar = document.getElementById('actionBar');
    bar.style.display = 'none';

    html2pdf().set({
        margin:      8,
        filename:    'Laporan-Tahunan-<?php echo e($tahun); ?>.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, windowWidth: 960 },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(el).save().then(() => {
        bar.style.display = 'flex';
    });
}
</script>

</body>
</html><?php /**PATH C:\laragon\www\SistemAnalisisSentimen\resources\views/laporan/tahunan.blade.php ENDPATH**/ ?>