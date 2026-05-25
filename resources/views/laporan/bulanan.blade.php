<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

.header-wrap { position: relative; background: #1a3a5c; color: white; margin-bottom: 0; overflow: hidden; }
.header-bg { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, #0f2744 0%, #1a4a7a 50%, #0d3060 100%); }
.header-content { position: relative; padding: 0; }
.header-top { display: flex; align-items: center; padding: 12px 20px 8px 20px; border-bottom: 1px solid rgba(255,255,255,0.15); }
.logo-circle { width: 48px; height: 48px; background: #c8a84b; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0; font-size: 14px; font-weight: bold; color: #1a3a5c; }
.logo-img { width: 48px; height: 48px; border-radius: 50%; object-fit: contain; margin-right: 12px; background: white; padding: 3px; }
.org1 { font-size: 11px; font-weight: bold; color: #c8a84b; letter-spacing: .5px; }
.org2 { font-size: 10px; color: rgba(255,255,255,.85); }
.header-body { padding: 14px 20px 16px 20px; }
.header-title { font-size: 24px; font-weight: bold; color: white; letter-spacing: 1px; text-transform: uppercase; line-height: 1.1; }
.header-sub { font-size: 14px; color: #c8a84b; font-weight: bold; letter-spacing: .5px; margin-bottom: 8px; text-transform: uppercase; }
.header-desc { font-size: 9.5px; color: rgba(255,255,255,.75); margin-bottom: 12px; line-height: 1.5; }
.periode-badge { display: inline-block; background: #1d4ed8; border-radius: 6px; padding: 7px 14px; }
.pb-label { font-size: 9px; color: rgba(255,255,255,.7); }
.pb-value { font-size: 13px; font-weight: bold; color: white; }

.page-body { padding: 16px 20px; }
.section { margin-bottom: 14px; }
.section-title { display: flex; align-items: center; gap: 7px; font-size: 11px; font-weight: bold; color: #1d4ed8; letter-spacing: .5px; text-transform: uppercase; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid #dbeafe; }

.stats { display: flex; gap: 8px; }
.stat-card { flex: 1; background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; display: flex; align-items: center; gap: 8px; }
.stat-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.stat-icon.blue { background: #eff6ff; } .stat-icon.green { background: #f0fdf4; } .stat-icon.red { background: #fef2f2; } .stat-icon.yellow { background: #fefce8; }
.slabel { font-size: 8.5px; color: #6b7280; }
.sval { font-size: 18px; font-weight: bold; line-height: 1.1; }
.spct { font-size: 8.5px; }
.sval.blue { color: #1d4ed8; } .sval.green { color: #16a34a; } .sval.red { color: #dc2626; } .sval.yellow { color: #d97706; }
.spct.green { color: #16a34a; } .spct.red { color: #dc2626; } .spct.yellow { color: #d97706; }

.two-col { display: flex; gap: 12px; }
.col-left { flex: 1; } .col-right { flex: 1; }

.donut-wrap { display: flex; align-items: center; gap: 16px; padding: 8px 0; }
.donut-chart { width: 100px; height: 100px; flex-shrink: 0; }
.donut-svg { width: 100px; height: 100px; transform: rotate(-90deg); }
.legend-item { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.legend-label { font-size: 9.5px; color: #374151; flex: 1; }
.legend-val { font-size: 9.5px; font-weight: bold; color: #111; }

table { width: 100%; border-collapse: collapse; }
.table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; }
thead tr { background: #1d4ed8; }
thead th { color: white; font-size: 9.5px; font-weight: bold; padding: 7px 10px; text-align: left; }
thead th.center { text-align: center; }
tbody tr:nth-child(even) { background: #f8fafc; }
tbody td { font-size: 9.5px; padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #374151; }
tbody td.center { text-align: center; }
tfoot td { background: #eff6ff; font-weight: bold; color: #1d4ed8; font-size: 9.5px; padding: 6px 10px; }

.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8.5px; font-weight: bold; }
.badge-positif { background: #dcfce7; color: #15803d; }
.badge-negatif { background: #fee2e2; color: #b91c1c; }
.badge-netral  { background: #fef9c3; color: #92400e; }

.rekom-item { display: flex; align-items: flex-start; gap: 10px; background: #f8fafc; border-radius: 8px; padding: 8px 10px; border: 1px solid #e5e7eb; margin-bottom: 6px; }
.rekom-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
.ri-red { background: #fee2e2; } .ri-orange { background: #ffedd5; } .ri-green { background: #dcfce7; } .ri-blue { background: #dbeafe; } .ri-purple { background: #ede9fe; }
.rtitle { font-weight: bold; font-size: 10px; color: #111827; margin-bottom: 2px; }
.rtip { font-size: 9px; color: #6b7280; line-height: 1.4; }

.eval-grid { display: flex; gap: 8px; }
.eval-box { flex: 1; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 9px 8px; text-align: center; }
.eval-label { font-size: 8.5px; color: #3b82f6; margin-bottom: 3px; }
.eval-val { font-size: 16px; font-weight: bold; color: #1d4ed8; }

.metodologi { background: #1a3a5c; color: white; padding: 14px 20px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-top: 16px; }
.metod-left h4 { font-size: 10px; font-weight: bold; color: #c8a84b; margin-bottom: 5px; }
.metod-left p { font-size: 8.5px; color: rgba(255,255,255,.75); line-height: 1.5; }
.metod-right { text-align: right; flex-shrink: 0; }
.mdate { font-size: 9px; color: rgba(255,255,255,.7); }
.msys { font-size: 9.5px; font-weight: bold; color: #c8a84b; margin-top: 3px; }

.page-break { page-break-before: always; }
.no-break { page-break-inside: avoid; }
</style>
</head>
<body>

{{-- HEADER --}}
<div class="header-wrap">
    <div class="header-bg"></div>
    <div class="header-content">
        <div class="header-top">
            @php $logoPath = public_path('images/logo-sentara.png'); @endphp
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" class="logo-img" alt="Logo">
            @else
                <div class="logo-circle">DISPAR</div>
            @endif
            <div>
                <div class="org1">DINAS PARIWISATA</div>
                <div class="org2">KABUPATEN JEMBER</div>
            </div>
        </div>
        <div class="header-body">
            <div class="header-title">LAPORAN ANALISIS SENTIMEN</div>
            <div class="header-sub">Wisata Kabupaten Jember</div>
            <div class="header-desc">Laporan ini berisi hasil analisis sentimen terhadap ulasan wisata dari Google Maps menggunakan metode Naive Bayes.</div>
            <div class="periode-badge">
                <div class="pb-label">Periode Analisis</div>
                <div class="pb-value">{{ $periode->nama }}</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">

{{-- RINGKASAN --}}
<div class="section no-break">
    <div class="section-title">📊 RINGKASAN SENTIMEN</div>
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon blue">💬</div>
            <div><div class="slabel">Total Ulasan</div><div class="sval blue">{{ $totalUlasan }}</div><div class="spct" style="color:#6b7280">100% dari keseluruhan</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">😊</div>
            <div><div class="slabel">Sentimen Positif</div><div class="sval green">{{ $totalPositif }}</div><div class="spct green">{{ $persen['positif'] }}%</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">😟</div>
            <div><div class="slabel">Sentimen Negatif</div><div class="sval red">{{ $totalNegatif }}</div><div class="spct red">{{ $persen['negatif'] }}%</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">😐</div>
            <div><div class="slabel">Sentimen Netral</div><div class="sval yellow">{{ $totalNetral }}</div><div class="spct yellow">{{ $persen['netral'] }}%</div></div>
        </div>
    </div>
</div>

{{-- DISTRIBUSI + EVALUASI --}}
<div class="two-col no-break">
    <div class="col-left">
        <div class="section-title">🍩 DISTRIBUSI SENTIMEN</div>
        @php
            $t   = max($totalUlasan, 1);
            $r   = 38; $circ = 2 * 3.14159 * $r;
            $pP  = $totalPositif / $t;
            $pN  = $totalNegatif / $t;
            $pNt = $totalNetral  / $t;
        @endphp
        <div class="donut-wrap">
            <div class="donut-chart">
                <svg class="donut-svg" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="#e5e7eb" stroke-width="18"/>
                    @if($pP > 0)<circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="#22c55e" stroke-width="18" stroke-dasharray="{{ $circ*$pP }} {{ $circ*(1-$pP) }}" stroke-dashoffset="0"/>@endif
                    @if($pN > 0)<circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="#ef4444" stroke-width="18" stroke-dasharray="{{ $circ*$pN }} {{ $circ*(1-$pN) }}" stroke-dashoffset="-{{ $circ*$pP }}"/>@endif
                    @if($pNt > 0)<circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="#f59e0b" stroke-width="18" stroke-dasharray="{{ $circ*$pNt }} {{ $circ*(1-$pNt) }}" stroke-dashoffset="-{{ $circ*($pP+$pN) }}"/>@endif
                    <circle cx="50" cy="50" r="22" fill="white"/>
                </svg>
            </div>
            <div>
                <div class="legend-item"><div class="legend-dot" style="background:#22c55e"></div><span class="legend-label">Positif</span><span class="legend-val">{{ $persen['positif'] }}% ({{ $totalPositif }})</span></div>
                <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div><span class="legend-label">Negatif</span><span class="legend-val">{{ $persen['negatif'] }}% ({{ $totalNegatif }})</span></div>
                <div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div><span class="legend-label">Netral</span><span class="legend-val">{{ $persen['netral'] }}% ({{ $totalNetral }})</span></div>
            </div>
        </div>
    </div>
    <div class="col-right">
        <div class="section-title">🎯 EVALUASI MODEL</div>
        @if($evaluasi)
        <div class="eval-grid">
            <div class="eval-box"><div class="eval-label">Precision</div><div class="eval-val">{{ number_format($evaluasi->precision,2) }}</div></div>
            <div class="eval-box"><div class="eval-label">Recall</div><div class="eval-val">{{ number_format($evaluasi->recall,2) }}</div></div>
            <div class="eval-box"><div class="eval-label">F1 Score</div><div class="eval-val">{{ number_format($evaluasi->f1_score,2) }}</div></div>
            <div class="eval-box"><div class="eval-label">Akurasi</div><div class="eval-val">{{ number_format($evaluasi->accuracy,2) }}</div></div>
        </div>
        @else
        <p style="font-size:10px;color:#9ca3af;padding:20px 0">Data evaluasi belum tersedia.</p>
        @endif
    </div>
</div>

{{-- TABEL PER DESTINASI --}}
<div class="two-col no-break" style="margin-top:14px">
    <div class="col-left">
        <div class="section-title">🔭 TOTAL DATA PER DESTINASI</div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No</th><th>Destinasi Wisata</th><th class="center">Total Ulasan</th></tr></thead>
                <tbody>
                    @foreach($perWisata as $i => $w)
                    <tr><td>{{ $i+1 }}</td><td style="font-weight:bold">{{ $w->wisata }}</td><td class="center">{{ $w->total }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td colspan="2" style="font-weight:bold;padding:6px 10px">Total</td><td class="center" style="padding:6px 10px;font-weight:bold">{{ $totalUlasan }}</td></tr></tfoot>
            </table>
        </div>
    </div>
    <div class="col-right">
        <div class="section-title">📉 SENTIMEN PER DESTINASI</div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No</th><th>Destinasi Wisata</th><th class="center" style="color:#86efac">Positif</th><th class="center" style="color:#fca5a5">Negatif</th><th class="center" style="color:#fde68a">Netral</th></tr></thead>
                <tbody>
                    @foreach($perWisata as $i => $w)
                    <tr>
                        <td>{{ $i+1 }}</td><td style="font-weight:bold">{{ $w->wisata }}</td>
                        <td class="center" style="color:#16a34a;font-weight:bold">{{ $w->positif }}</td>
                        <td class="center" style="color:#dc2626;font-weight:bold">{{ $w->negatif }}</td>
                        <td class="center" style="color:#d97706;font-weight:bold">{{ $w->netral }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot><tr>
                    <td colspan="2" style="padding:6px 10px;font-weight:bold">Total</td>
                    <td class="center" style="padding:6px 10px;color:#16a34a;font-weight:bold">{{ $totalPositif }}</td>
                    <td class="center" style="padding:6px 10px;color:#dc2626;font-weight:bold">{{ $totalNegatif }}</td>
                    <td class="center" style="padding:6px 10px;color:#d97706;font-weight:bold">{{ $totalNetral }}</td>
                </tr></tfoot>
            </table>
        </div>
    </div>
</div>

{{-- DETAIL + REKOMENDASI --}}
<div class="two-col no-break" style="margin-top:14px">
    <div class="col-left">
        <div class="section-title">📋 DETAIL HASIL ANALISIS</div>
        <div class="table-wrap">
            <table>
                <thead><tr><th width="6%">No</th><th width="22%">Destinasi</th><th width="44%">Ulasan</th><th width="16%">Sentimen</th><th width="12%" class="center">Prob.</th></tr></thead>
                <tbody>
                    @foreach($hasilAnalisis->take(8) as $i => $item)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td style="font-size:8.5px">{{ $item->wisata }}</td>
                        <td style="font-size:8.5px">{{ \Illuminate\Support\Str::limit($item->ulasan_asli ?? '-', 75) }}</td>
                        <td><span class="badge badge-{{ strtolower($item->sentimen) }}">{{ ucfirst($item->sentimen) }}</span></td>
                        <td class="center">{{ number_format($item->probabilitas,2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($hasilAnalisis->count() > 8)
        <p style="font-size:8.5px;color:#3b82f6;margin-top:5px;text-align:right">* Lihat semua data ulasan pada sistem ›</p>
        @endif
    </div>
    <div class="col-right">
        <div class="section-title">💡 REKOMENDASI LAYANAN</div>
        @php $iconColors = ['red','orange','green','blue','purple']; @endphp
        @forelse($rekomendasi as $i => $r)
        @php $c = $iconColors[$i % count($iconColors)]; @endphp
        <div class="rekom-item">
            <div class="rekom-icon ri-{{ $c }}">{{ $r['icon'] }}</div>
            <div><div class="rtitle">{{ $r['nama'] }}</div><div class="rtip">{{ $r['tip'] }}</div></div>
        </div>
        @empty
        <p style="font-size:10px;color:#9ca3af;padding:20px 0;text-align:center">Tidak ada keluhan signifikan. 🎉</p>
        @endforelse
    </div>
</div>

</div>

{{-- FOOTER --}}
<div class="metodologi">
    <div class="metod-left">
        <h4>METODOLOGI</h4>
        <p>Analisis sentimen dilakukan menggunakan metode Naive Bayes Classifier pada data ulasan Google Maps yang telah melalui proses preprocessing (teks cleaning, case folding, tokenizing, filtering, stemming).</p>
    </div>
    <div class="metod-right">
        <div class="mdate">Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }} WIB</div>
        <div class="msys">Sistem Analisis Sentimen Wisata</div>
        <div style="font-size:8.5px;color:rgba(255,255,255,.6)">Kabupaten Jember</div>
    </div>
</div>

{{-- HALAMAN 2: TABEL LENGKAP --}}
@if($hasilAnalisis->count() > 8)
<div class="page-break"></div>
<div class="page-body">
    <div style="margin-bottom:12px">
        <div style="font-size:14px;font-weight:bold;color:#1d4ed8">Detail Lengkap Hasil Analisis</div>
        <div style="font-size:9px;color:#6b7280">Periode: {{ $periode->nama }} — Total {{ $hasilAnalisis->count() }} data</div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th width="5%">No</th><th width="20%">Destinasi</th><th width="55%">Ulasan Asli</th><th width="12%">Sentimen</th><th width="8%" class="center">Prob.</th></tr></thead>
            <tbody>
                @foreach($hasilAnalisis as $i => $item)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td style="font-size:8.5px">{{ $item->wisata }}</td>
                    <td style="font-size:8.5px">{{ \Illuminate\Support\Str::limit($item->ulasan_asli ?? '-', 130) }}</td>
                    <td><span class="badge badge-{{ strtolower($item->sentimen) }}">{{ ucfirst($item->sentimen) }}</span></td>
                    <td class="center">{{ number_format($item->probabilitas,2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="metodologi" style="margin-top:16px">
        <div class="metod-left"><h4>METODOLOGI</h4><p>Analisis sentimen menggunakan Naive Bayes Classifier dengan preprocessing teks.</p></div>
        <div class="metod-right"><div class="mdate">{{ now()->translatedFormat('d F Y H:i') }} WIB</div><div class="msys">SENTARA — Sistem Analisis Wisata Jember</div></div>
    </div>
</div>
@endif

</body>
</html>