<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Kategori {{ $category->nama }}</title>
    <style>
        @page {
            margin: 2cm 1.5cm 2.5cm 1.5cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #212529;
        }

        h2 {
            margin: 0 0 12px 0;
            font-size: 18px;
        }

        .info td {
            padding: 2px 8px 2px 0;
        }

        .info th {
            text-align: left;
            padding: 2px 8px 2px 0;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        table.items th,
        table.items td {
            border: 1px solid #adb5bd;
            padding: 5px 6px;
        }

        table.items th {
            background: #e9ecef;
            text-align: left;
        }

        table.items tr:nth-child(even) td {
            background: #f8f9fa;
        }

        .angka {
            text-align: right;
        }

        .tengah {
            text-align: center;
        }

        footer {
            position: fixed;
            bottom: -1.5cm;
            left: 0;
            right: 0;
            height: 1cm;
            border-top: 1px solid #adb5bd;
            padding-top: 4px;
            font-size: 9px;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <footer>
        Dicetak pada {{ $dicetak_pada->format('d-m-Y H:i:s') }} WIB
    </footer>

    <h2>Data Kategori Barang</h2>
    <table class="info">
        <tr>
            <th>Nama Kategori</th>
            <td>:</td>
            <td>{{ $category->nama }}</td>
        </tr>
        <tr>
            <th>Kode Kategori</th>
            <td>:</td>
            <td>{{ $category->kode }}</td>
        </tr>
        <tr>
            <th>Jumlah Item</th>
            <td>:</td>
            <td>{{ $total }}@if($total > $batas) (ditampilkan {{ $batas }} pertama)@endif</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="tengah">No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Jenis</th>
                <th>Supplier</th>
                <th class="angka">Harga Beli</th>
                <th class="angka">Laba (%)</th>
                <th class="angka">Harga Jual</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            @php $hargaJual = $item->harga_jual; @endphp
            <tr>
                <td class="tengah">{{ $loop->iteration }}</td>
                <td>{{ $item->kode }}</td>
                <td>{{ $item->nama }}</td>
                <td>{{ $item->jenis }}</td>
                <td>{{ $item->supplier }}</td>
                <td class="angka">{{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                <td class="angka">{{ $item->laba }}</td>
                <td class="angka">{{ number_format($hargaJual, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="tengah">Belum ada item untuk kategori ini</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
