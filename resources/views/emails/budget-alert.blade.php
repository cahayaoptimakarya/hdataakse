@php use App\Support\Fmt; @endphp
<!doctype html>
<html lang="id">
<body style="margin:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
<div style="max-width:640px;margin:0 auto;padding:24px 16px">
    <div style="background:#fff;border:1px solid #e5e9f0;border-radius:12px;overflow:hidden">
        <div style="padding:18px 22px;border-bottom:1px solid #e5e9f0">
            <div style="font-size:12px;color:#64748b">Kontrol Biaya · {{ $periodeLabel }}</div>
            <div style="font-size:18px;font-weight:bold;margin-top:4px">Ada {{ $items->count() }} pos yang perlu diperhatikan</div>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <tr style="background:#f8fafc;color:#475569;text-align:left">
                <th style="padding:10px 22px">Pos</th>
                <th style="padding:10px;text-align:right">Realisasi</th>
                <th style="padding:10px;text-align:right">Budget</th>
                <th style="padding:10px 22px;text-align:right">Serapan</th>
            </tr>
            @foreach($items as $p)
                <tr style="border-top:1px solid #eef2f6">
                    <td style="padding:10px 22px"><b>{{ $p->akun }}</b><br><span style="color:#64748b">{{ $p->division }}</span></td>
                    <td style="padding:10px;text-align:right;white-space:nowrap">{{ Fmt::rp($p->realisasi) }}</td>
                    <td style="padding:10px;text-align:right;white-space:nowrap">{{ Fmt::rp($p->budget) }}</td>
                    <td style="padding:10px 22px;text-align:right;white-space:nowrap;font-weight:bold;color:{{ $p->level === 100 ? '#dc2626' : '#d97706' }}">
                        {{ number_format($p->pct, 1, ',', '.') }}%
                    </td>
                </tr>
            @endforeach
        </table>
        <div style="padding:18px 22px;border-top:1px solid #e5e9f0">
            <a href="{{ $laporanUrl }}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:bold">Buka laporan</a>
            <div style="font-size:12px;color:#64748b;margin-top:12px">Merah: sudah melebihi budget. Kuning: sudah di atas 80%. Setiap pos hanya diingatkan sekali per level dalam setahun.</div>
        </div>
    </div>
</div>
</body>
</html>
