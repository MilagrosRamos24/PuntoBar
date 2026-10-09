@extends('layouts.puntobar')

@section('title', 'Notificaciones')
@section('shell-class', 'pb-shell--productos')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/productos.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notificaciones.css') }}">

    <div class="pp">
        <header class="pp-header">
            <div class="pp-brand">
                <img class="pp-logo" src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="Punto Bar">
                <div>
                    <h1>Notificaciones</h1>
                    <p>Productos que se quedaron sin stock</p>
                </div>
            </div>

            <div class="pp-header-right">
                <nav class="pp-nav" aria-label="Secciones del administrador">
                    <a href="{{ route('mesas') }}">Mesas</a>
                    <a href="{{ route('admin.mesas.index') }}">Gestión de mesas</a>
                    <a href="{{ route('admin.productos.index') }}">Productos</a>
                </nav>
                <x-campana-notificaciones />
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="pp-link-btn">Cerrar sesión</button>
                </form>
            </div>
        </header>

        @if (session('success'))
            <div class="pn-aviso" role="status">{{ session('success') }}</div>
        @endif

        @if ($notificaciones->total() === 0)
            <div class="pn-vacio">
                <strong>No hay notificaciones pendientes.</strong>
                <span>Cuando un producto se quede sin stock, vas a verlo acá.</span>
            </div>
        @else
            <p class="pn-resumen">
                @if ($noLeidas > 0)
                    <strong>{{ $noLeidas }}</strong> {{ $noLeidas === 1 ? 'notificación sin leer' : 'notificaciones sin leer' }}
                @else
                    No tenés notificaciones sin leer.
                @endif
            </p>

            <div class="pn-lista">
                @foreach ($notificaciones as $n)
                    <article @class(['pn-item', 'is-nueva' => ! $n->estaLeida()])>
                        <span class="pn-icono" aria-hidden="true">!</span>

                        <div class="pn-cuerpo">
                            <p class="pn-titulo"><strong>{{ $n->nombre_producto }}</strong> se quedó sin stock</p>
                            <p class="pn-detalle">
                                Se agotó el {{ $n->sin_stock_at->format('d/m/Y') }} a las {{ $n->sin_stock_at->format('H:i') }}
                                ·
                                @if ($n->sigueSinStock())
                                    todavía sin stock
                                @else
                                    repuesto el {{ $n->repuesto_at->format('d/m/Y') }} a las {{ $n->repuesto_at->format('H:i') }}
                                @endif
                            </p>
                        </div>

                        <div class="pn-acciones">
                            @if ($n->estaLeida())
                                <span class="pp-chip pp-chip--muted">Leída</span>
                            @else
                                <span class="pp-chip pp-chip--warn">Sin leer</span>
                                <form method="POST" action="{{ route('admin.notificaciones.leida', $n) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="pp-btn pp-btn--ghost pp-btn--sm">Marcar como leída</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($notificaciones->hasPages())
                <nav class="pn-paginas" aria-label="Paginación">
                    @if ($notificaciones->onFirstPage())
                        <span aria-disabled="true">‹ Más recientes</span>
                    @else
                        <a href="{{ $notificaciones->previousPageUrl() }}">‹ Más recientes</a>
                    @endif

                    @if ($notificaciones->hasMorePages())
                        <a href="{{ $notificaciones->nextPageUrl() }}">Más antiguas ›</a>
                    @else
                        <span aria-disabled="true">Más antiguas ›</span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection