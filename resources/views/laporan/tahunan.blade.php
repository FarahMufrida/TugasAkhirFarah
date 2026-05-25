<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>
        Laporan Tahunan
    </title>

    <style>

        body{
            font-family: sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1,h2,h3{
            margin-bottom: 5px;
        }

        .title{
            text-align: center;
            margin-bottom: 30px;
        }

        table{
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th{
            background: #f3f3f3;
        }

        table th,
        table td{
            border: 1px solid #ccc;
            padding: 8px;
        }

    </style>
</head>

<body>

    <div class="title">

        <h1>
            LAPORAN TAHUNAN ANALISIS SENTIMEN
        </h1>

        <h2>
            Wisata Kabupaten Jember
        </h2>

        <p>
            Tahun:
            <strong>
                {{ $tahun }}
            </strong>
        </p>

    </div>

    <table>

        <tr>
            <th>Total Ulasan</th>
            <td>{{ $totalUlasan ?? 0 }}</td>
        </tr>

        <tr>
            <th>Positif</th>
            <td>{{ $positif ?? 0 }}</td>
        </tr>

        <tr>
            <th>Negatif</th>
            <td>{{ $negatif ?? 0 }}</td>
        </tr>

        <tr>
            <th>Netral</th>
            <td>{{ $netral ?? 0 }}</td>
        </tr>

    </table>

    <br>

    <h3>Rekomendasi Tahunan</h3>

    @forelse(($rekomendasi ?? []) as $item)

        <p>
            • {{ $item->rekomendasi }}
        </p>

    @empty

        <p>
            Tidak ada rekomendasi.
        </p>

    @endforelse

</body>
</html>