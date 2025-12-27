<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Detail Pengeluaran</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h2 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .info p {
            margin: 5px 0;
            font-size: 10px;
        }
        .info strong {
            font-weight: bold;
        }
        .summary {
            margin: 15px 0;
            padding: 10px;
            background-color: #fee2e2;
            border-radius: 5px;
        }
        .summary p {
            margin: 5px 0;
            font-size: 11px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #333;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        td {
            background-color: #fff;
        }
        tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        .amount {
            text-align: right;
        }
        .total {
            margin-top: 15px;
            padding: 10px;
            background-color: #dc2626;
            color: white;
            text-align: right;
            font-size: 12px;
            font-weight: bold;
            border-radius: 5px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>DETAIL PENGELUARAN</h2>
        <p>Bulan: {{ $month }}</p>
    </div>
    
    <div class="info">
        <p><strong>Nama:</strong> {{ $user->name ?? $user->phone_number }}</p>
        <p><strong>Phone:</strong> {{ $user->phone_number }}</p>
        <p><strong>Plan:</strong> {{ strtoupper($user->plan) }}</p>
        <p><strong>Periode:</strong> {{ $month }}</p>
    </div>
    
    <div class="summary">
        <p>Total Transaksi: {{ $expenses->count() }}</p>
        <p>Total Pengeluaran: Rp {{ number_format($total, 0, ',', '.') }}</p>
    </div>
    
    @if($expenses->count() > 0)
        <h3 style="margin-top: 15px; margin-bottom: 10px; font-size: 12px;">DETAIL TRANSAKSI</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">No</th>
                    <th style="width: 20%;">Tanggal</th>
                    <th style="width: 40%;">Deskripsi</th>
                    <th style="width: 15%;">Sumber</th>
                    <th style="width: 15%;" class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $index => $expense)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($expense->tanggal)->locale('id')->isoFormat('D MMM YYYY') }}</td>
                    <td>{{ $expense->description ?? '-' }}</td>
                    <td>{{ $expense->source ?? '-' }}</td>
                    <td class="amount">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 20px; color: #999;">
            <p>Tidak ada transaksi pengeluaran pada bulan ini.</p>
        </div>
    @endif
    
    <div class="total">
        <strong>Total Pengeluaran: Rp {{ number_format($total, 0, ',', '.') }}</strong>
    </div>
    
    <div class="footer">
        <p>Dibuat pada: {{ now()->locale('id')->isoFormat('D MMMM YYYY HH:mm:ss') }}</p>
        <p>Admin Dashboard - Detail Pengeluaran</p>
    </div>
</body>
</html>


