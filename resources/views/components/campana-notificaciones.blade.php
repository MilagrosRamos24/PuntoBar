{{-- Campana de notificaciones. Solo la ve el administrador; el mozo no ve nada. --}}
@if (auth()->check() && auth()->user()->role === 'admin')
    @once
        <link rel="stylesheet" href="{{ asset('css/notificaciones.css') }}">
    @endonce

    @php
        $pendientes = \App\Models\NotificacionStock::noLeidas()->count();
    @endphp

    <a href="{{ route('admin.notificaciones.index') }}"
       @class(['pn-campana', 'is-activa' => request()->routeIs('admin.notificaciones.*')])
       aria-label="Notificaciones{{ $pendientes > 0 ? ': ' . $pendientes . ' sin leer' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path></svg>
        @if ($pendientes > 0)
            <span class="pn-contador" aria-hidden="true">{{ $pendientes > 99 ? '99+' : $pendientes }}</span>
        @endif
    </a>
@endif