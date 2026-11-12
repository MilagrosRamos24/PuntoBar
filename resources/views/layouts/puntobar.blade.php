<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gestión') · Punto Bar</title>
    <link rel="stylesheet" href="{{ asset('css/puntobar.css') }}">
    <link rel="icon"
      type="image/png"
      href="{{ asset('img/favicon.png') }}?v=3">
</head>
<body>
    @yield('admin-header')
    <main class="pb-shell @yield('shell-class')"
        @if(request()->routeIs('admin.productos.*', 'admin.notificaciones.*'))
            data-admin-fluida="{{ request()->routeIs('admin.productos.*') ? 'productos' : 'notificaciones' }}" tabindex="-1"
        @endif>
        @hasSection('admin-header')
        @else
        <img class="logo" src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="Punto Bar">
        @endif
        @yield('content')
    </main>
    @stack('scripts')
    <x-dialogo />
@if(request()->routeIs('admin.productos.*', 'admin.notificaciones.*'))
    <script src="{{ asset('js/admin-fluida.js') }}?v=20261112" defer></script>
    @endif
</body>
</html>
