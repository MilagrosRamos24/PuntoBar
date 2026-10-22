@props(['titulo' => 'Administración'])
<link rel="stylesheet" href="{{ asset('css/admin-nav.css') }}?v=20261020">
<header class="pb-admin-nav">
    <div class="pb-admin-brand">
        <img src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="Punto Bar" class="pb-admin-logo">
        <div class="pb-admin-heading">
            <h1>{{ $titulo }}</h1>
            <p>Panel de administración</p>
        </div>
    </div>
    <nav class="pb-admin-links" aria-label="Secciones del administrador">
        @foreach ([
            ['mesas', 'Mesas', 'mesas', request()->routeIs('mesas', 'mesas.*', 'admin.mesas.*')],
            ['admin.mozos.index', 'Mozos', 'mozo', request()->routeIs('admin.mozos.*')],
            ['admin.productos.index', 'Productos', 'productos', request()->routeIs('admin.productos.*')],
            ['admin.comandas.index', 'Comandas', 'comanda', request()->routeIs('admin.comandas.*', 'comandas.*')],
            ['admin.descuento.edit', 'Descuento', 'descuento', request()->routeIs('admin.descuento.*')],
        ] as [$ruta, $texto, $icono, $activo])
            <a href="{{ route($ruta) }}" class="pb-admin-link {{ $activo ? 'is-active' : '' }}" @if($activo) aria-current="page" @endif>
                <x-icono :nombre="$icono" tamano="20" />
                {{ $texto }}
            </a>
        @endforeach
          <a href="{{ route('ayuda') }}" class="pb-admin-link">
            <svg class="icono" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5V5.5Z"/><path d="M4 21.5A2.5 2.5 0 0 1 6.5 19H20"/></svg>
            Ayuda
        </a>
        <x-campana-notificaciones />
    </nav>
    <div class="pb-admin-account">
        <span>Vista de administrador</span>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</header>
