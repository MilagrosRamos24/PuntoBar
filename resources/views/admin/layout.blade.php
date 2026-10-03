<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · Punto Bar</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:#12110f;color:#eee9e1;font-family:Arial,Helvetica,sans-serif}main{width:min(92%,540px);margin:0 auto;padding:30px 0 50px}.logo{display:block;width:min(100%,300px);margin:0 auto}.card{background:#1a1815;border:1px solid #403628;border-radius:20px;padding:32px}h1{font-size:26px;margin:0 0 10px}p{line-height:1.6;color:#b8aea0}label{display:block;margin:20px 0 8px}input{width:100%;padding:14px;background:#12110f;border:1px solid #635442;border-radius:10px;color:#eee9e1;font-size:16px}input:focus{outline:2px solid #e9a365;outline-offset:2px}button{cursor:pointer;width:100%;padding:14px;border:0;border-radius:10px;background:#e9a365;color:#21170e;font-weight:bold;font-size:16px;margin-top:24px}button:hover{background:#f4b880}a{color:#e9a365}.back{display:block;text-align:center;margin-top:24px}.error{border:1px solid #d88a80;background:#382321;color:#ffd2cb;padding:12px;border-radius:8px;margin:16px 0}.error p{color:inherit;margin:4px 0}.show{display:flex;align-items:center;gap:8px;font-size:14px}.show input{width:auto;accent-color:#e9a365}.badge{color:#e9a365;font-size:13px;letter-spacing:1px;text-transform:uppercase}.modules{display:grid;gap:12px;margin-top:24px}.module{padding:16px;border:1px solid #403628;border-radius:10px}.module p{margin:6px 0 0;font-size:14px}@media(max-width:420px){.card{padding:24px}}
</style>
</head>
<body><main><img class="logo" src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="Punto Bar">@yield('content')</main></body>
</html>
