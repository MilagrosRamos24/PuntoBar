@extends('layouts.puntobar')

@section('title', 'Notificaciones')
@section('shell-class', 'pb-shell--productos')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/productos.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notificaciones.css') . '?v=20261024' }}">

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

        @if ($errors->any())
            <div class="pn-aviso" role="alert">{{ $errors->first() }}</div>
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

            <div class="pn-herramientas">
                <label class="pn-seleccion"><input type="checkbox" id="pn-todas"> Seleccionar todas las de esta página</label>
                <span id="pn-conteo" role="status" aria-live="polite">0 seleccionadas</span>
                <form id="pn-eliminar"
                method="POST"
                action="{{ route('admin.notificaciones.eliminar') }}">
                @csrf
                @method('DELETE')
                <input type="hidden"
                id="pn-alcance"
                name="alcance"
                value="todas">
                <button class="pp-btn pn-btn-eliminar"
                 id="pn-borrar"
                 type="submit"
                 data-total="{{ $notificaciones->total() }}">
                 Eliminar todas ({{ $notificaciones->total() }})
                </button>
            </form>
                <small>Si marcás notificaciones, se eliminan únicamente las seleccionadas. Sin selección, se elimina todo el historial, incluidas las otras páginas. </small>
            </div>

            <div class="pn-lista">
                @foreach ($notificaciones as $n)
                    <article @class(['pn-item', 'is-nueva' => ! $n->estaLeida()])>
                        <input class="pn-elegir" type="checkbox" name="ids[]" value="{{ $n->id }}" form="pn-eliminar" aria-label="Seleccionar notificación de {{ $n->nombre_producto }} del {{ $n->sin_stock_at->format('d/m/Y H:i') }}">
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
    <script>
(() => {
    const formulario = document.getElementById('pn-eliminar');

    if (!formulario) return;

    const todas = document.getElementById('pn-todas');
    const casillas = Array.from(document.querySelectorAll('.pn-elegir'));
    const boton = document.getElementById('pn-borrar');
    const alcance = document.getElementById('pn-alcance');
    const conteo = document.getElementById('pn-conteo');
    const total = Number(boton.dataset.total);

    function actualizar() {
        const cantidad = casillas.filter(c => c.checked).length;

        todas.checked = casillas.length > 0
            && cantidad === casillas.length;

        todas.indeterminate = cantidad > 0
            && cantidad < casillas.length;

        alcance.value = cantidad > 0 ? 'seleccionadas' : 'todas';

        boton.textContent = cantidad > 0
            ? 'Eliminar seleccionadas (' + cantidad + ')'
            : 'Eliminar todas (' + total + ')';

        if (conteo) {
            conteo.textContent = cantidad + ' seleccionadas';
        }
    }

    todas.addEventListener('change', () => {
        casillas.forEach(c => {
            c.checked = todas.checked;
        });

        actualizar();
    });

    casillas.forEach(c => {
        c.addEventListener('change', actualizar);
    });

    formulario.addEventListener('submit', evento => {
        actualizar();

        const cantidad = casillas.filter(c => c.checked).length;

        const mensaje = cantidad > 0
            ? '¿Eliminar las ' + cantidad
                + ' notificaciones seleccionadas? Esta acción no se puede deshacer.'
            : '¿Eliminar TODAS las ' + total
                + ' notificaciones, incluidas las de otras páginas? '
                + 'Esta acción no se puede deshacer.';

        if (!window.confirm(mensaje)) {
            evento.preventDefault();
        }
    });

    actualizar();
})();
</script>
@endsection
