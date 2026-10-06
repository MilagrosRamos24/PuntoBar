<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Punto Bar</title>
    <link rel="stylesheet" href="{{ asset('css/mesas.css') }}">
</head>
<body>
<header class="cabecera">
    <img src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="Punto Bar">
    <nav aria-label="Navegación de mesas">
        <a href="{{ route('mesas') }}">Panel</a>
        <a href="{{ route('admin.mesas.index') }}">Gestionar mesas</a>
        <form action="{{ route('admin.logout') }}" method="POST">@csrf<button>Cerrar sesión</button></form>
    </nav>
</header>
<main class="contenido">
    @if(session('success'))<p class="mensaje" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())
        <div class="errores" role="alert"><strong>Revisá los datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
</body>
</html>
