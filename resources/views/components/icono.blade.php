{{--
    Íconos de línea fina para Punto Bar (SVG dentro de la página, sin cargar nada de internet).
    Toman el color del texto que los rodea (currentColor).

    Uso:
        <x-icono nombre="mesa" />
        <x-icono nombre="reloj" tamano="16" class="nav-icon" />

    Nombres disponibles: mesas, mesa, mozo, personas, productos, comanda, descuento,
    reloj, mas, check, salir, ajustes, lista, flecha-abajo, campana.
--}}

@props(['nombre' => '', 'tamano' => 20])

@php
    $iconos = [
        // Cuadrícula: la sección "Mesas"
        'mesas' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',

        // Una mesa vista de costado
        'mesa' => '<rect x="3" y="7" width="18" height="3" rx="1"/><path d="M6 10v10"/><path d="M18 10v10"/><path d="M6 15h12"/>',

        // Una persona
        'mozo' => '<circle cx="12" cy="7.5" r="3.5"/><path d="M5 20v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1"/>',

        // Dos personas
        'personas' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20v-.5A5.5 5.5 0 0 1 8.5 14h1A5.5 5.5 0 0 1 15 19.5v.5"/><path d="M15.5 5a3.2 3.2 0 0 1 0 6.2"/><path d="M17.5 14.2A5.5 5.5 0 0 1 21 19.5v.5"/>',

        // Copa: la sección "Productos"
        'productos' => '<path d="M7 3h10l-.6 5.5a4.4 4.4 0 0 1-8.8 0z"/><path d="M12 13v8"/><path d="M8 21h8"/>',

        // Ticket con renglones: comanda
        'comanda' => '<path d="M6 3h12v18l-2.5-1.5L13 21l-2.5-1.5L8 21l-2-1.2z"/><path d="M9 8h6"/><path d="M9 12h6"/><path d="M9 16h3"/>',

        // Porcentaje: descuento
        'descuento' => '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>',

        // Reloj: tiempo de espera, registrar atención
        'reloj' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',

        // Más: agregar
        'mas' => '<path d="M12 5v14"/><path d="M5 12h14"/>',

        // Tilde: cerrar comanda
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',

        // Puerta con flecha: salir
        'salir' => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="m15 16 4-4-4-4"/><path d="M19 12H9"/>',

        // Controles deslizantes: administrar
        'ajustes' => '<path d="M4 6h9"/><path d="M17 6h3"/><circle cx="15" cy="6" r="2"/><path d="M4 12h3"/><path d="M11 12h9"/><circle cx="9" cy="12" r="2"/><path d="M4 18h11"/><path d="M19 18h1"/><circle cx="17" cy="18" r="2"/>',

        // Lista: gestionar
        'lista' => '<path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',

        // Flecha hacia abajo: menú desplegable
        'flecha-abajo' => '<path d="m6 9 6 6 6-6"/>',

        // Campana: notificaciones
        'campana' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
    ];
@endphp

<svg
    {{ $attributes->merge(['class' => 'icono']) }}
    width="{{ $tamano }}"
    height="{{ $tamano }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{!! $iconos[$nombre] ?? '' !!}</svg>
