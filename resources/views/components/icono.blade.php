{{--
    Íconos de línea fina para Punto Bar (SVG dentro de la página, sin cargar nada de internet).
    Toman el color del texto que los rodea (currentColor).

    Uso: x-icono con el atributo nombre (obligatorio), y opcionalmente tamano y class.
    Ejemplo: x-icono nombre="reloj" tamano="16"

    Nombres disponibles: mesas, mesa, mozo, personas, productos, comanda, descuento,
    reloj, mas, check, salir, ajustes, lista, flecha-abajo, campana,
    editar, eliminar, volver, guardar, alerta, reloj-arena,
    filtro, ver, quitar, siguiente.

    Para agregar un ícono: sumar una línea al arreglo de abajo, con su nombre y su dibujo SVG.
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

        // Lápiz: editar
        'editar' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',

        // Tacho: eliminar o dar de baja
        'eliminar' => '<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/>',

        // Flecha hacia la izquierda: volver
        'volver' => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',

        // Tilde dentro de un círculo: guardar o actualizar
        'guardar' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/>',

        // Triángulo con signo de exclamación: alerta, stock bajo
        'alerta' => '<path d="M12 3.5 2.5 20h19z"/><path d="M12 10v4.5"/><path d="M12 17.5h.01"/>',

        // Reloj de arena: mesa en alerta por demora
        'reloj-arena' => '<path d="M6 3h12"/><path d="M6 21h12"/><path d="M7 3v3.5a5 5 0 0 0 2.2 4.1L12 12l2.8-1.4A5 5 0 0 0 17 6.5V3"/><path d="M7 21v-3.5a5 5 0 0 1 2.2-4.1L12 12l2.8 1.4A5 5 0 0 1 17 17.5V21"/>',

        // Embudo: filtrar
        'filtro' => '<path d="M4 5h16l-6 7.5V19l-4 1.5v-8z"/>',

        // Ojo: ver el detalle
        'ver' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',

        // Cruz: quitar filtros, cerrar
        'quitar' => '<path d="M6 6l12 12"/><path d="M18 6 6 18"/>',

        // Flecha hacia la derecha: siguiente
        'siguiente' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
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
