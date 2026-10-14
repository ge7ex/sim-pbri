<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ไม่มีสิทธิ์เข้าถึง · SIM PBRI</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f1f5f9; color: #243b53; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; line-height: 1.6; }
        main { box-sizing: border-box; width: min(100% - 32px, 560px); padding: 32px; background: #fff; border: 1px solid #d9e2ec; border-radius: 16px; }
        .brand { color: #486581; font-weight: 700; } .code { margin-bottom: 0; color: #486581; }
        h1 { font-size: 1.5rem; color: #102a43; } p { overflow-wrap: anywhere; }
        a { display: inline-flex; align-items: center; min-height: 44px; padding: 0 16px; border-radius: 8px; background: #102a43; color: #fff; text-decoration: none; }
        a:focus-visible { outline: 3px solid #527da3; outline-offset: 4px; }
        @media (max-width: 480px) { main { padding: 24px; } }
    </style>
</head>
<body>
    <main aria-labelledby="error-title">
        <p class="brand">SIM PBRI</p>
        <p class="code">403</p>
        <h1 id="error-title">ไม่มีสิทธิ์เข้าถึงหน้านี้</h1>
        <p>บัญชีนี้ไม่มีสิทธิ์ใช้งานหน้าหรือรายการที่เลือก หากต้องใช้ส่วนนี้ กรุณาติดต่อผู้ดูแลระบบของวิทยาลัย</p>
        <a href="{{ route('home', [], false) }}">กลับหน้าแรก</a>
    </main>
</body>
</html>
