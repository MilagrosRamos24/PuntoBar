<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gestión') · Punto Bar</title>
    <link rel="stylesheet" href="{{ asset('css/puntobar.css') }}">
</head>
<body>
    <main class="pb-shell @yield('shell-class')">
        <img class="logo" src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="Punto Bar">
        @yield('content')
    </main>
    @stack('scripts')
    <x-dialogo />
</body>
</html>
