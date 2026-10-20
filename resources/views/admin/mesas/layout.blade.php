<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Punto Bar</title>
    <link rel="stylesheet" href="{{ asset('css/mesas.css') }}">
</head>
<body>
<x-admin-nav :titulo="trim($__env->yieldContent('title', 'Gestión de mesas'))" />
<main class="contenido">
    @if(session('success'))<p class="mensaje" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())
        <div class="errores" role="alert"><strong>Revisá los datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
<x-dialogo />
</body>
</html>
