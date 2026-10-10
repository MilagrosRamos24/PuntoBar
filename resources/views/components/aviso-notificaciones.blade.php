{{-- Aviso de notificaciones sin leer para el panel principal del administrador.
     Solo lo ve el administrador y solo aparece si hay algo sin leer. --}}
@if (auth()->check() && auth()->user()->role === 'admin')
    @php
        $sinLeer = \App\Models\NotificacionStock::noLeidas()->count();
        $ultimas = $sinLeer > 0
            ? \App\Models\NotificacionStock::noLeidas()->recientes()->limit(3)->get()
            : collect();
    @endphp

    @if ($sinLeer > 0)
        @once
            <link rel="stylesheet" href="{{ asset('css/notificaciones.css') }}">
        @endonce

        <section class="pn-banner" role="status" aria-label="Notificaciones sin leer">
            <span class="pn-banner-icono" aria-hidden="true">!</span>

            <div class="pn-banner-cuerpo">
                <strong class="pn-banner-titulo">
                    {{ $sinLeer === 1 ? 'Tenés 1 notificación sin leer' : 'Tenés ' . $sinLeer . ' notificaciones sin leer' }}
                </strong>
                <ul class="pn-banner-lista">
                    @foreach ($ultimas as $n)
                        <li><strong>{{ $n->nombre_producto }}</strong> se quedó sin stock <span>· {{ $n->sin_stock_at->format('d/m H:i') }}</span></li>
                    @endforeach
                    @if ($sinLeer > $ultimas->count())
                        <li>y {{ $sinLeer - $ultimas->count() }} más</li>
                    @endif
                </ul>
            </div>

            <a href="{{ route('admin.notificaciones.index') }}" class="pn-banner-enlace">Ver notificaciones</a>
        </section>
    @endif
@endif