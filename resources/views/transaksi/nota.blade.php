<!DOCTYPE html>
<html>

<head>
    <title>Nota Transaksi</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
            background-color: #f9f9f9;
        }

        .container {
            padding: 20px;
            max-width: 800px;
            margin: auto;
            background: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            padding: 10px;
            background-color: skyblue;
            color: white;
            border-bottom: 1px solid #ddd;
        }

        .footer {
            padding: 10px;
            background-color: #f4f4f4;
            border-top: 1px solid #ddd;
            position: relative;
            bottom: 0;
            width: 100%;
            text-align: center;
        }

        .content {
            margin-bottom: 20px;
        }

        .content p {
            margin: 5px 0;
        }

        .content p span {
            display: inline-block;
            min-width: 150px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f4f4f4;
        }

        .amount {
            text-align: right;
        }

        .description-title {
            margin: 20px 0 10px;
            font-size: 18px;
            border-bottom: 2px solid #333;
            display: inline-block;
        }

        .status {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 5px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Nota Transaksi</h1>
        </div>

        <div class="content">
            <table>
                <tr>
                    <th>Nama</th>
                    <td>{{ $transaksi->tagihan->penduduk->nama_penduduk }}</td>
                </tr>
                <tr>
                    <th>Bulan Tagihan</th>
                    <td>{{ $transaksi->tagihan->bulan_tagihan }}</td>
                </tr>
                <tr>
                    <th>Tahun Tagihan</th>
                    <td>{{ $transaksi->tagihan->tahun_tagihan }}</td>
                </tr>
                <tr>
                    <th>Tagihan Meteran</th>
                    <td>{{ $transaksi->tagihan->tagihan_meteran }}</td>
                </tr>
                <tr>
                    <th>Kategori</th>
                    <td>{{ $transaksi->kategori->nama }}</td>
                </tr>
                <tr>
                    <th>Keterangan</th>
                    <td>{{ $transaksi->keterangan }}</td>
                </tr>
            </table>
        </div>

        <div class="content">
            <h3 class="description-title">Deskripsi</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal Pembayaran</th>
                        <th>Total Tagihan</th>
                        <th>Jumlah Pembayaran</th>
                        <th>Status Tagihan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ date('d M Y', strtotime($transaksi->tanggal_pembayaran)) }}</td>
                        <td class="amount">Rp {{ number_format($transaksi->tagihan->total_tagihan, 2, ',', '.') }}</td>
                        <td class="amount">Rp {{ number_format($transaksi->jumlah_pembayaran, 2, ',', '.') }}</td>
                        <td class="status">{{ $transaksi->tagihan->status_tagihan }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
