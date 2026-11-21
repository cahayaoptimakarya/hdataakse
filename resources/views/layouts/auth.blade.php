<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') · Kontrol Biaya</title>
    <link rel="shortcut icon" href="{{ asset('metronic/media/logos/favicon.png') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #0f172a; -webkit-font-smoothing: antialiased; background: #fff; }
        .wrap { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; }
        .l { background: linear-gradient(150deg, #0b1424 0%, #132a52 60%, #1d4ed8 130%); color: #e2e8f0; padding: 56px; display: flex; flex-direction: column; }
        .brand { display: flex; align-items: center; gap: 10px; color: #fff; font-weight: 700; font-size: 17px; }
        .brand i { width: 32px; height: 32px; border-radius: 9px; background: linear-gradient(135deg, #3b82f6, #06b6d4); display: grid; place-items: center; font-style: normal; font-size: 13px; }
        .l h1 { font-size: 34px; line-height: 1.2; color: #fff; margin-top: auto; max-width: 460px; }
        .l p { color: #94a3b8; margin-top: 14px; max-width: 440px; font-size: 14px; line-height: 1.6; }
        .l ul { list-style: none; margin: 28px 0 auto; display: grid; gap: 10px; color: #cbd5e1; font-size: 14px; }
        .l li::before { content: "✓"; color: #60a5fa; font-weight: 700; margin-right: 10px; }
        .l .foot { font-size: 12px; color: #64748b; margin-top: 32px; }
        .r { display: grid; place-items: center; padding: 32px 20px; }
        .f { width: 100%; max-width: 380px; }
        .f .logo { height: 44px; margin-bottom: 28px; }
        .f h2 { font-size: 24px; }
        .f .sub { color: #475569; margin: 6px 0 24px; font-size: 14px; line-height: 1.5; }
        .f label { font-weight: 600; font-size: 12.5px; display: flex; margin: 16px 0 6px; }
        .f label a { margin-left: auto; }
        .f input[type=email], .f input[type=password], .f input[type=text] { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 12px 14px; font-size: 14px; font-family: inherit; color: #0f172a; outline: none; }
        .f input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px #dbeafe; }
        .f input.bad { border-color: #dc2626; }
        .err { color: #dc2626; font-size: 12.5px; margin-top: 6px; }
        .ok { background: #dcfce7; color: #166534; border-radius: 10px; padding: 10px 12px; font-size: 13px; margin-bottom: 16px; }
        .chk { display: flex; gap: 8px; align-items: center; margin-top: 16px; color: #475569; font-size: 13px; font-weight: 400 !important; }
        .btn { display: block; width: 100%; margin-top: 22px; background: #2563eb; color: #fff; text-align: center; border-radius: 10px; padding: 13px; font-weight: 650; font-size: 14px; border: none; cursor: pointer; font-family: inherit; }
        .btn:hover { background: #1d4ed8; }
        .btn.sec { background: #fff; color: #0f172a; border: 1px solid #d1d5db; }
        a { color: #2563eb; text-decoration: none; font-weight: 550; }
        .note { margin-top: 22px; color: #94a3b8; font-size: 12.5px; text-align: center; }
        @media (max-width: 900px) { .wrap { grid-template-columns: 1fr; } .l { display: none; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="l">
        <div class="brand"><i>KB</i>Kontrol Biaya</div>
        <h1>Pantau realisasi biaya terhadap budget, per divisi dan per toko.</h1>
        <p>Import jurnal dari Excel, lihat serapan budget, dan ketahui lebih awal pos biaya yang melewati batas.</p>
        <ul>
            <li>Laporan realisasi vs budget per periode</li>
            <li>Riwayat import yang bisa dibatalkan</li>
            <li>Tanda otomatis untuk pos yang lewat budget</li>
        </ul>
        <div class="foot">© {{ date('Y') }} Cahaya Optima Karya</div>
    </div>
    <div class="r">
        <div class="f">
            <img src="{{ asset('metronic/media/logos/logo-cok.png') }}" alt="Logo" class="logo">
            @yield('form')
        </div>
    </div>
</div>
</body>
</html>
