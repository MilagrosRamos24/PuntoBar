@php
    $esAdmin = auth()->check() && auth()->user()->role === 'admin';

    $totalMesas = $mesas->count();

    $mesasLibres = $mesas->where('estado', 'libre')->count();

    $mesasAlertas = $mesas->where('estado', 'pendiente_de_cierre')->count();

    $nombreUsuario = auth()->user()->name ?? 'Usuario';

    // Formato de precios: $4.000 o $4.250,50
    $pesos = function ($valor) {
        $valor = (float) $valor;
        $decimales = fmod($valor, 1) == 0 ? 0 : 2;

        return '$' . number_format($valor, $decimales, ',', '.');
    };

    $estadoInfo = [
        'libre' => [
            'label' => 'Libre',
            'clase' => 'libre',
        ],

        'ocupada' => [
            'label' => 'En espera de pedido',
            'clase' => 'espera',
        ],
        'espera' => [
            'label' => 'En espera de pedido',
            'clase' => 'espera',
        ],
        'esperando_pedido' => [
            'label' => 'En espera de pedido',
            'clase' => 'espera',
        ],

        'reservada' => [
            'label' => 'Mesa atendida',
            'clase' => 'atendida',
        ],
        'atendida' => [
            'label' => 'Mesa atendida',
            'clase' => 'atendida',
        ],

        'pendiente_de_cierre' => [
            'label' => 'Alerta de atención',
            'clase' => 'alerta',
        ],
        'alerta' => [
            'label' => 'Alerta de atención',
            'clase' => 'alerta',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Gestión de mesas - PUNTO BAR</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #12100d;
            color: #f1e9df;
            min-height: 100vh;
        }

        /* =========================
           BARRA SUPERIOR
        ========================= */

        .topbar {
            height: 92px;
            display: flex;
            align-items: center;
            padding: 0 40px;
            background: #15130f;
            border-bottom: 1px solid #34271c;
        }

        .brand {
            display: flex;
            align-items: center;
            min-width: 360px;
        }

        .logo {
            width: 125px;
            height: auto;
            padding-right: 25px;
            margin-right: 25px;
            border-right: 1px solid #403126;
        }

        .titulo-sistema h1 {
            font-size: 28px;
            font-weight: 700;
            color: #f5eee7;
        }

        .titulo-sistema p {
            margin-top: 5px;
            color: #9e948b;
            font-size: 15px;
        }

        .navegacion {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: 9px;
            color: #c7bdb4;
            text-decoration: none;
            font-size: 16px;
            border: 1px solid transparent;
        }

        .nav-item:hover {
            color: #f0a15f;
            border-color: #4a3424;
        }

        .nav-item.activo {
            color: #f39b55;
            background: #211a14;
            border-color: #543a26;
        }

        .nav-icon {
            font-size: 21px;
        }

        .usuario {
            min-width: 210px;
            padding-left: 30px;
            border-left: 1px solid #403126;
            text-align: right;
            color: #a79b91;
            font-size: 14px;
        }

        .usuario strong {
            color: #e3d9d0;
        }

        /* =========================
           CONTENIDO
        ========================= */

        .contenido {
            padding: 28px 30px 30px;
            max-width: 1550px;
            margin: auto;
        }

        /* =========================
           ESTADÍSTICAS
        ========================= */

        .estadisticas {
            display: flex;
            align-items: center;
            gap: 35px;
            margin: 0 10px 28px;
        }

        .estadistica {
            display: flex;
            align-items: baseline;
            gap: 9px;
        }

        .estadistica strong {
            font-size: 34px;
            color: #f4eee8;
        }

        .estadistica span {
            font-size: 17px;
            color: #b2a69c;
        }

        .separador {
            width: 1px;
            height: 35px;
            background: #4a3829;
        }

        /* =========================
           ACCIONES ADMIN
        ========================= */

        .barra-acciones {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 22px;
        }

        .admin-menu {
            position: relative;
        }

        .admin-menu summary {
            list-style: none;
            cursor: pointer;
            padding: 13px 20px;
            border: 1px solid #a05d2d;
            border-radius: 9px;
            color: #ef9a56;
            background: #17130f;
            font-size: 15px;
        }

        .admin-menu summary::-webkit-details-marker {
            display: none;
        }

        .admin-menu summary:hover {
            background: #211810;
        }

        .admin-dropdown {
            position: absolute;
            right: 0;
            top: 52px;
            width: 230px;
            background: #1b1712;
            border: 1px solid #58402c;
            border-radius: 10px;
            padding: 8px;
            z-index: 50;
            box-shadow: 0 15px 35px rgba(0, 0, 0, .45);
        }

        .admin-dropdown a {
            display: block;
            padding: 12px 14px;
            color: #d8cec5;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
        }

        .admin-dropdown a:hover {
            background: #282018;
            color: #f1a060;
        }

        /* =========================
           GRID DE MESAS
        ========================= */

        .mesas-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .mesa {
            position: relative;
            min-height: 275px;
            padding: 24px 20px 18px;
            background: #1b1915;
            border: 1px solid #3d3024;
            border-radius: 14px;
            transition:
                transform .2s ease,
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .mesa:hover {
            transform: translateY(-2px);
            border-color: #62462e;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .20);
        }

        .mesa.seleccionada {
            border: 2px solid #f39b55;
            box-shadow: 0 0 0 1px rgba(243, 155, 85, .25);
            transform: translateY(-2px);
        }

        .mesa.seleccionada::after {
            content: "✓ Seleccionada";
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 5px 9px;
            border-radius: 6px;
            background: #f39b55;
            color: #1b1510;
            font-size: 11px;
            font-weight: 700;
        }

        .mesa.alerta {
            border: 2px solid #df7b48;
        }

        /* =========================
           CABECERA MESA
        ========================= */

        .mesa-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .mesa-icono {
            width: 60px;
            height: 60px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 29px;
            border: 1px solid;
        }

        .mesa-icono.libre {
            color: #5ba8ff;
            background: rgba(30, 115, 220, .16);
            border-color: #287ad7;
        }

        .mesa-icono.espera {
            color: #ffd03b;
            background: rgba(220, 165, 20, .14);
            border-color: #dca91e;
        }

        .mesa-icono.atendida {
            color: #45d77d;
            background: rgba(24, 180, 86, .13);
            border-color: #1fb85e;
        }

        .mesa-icono.alerta {
            color: #ff6970;
            background: rgba(205, 55, 65, .15);
            border-color: #dc4650;
        }

        .mesa-titulo h2 {
            font-size: 25px;
            color: #f5eee8;
            margin-bottom: 8px;
        }

        .estado {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .estado.libre {
            color: #fff;
            background: #1976d2;
        }

        .estado.espera {
            color: #21190d;
            background: #ffc42f;
        }

        .estado.atendida {
            color: #102117;
            background: #32cf6a;
        }

        .estado.alerta {
            color: #fff;
            background: #d9434b;
        }

        /* =========================
           INFORMACIÓN
        ========================= */

        .mesa-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
            color: #d3c9c0;
            font-size: 15px;
            margin-top: 5px;
        }

        .info-linea {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon {
            width: 27px;
            color: #c9bdb2;
            font-size: 19px;
            text-align: center;
        }

        .mesa-separador {
            height: 1px;
            background: #3b3026;
            margin: 18px 0 15px;
        }

        .mesa-total {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #c8bdb4;
            font-size: 16px;
        }

        .mesa-total strong {
            font-size: 21px;
        }

        .mesa-total.libre strong {
            color: #3193f5;
        }

        .mesa-total.espera strong {
            color: #ffd03b;
        }

        .mesa-total.atendida strong {
            color: #35d878;
        }

        .mesa-total.alerta strong {
            color: #ff5962;
        }

        /* =========================
           ACCIONES DE MESA
        ========================= */

        .mesa-acciones {
            display: flex;
            gap: 8px;
            margin-top: 18px;
        }

        .mesa-acciones form {
            flex: 1;
            display: flex;
        }

        .btn-mesa {
            flex: 1;
            padding: 9px 8px;
            border-radius: 7px;
            border: 1px solid #4d3a2a;
            background: transparent;
            color: #d3c7bd;
            cursor: pointer;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
        }

        .btn-mesa:hover {
            border-color: #a66538;
            color: #f0a05d;
        }

        .btn-mesa.btn-comanda {
            border-color: #a05d2d;
            color: #f39b55;
            font-weight: 700;
        }

        .btn-mesa.btn-comanda:hover {
            background: #211810;
        }

        /* =========================
           LEYENDA
        ========================= */

        .leyenda {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 38px;
            margin-top: 28px;
            padding: 20px 0;
            border-top: 1px solid #393027;
        }

        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #c8bdb4;
            font-size: 14px;
        }

        .punto {
            width: 13px;
            height: 13px;
            border-radius: 50%;
        }

        .punto.azul {
            background: #2588ed;
        }

        .punto.amarillo {
            background: #ffc42f;
        }

        .punto.verde {
            background: #2ed16b;
        }

        .punto.rojo {
            background: #ef4e58;
        }

        .leyenda-alerta {
            padding-left: 30px;
            border-left: 1px solid #493b30;
            color: #d0c5bc;
            font-size: 14px;
        }

        /* =========================
           BARRA INFERIOR
        ========================= */

        .barra-inferior {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
            padding: 18px 22px;
            background: #1b1813;
            border: 1px solid #3d3025;
            border-radius: 13px;
        }

        .seleccion {
            color: #ddd3ca;
            font-size: 17px;
        }

        .seleccion strong {
            color: #f4eee8;
        }

        .acciones-principales {
            display: flex;
            gap: 10px;
        }

        .btn-principal {
            padding: 13px 20px;
            border-radius: 8px;
            border: 1px solid #5d4834;
            background: transparent;
            color: #ded3ca;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-principal:hover {
            border-color: #d27a40;
            color: #f0a060;
        }

        .btn-atencion {
            background: #f09b58;
            border-color: #f09b58;
            color: #1c1510;
            font-weight: 700;
        }

        .btn-atencion:hover {
            background: #ffad6a;
            color: #1c1510;
        }

        .btn-salir {
            padding: 13px 22px;
            border-radius: 8px;
            border: 1px solid #604936;
            background: transparent;
            color: #ded3ca;
            cursor: pointer;
        }

        .btn-salir:hover {
            border-color: #b86b3b;
            color: #ef9c5a;
        }

        /* =========================
           SIN MESAS
        ========================= */

        .sin-mesas {
            grid-column: 1 / -1;
            padding: 70px;
            text-align: center;
            color: #9f948b;
            background: #1b1915;
            border: 1px solid #3d3024;
            border-radius: 14px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1200px) {

            .topbar {
                padding: 0 20px;
            }

            .brand {
                min-width: 300px;
            }

            .navegacion {
                gap: 0;
            }

            .nav-item {
                padding: 12px;
            }

            .usuario {
                min-width: 150px;
            }

            .mesas-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {

            .topbar {
                height: auto;
                padding: 18px;
                flex-wrap: wrap;
                gap: 15px;
            }

            .brand {
                min-width: 100%;
            }

            .navegacion {
                order: 3;
                width: 100%;
                overflow-x: auto;
                justify-content: flex-start;
            }

            .usuario {
                border-left: none;
                margin-left: auto;
                padding-left: 0;
            }

            .mesas-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .barra-inferior {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .acciones-principales {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 600px) {

            .contenido {
                padding: 20px 15px;
            }

            .estadisticas {
                flex-wrap: wrap;
                gap: 15px;
            }

            .separador {
                display: none;
            }

            .mesas-grid {
                grid-template-columns: 1fr;
            }

            .leyenda {
                flex-wrap: wrap;
                gap: 15px;
            }

            .leyenda-alerta {
                border-left: none;
                padding-left: 0;
                width: 100%;
                text-align: center;
            }

            .acciones-principales {
                flex-direction: column;
            }

            .btn-principal,
            .btn-salir {
                width: 100%;
            }
        }

        /* =========================
           MODAL COMANDA
        ========================= */

        .modal-comanda {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.75);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-comanda.activo {
            display: flex;
        }

        .comanda-contenido {
            position: relative;
            width: min(600px, 100%);
            max-height: 90vh;
            overflow-y: auto;
            background: #1b1815;
            border: 1px solid #5c4028;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .comanda-cerrar {
            position: absolute;
            top: 15px;
            right: 18px;
            border: none;
            background: transparent;
            color: #cfc5bc;
            font-size: 30px;
            cursor: pointer;
        }

        .comanda-cerrar:hover {
            color: #f39b55;
        }

        .comanda-header {
            padding-right: 35px;
            margin-bottom: 25px;
        }

        .comanda-header .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            background: #f39b55;
            color: #1b1510;
            font-size: 11px;
            font-weight: 700;
        }

        .comanda-header h2 {
            margin: 12px 0 6px;
            color: #f3e1cf;
            font-size: 28px;
        }

        .comanda-header p {
            margin: 0;
            color: #a99d94;
        }

        .comanda-resumen {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .comanda-resumen > div {
            padding: 15px;
            background: #211d19;
            border: 1px solid #4d3827;
            border-radius: 10px;
        }

        .comanda-resumen span {
            display: block;
            margin-bottom: 6px;
            color: #8f837a;
            font-size: 12px;
        }

        .comanda-resumen strong {
            color: #f3e1cf;
        }

        .comanda-productos {
            padding: 20px 0;
            border-top: 1px solid #4d3827;
            border-bottom: 1px solid #4d3827;
        }

        .comanda-productos h3 {
            margin: 0 0 15px;
            color: #f3e1cf;
        }

        .sin-productos {
            padding: 25px;
            text-align: center;
            color: #8f837a;
            background: #211d19;
            border-radius: 10px;
        }

        .comanda-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 22px;
        }

        .comanda-total span {
            color: #cfc5bc;
            font-size: 18px;
        }

        .comanda-total strong {
            color: #f39b55;
            font-size: 28px;
        }

        @media (max-width: 600px) {

            .comanda-resumen {
                grid-template-columns: 1fr;
            }

            .comanda-contenido {
                padding: 22px;
            }
        }

        /* Contenido de la comanda dentro de la ventana */

        .comanda-errores {
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #d88a80;
            border-radius: 10px;
            background: #382321;
            color: #ffd2cb;
            font-size: 14px;
        }

        .comanda-linea {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px 12px;
            padding: 12px 0;
            border-bottom: 1px solid #2e261d;
        }

        .comanda-linea:last-child {
            border-bottom: none;
        }

        .comanda-linea .nombre {
            color: #f3e1cf;
        }

        .comanda-linea .precio {
            color: #8f837a;
            font-size: 13px;
            margin-top: 3px;
        }

        .comanda-linea .importe {
            color: #f3e1cf;
            font-weight: 700;
            text-align: right;
        }

        .comanda-linea-acciones {
            grid-column: 1 / -1;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .comanda-linea-acciones form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .comanda-contenido input[type="number"],
        .comanda-contenido select {
            padding: 9px 10px;
            border-radius: 7px;
            border: 1px solid #5d4834;
            background: #12100d;
            color: #f1e9df;
            font-size: 14px;
        }

        .comanda-linea-acciones input[type="number"] {
            width: 72px;
        }

        .btn-chico {
            padding: 9px 12px;
            border-radius: 7px;
            border: 1px solid #5d4834;
            background: transparent;
            color: #ded3ca;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-chico:hover {
            border-color: #d27a40;
            color: #f0a060;
        }

        .btn-chico.peligro {
            border-color: #8c4a42;
            color: #ffb3a8;
        }

        .btn-chico.peligro:hover {
            background: #382321;
        }

        .comanda-agregar {
            display: grid;
            grid-template-columns: 1fr 80px auto;
            gap: 10px;
            align-items: end;
            padding: 20px 0;
            border-bottom: 1px solid #4d3827;
        }

        .comanda-agregar label {
            display: block;
            margin-bottom: 6px;
            color: #8f837a;
            font-size: 12px;
        }

        .comanda-agregar select,
        .comanda-agregar input[type="number"] {
            width: 100%;
        }

        .comanda-subtotales {
            display: grid;
            gap: 6px;
            padding-top: 18px;
            color: #a99d94;
            font-size: 14px;
        }

        .comanda-subtotales div {
            display: flex;
            justify-content: space-between;
        }

        .comanda-cierre {
            margin-top: 22px;
        }

        .comanda-cierre .btn-atencion {
            width: 100%;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        .comanda-contenido :focus-visible {
            outline: 3px solid #e2a15c;
            outline-offset: 2px;
        }

        @media (max-width: 600px) {

            .comanda-agregar {
                grid-template-columns: 1fr 80px;
            }

            .comanda-agregar button {
                grid-column: 1 / -1;
            }
        }

        /* =========================
           ESTADOS (nombres guardados en la base)
        ========================= */

        .mesa-icono.ocupada {
            color: #ffd03b;
            background: rgba(220, 165, 20, .14);
            border-color: #dca91e;
        }

        .estado.ocupada {
            color: #21190d;
            background: #ffc42f;
        }

        .mesa-total.ocupada strong {
            color: #ffd03b;
        }

        .mesa-icono.reservada {
            color: #45d77d;
            background: rgba(24, 180, 86, .13);
            border-color: #1fb85e;
        }

        .estado.reservada {
            color: #102117;
            background: #32cf6a;
        }

        .mesa-total.reservada strong {
            color: #35d878;
        }

        .mesa-icono.pendiente_de_cierre {
            color: #ff6970;
            background: rgba(205, 55, 65, .15);
            border-color: #dc4650;
        }

        .estado.pendiente_de_cierre {
            color: #fff;
            background: #d9434b;
        }

        .mesa-total.pendiente_de_cierre strong {
            color: #ff5962;
        }

        .mesa.pendiente_de_cierre {
            border: 2px solid #df7b48;
        }

        .estado-form { margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .estado-form label { width: 100%; color: #cfc3b8; font-size: 13px; }
        .estado-form select, .estado-form button { padding: 8px; border-radius: 6px; border: 1px solid #80634e; background: #211812; color: #f1e9df; }
        .estado-form select { flex: 1; min-width: 120px; }
        .estado-form button { cursor: pointer; }
        .estado-form :focus-visible { outline: 3px solid #e2a15c; outline-offset: 2px; }
        .feedback-mesas { padding: 16px; margin-bottom: 20px; border: 1px solid #80634e; border-radius: 8px; color: #f1e9df; }

    </style>

</head>

<body>

    {{-- =========================
         BARRA SUPERIOR
    ========================= --}}

    <header class="topbar">

        <div class="brand">

            <img
                src="{{ asset('img/punto-bar-logo-transparente.png') }}"
                alt="PUNTO BAR"
                class="logo"
            >

            <div class="titulo-sistema">

                <h1>Gestión de mesas</h1>

                <p>
                    {{ $esAdmin ? 'Panel de administración' : 'Panel de mozo' }}
                </p>

            </div>

        </div>


        <nav class="navegacion">

            <a
                href="{{ route('mesas') }}"
                class="nav-item activo"
            >
                <span class="nav-icon">▣</span>
                Mesas
            </a>


            @if ($esAdmin)

                <span class="nav-item">
                    <span class="nav-icon">♙</span>
                    Mozos
                </span>

                            <a href="{{ route('admin.productos.index') }}" class="nav-item">
                    <span class="nav-icon">♜</span>
                    Productos
                </a>

                <a href="{{ route('admin.comandas.index') }}" class="nav-item">
                <span class="nav-icon">▤</span>
                Comandas
                </a>


            @endif

        </nav>


        <div class="usuario">

            <strong>
                {{ $esAdmin ? 'Vista de administrador' : 'Mozo: ' . $nombreUsuario }}
            </strong>

        </div>

    </header>


    {{-- =========================
         CONTENIDO
    ========================= --}}

    <main class="contenido">
        @if(session('success'))<p class="feedback-mesas" role="status">{{ session('success') }}</p>@endif
        {{-- Los errores de una comanda se muestran dentro de su ventana --}}
        @if($errors->any() && ! $comandaParaAbrir)<div class="feedback-mesas" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif


        {{-- ESTADÍSTICAS --}}

        <section class="estadisticas">

            <div class="estadistica">
                <strong>{{ $totalMesas }}</strong>
                <span>Mesas</span>
            </div>

            <div class="separador"></div>

            <div class="estadistica">
                <strong>{{ $mesasLibres }}</strong>
                <span>Libres</span>
            </div>

            <div class="separador"></div>

            <div class="estadistica">
                <strong>{{ $mesasAlertas }}</strong>
                <span>Pendientes de cierre</span>
            </div>

        </section>


        {{-- ADMINISTRAR MESAS --}}

        @if ($esAdmin)

            <div class="barra-acciones">

                <details class="admin-menu">

                    <summary>
                        ⚙ Administrar mesas ▾
                    </summary>

                    <div class="admin-dropdown">

                        <a href="{{ route('admin.mesas.create') }}">
                            ⊕ &nbsp; Agregar mesa
                        </a>

                        <a href="{{ route('admin.mesas.index') }}">
                            ☷ &nbsp; Gestionar mesas
                        </a>

                    </div>

                </details>

            </div>

        @endif


        {{-- =========================
             MESAS
        ========================= --}}

        <section class="mesas-grid">

            @forelse ($mesas as $mesa)

                @php

                    $estado = $estadoInfo[$mesa->estado] ?? [
                        'label' => ucfirst($mesa->estado),
                        'clase' => 'libre',
                    ];

                    $claseEstado = $estado['clase'];

                    $esMiMesa = (int) $mesa->mozo_id === (int) auth()->id();

                    $puedeOperar = $esAdmin || $esMiMesa;

                    $comanda = $mesa->comandaAbierta;

                @endphp


                <article
                    class="mesa {{ $claseEstado }}"
                    data-mesa-id="{{ $mesa->id }}"
                    data-mesa-numero="{{ $mesa->numero }}"
                    data-mesa-mozo="{{ $mesa->mozo ? $mesa->mozo->name : 'Sin asignar' }}"
                    data-mesa-estado="{{ $estado['label'] }}"
                    data-mesa-personas="{{ $mesa->cantidad_personas }}"
                    data-comanda-id="{{ $comanda?->id }}"
                >

                    <div class="mesa-titulo">

                        <h2>
                            Mesa {{ $mesa->numero }}
                        </h2>

                        <span class="estado {{ $claseEstado }}">
                            {{ $estado['label'] }}
                        </span>

                    </div>


                    <div class="mesa-info">

                        <div class="info-linea">

                            <span class="info-icon">
                                ♟
                            </span>

                            <span>
                                @if ($mesa->mozo)
                                    Mozo: {{ $mesa->mozo->name }}
                                @else
                                    Sin asignar
                                @endif
                            </span>

                        </div>


                        <div class="info-linea">

                            <span class="info-icon">
                                ♟
                            </span>

                            <span>
                                Personas:
                                {{ $mesa->cantidad_personas }}
                            </span>

                        </div>

                    </div>


                    <div class="mesa-separador"></div>


                    <div class="mesa-total {{ $claseEstado }}">

                        <span class="info-icon">
                            ▤
                        </span>

                        <span>
                            Total:
                        </span>

                        <strong>
                            {{ $pesos($comanda->total ?? 0) }}
                        </strong>

                    </div>


                    {{-- Comanda (PB-10): abrir o ver la comanda de la mesa --}}
                    @if ($puedeOperar)
                        <div class="mesa-acciones">
                            @if ($comanda)
                                <button
                                    type="button"
                                    class="btn-mesa btn-comanda"
                                    data-abrir-comanda="{{ $comanda->id }}"
                                >
                                    ▤ &nbsp; Ver comanda
                                </button>
                            @elseif ($mesa->mozo_id)
                                <form method="POST" action="{{ route('comandas.store', $mesa) }}">
                                    @csrf
                                    <button type="submit" class="btn-mesa btn-comanda">
                                        ⊕ &nbsp; Abrir comanda
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif


                    @if ($puedeOperar)

                        <form class="estado-form" action="{{ route('mesas.estado', $mesa) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <label for="estado-mesa-{{ $mesa->id }}">Estado de la mesa {{ $mesa->numero }}</label>
                            <select id="estado-mesa-{{ $mesa->id }}" name="estado" required>
                                @if(!array_key_exists($mesa->estado, \App\Models\Mesa::ESTADOS))
                                    <option value="" selected disabled>Elegí un nuevo estado</option>
                                @endif
                                @foreach(\App\Models\Mesa::ESTADOS as $valor => $texto)
                                    <option value="{{ $valor }}" @selected($mesa->estado === $valor)>{{ $texto }}</option>
                                @endforeach
                            </select>
                            <button type="submit">Actualizar</button>
                        </form>

                    @endif


                    @if ($esAdmin)
                        <div class="mesa-acciones">
                            <a href="{{ route('admin.mesas.show', $mesa) }}" class="btn-mesa">
                                Consultar
                            </a>
                            <a href="{{ route('admin.mesas.edit', $mesa) }}" class="btn-mesa">
                                Editar
                            </a>
                        </div>
                    @elseif ($esMiMesa)
                        <div class="mesa-acciones">
                            <a href="{{ route('mesas.show', $mesa) }}" class="btn-mesa">
                                Acceder a mi mesa
                            </a>
                        </div>
                    @endif

                </article>

            @empty

                <div class="sin-mesas">

                    No hay mesas registradas.

                </div>

            @endforelse

        </section>


        {{-- =========================
             LEYENDA
        ========================= --}}

        <section class="leyenda" aria-label="Estados de mesa">

            <div class="leyenda-item">
                <span class="punto azul"></span>
                Libre
            </div>

            <div class="leyenda-item">
                <span class="punto amarillo"></span>
                En espera de pedido
            </div>

            <div class="leyenda-item">
                <span class="punto verde"></span>
                Mesa atendida
            </div>

            <div class="leyenda-item">
                <span class="punto rojo"></span>
                Alerta de atención
            </div>

        </section>


        {{-- =========================
             BARRA INFERIOR
        ========================= --}}

        <section class="barra-inferior">

            <div class="seleccion" id="mesa-seleccionada">

                <span id="texto-seleccion">
                    Seleccioná una mesa para ver sus detalles
                </span>

            </div>


            <div class="acciones-principales">

                <button
                    type="button"
                    class="btn-principal"
                    id="btn-ver-comanda"
                >
                    ▤ &nbsp; Ver comanda
                </button>

                <button
                    type="button"
                    class="btn-principal btn-atencion"
                >
                    ◷ &nbsp; Registrar atención
                </button>

                <button
                    type="button"
                    class="btn-principal"
                    id="btn-cerrar-comanda"
                >
                    ✓ &nbsp; Cerrar comanda
                </button>

                <form
                    method="POST"
                    action="{{ $esAdmin ? route('admin.logout') : route('mozo.logout') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn-salir"
                    >
                        ⇥ &nbsp; Salir
                    </button>
                </form>

            </div>

        </section>

    </main>


    {{-- =========================
         VENTANAS DE COMANDA (Módulo IV)
         Una por cada comanda abierta que el usuario puede ver.
    ========================= --}}

    @foreach ($mesas as $mesa)

        @php
            $comanda = $mesa->comandaAbierta;
            $puedeVerComanda = $comanda && ($esAdmin || (int) $comanda->mozo_id === (int) auth()->id());
            $esLaComandaActual = $comanda && (int) $comandaParaAbrir === (int) $comanda->id;
        @endphp

        @if ($puedeVerComanda)

            <div
                class="modal-comanda"
                id="modal-comanda-{{ $comanda->id }}"
                role="dialog"
                aria-modal="true"
                aria-labelledby="comanda-titulo-{{ $comanda->id }}"
            >

                <div class="comanda-contenido">

                    <button type="button" class="comanda-cerrar" data-cerrar-modal aria-label="Cerrar ventana">
                        ×
                    </button>

                    <div class="comanda-header">

                        <span class="badge">COMANDA #{{ $comanda->id }}</span>

                        <h2 id="comanda-titulo-{{ $comanda->id }}">
                            Mesa {{ $mesa->numero }}
                        </h2>

                        <p>
                            Abierta el {{ $comanda->fecha->format('d/m/Y') }} a las {{ $comanda->fecha->format('H:i') }}
                        </p>

                    </div>


                    @if ($esLaComandaActual && $errors->any())
                        <div class="comanda-errores" role="alert">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif


                    <div class="comanda-resumen">

                        <div>
                            <span>Mozo</span>
                            <strong>{{ $comanda->mozo->name ?? '—' }}</strong>
                        </div>

                        <div>
                            <span>Personas</span>
                            <strong>{{ $mesa->cantidad_personas }}</strong>
                        </div>

                        <div>
                            <span>Estado</span>
                            <strong>{{ $comanda->estado_texto }}</strong>
                        </div>

                    </div>


                    {{-- Productos cargados: cambiar cantidad y quitar (PB-12) --}}
                    <div class="comanda-productos">

                        <h3>Productos</h3>

                        @forelse ($comanda->detalles as $detalle)

                            <div class="comanda-linea">

                                <div>
                                    <div class="nombre">{{ $detalle->cantidad }} × {{ $detalle->producto->nombre }}</div>
                                    <div class="precio">{{ $pesos($detalle->precio_unitario) }} c/u</div>
                                </div>

                                <div class="importe">{{ $pesos($detalle->total) }}</div>

                                <div class="comanda-linea-acciones">

                                    <form method="POST" action="{{ route('comandas.productos.update', [$comanda, $detalle]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="cantidad-detalle-{{ $detalle->id }}">
                                            Cantidad de {{ $detalle->producto->nombre }}
                                        </label>
                                        <input
                                            id="cantidad-detalle-{{ $detalle->id }}"
                                            type="number"
                                            name="cantidad"
                                            value="{{ $detalle->cantidad }}"
                                            min="1"
                                            max="99"
                                            required
                                        >
                                        <button type="submit" class="btn-chico">Actualizar</button>
                                    </form>

                                    <form method="POST" action="{{ route('comandas.productos.destroy', [$comanda, $detalle]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-chico peligro">Quitar</button>
                                    </form>

                                </div>

                            </div>

                        @empty

                            <div class="sin-productos">
                                No hay productos cargados en esta comanda.
                            </div>

                        @endforelse

                    </div>


                    {{-- Agregar productos y cantidades (PB-11) --}}
                    @if ($productos->isNotEmpty())

                        <form
                            method="POST"
                            action="{{ route('comandas.productos.store', $comanda) }}"
                            class="comanda-agregar"
                        >
                            @csrf

                            <div>
                                <label for="producto-{{ $comanda->id }}">Producto</label>
                                                               <select id="producto-{{ $comanda->id }}" name="producto_id" required>
                                    <option value="" disabled @selected(! ($esLaComandaActual && old('producto_id')))>Elegí un producto</option>
                                    @foreach ($productos as $categoria => $lista)
                                        <optgroup label="{{ \App\Models\Producto::CATEGORIAS[$categoria] ?? ucfirst($categoria) }}">
                                            @foreach ($lista as $producto)
                                                <option
                                                    value="{{ $producto->id }}"
                                                    @selected($esLaComandaActual && old('producto_id') == $producto->id)
                                                >
                                                    {{ $producto->nombre }} · {{ $pesos($producto->precio) }}{{ $producto->llevaStock() ? ' · quedan ' . $producto->stock : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select> 
                            </div>

                            <div>
                                <label for="cantidad-{{ $comanda->id }}">Cantidad</label>
                                <input
                                    id="cantidad-{{ $comanda->id }}"
                                    type="number"
                                    name="cantidad"
                                    value="{{ $esLaComandaActual ? old('cantidad', 1) : 1 }}"
                                    min="1"
                                    max="99"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn-principal">Agregar</button>

                        </form>

                    @else

                        <p class="sin-productos" style="margin-top: 20px;">
                            No hay productos activos en el catálogo.
                        </p>

                    @endif


                    {{-- Total y cierre (PB-14) --}}
                    <div class="comanda-subtotales">

                        <div>
                            <span>Subtotal</span>
                            <span>{{ $pesos($comanda->subtotal) }}</span>
                        </div>

                        @if ((float) $comanda->descuento > 0)
                            <div>
                                <span>Descuento</span>
                                <span>− {{ $pesos($comanda->descuento) }}</span>
                            </div>
                        @endif

                    </div>

                    <div class="comanda-total">

                        <span>Total</span>

                        <strong>
                            {{ $pesos($comanda->total) }}
                        </strong>

                    </div>

                    <form
                        method="POST"
                        action="{{ route('comandas.cerrar', $comanda) }}"
                        class="comanda-cierre"
                        id="form-cerrar-comanda-{{ $comanda->id }}"
                        data-mesa-numero="{{ $mesa->numero }}"
                    >
                        @csrf
                        <button type="submit" class="btn-principal btn-atencion">
                            ✓ &nbsp; Cerrar comanda
                        </button>
                    </form>

                </div>

            </div>

        @endif

    @endforeach


<script>
document.addEventListener('DOMContentLoaded', function () {

    const mesas = document.querySelectorAll('.mesa');
    const textoSeleccion = document.getElementById('texto-seleccion');

    let mesaSeleccionada = null;

    const escaparTexto = (valor) => {
        const elemento = document.createElement('span');
        elemento.textContent = valor;
        return elemento.innerHTML;
    };

    // =========================
    // SELECCIÓN DE MESA
    // =========================

    mesas.forEach(function (mesa) {

        mesa.addEventListener('click', function () {

            mesas.forEach(function (otraMesa) {
                otraMesa.classList.remove('seleccionada');
            });

            mesa.classList.add('seleccionada');
            mesaSeleccionada = mesa;

            const numero = mesa.dataset.mesaNumero || '';
            const mozo = mesa.dataset.mesaMozo || 'Sin asignar';
            const estado = mesa.dataset.mesaEstado || '';
            const personas = mesa.dataset.mesaPersonas || '0';

            if (textoSeleccion) {
                textoSeleccion.innerHTML = `
                    Mesa seleccionada:
                    <strong>${escaparTexto(numero)}</strong>
                    <span style="color:#776c63;"> · </span>
                    Mozo:
                    <strong>${escaparTexto(mozo)}</strong>
                    <span style="color:#776c63;"> · </span>
                    Estado:
                    <strong>${escaparTexto(estado)}</strong>
                    <span style="color:#776c63;"> · </span>
                    Personas:
                    <strong>${escaparTexto(personas)}</strong>
                `;
            }

        });

    });

    // =========================
    // VENTANAS DE COMANDA
    // =========================

    let elementoAntesDeAbrir = null;

    function abrirComanda(id) {
        const modal = document.getElementById('modal-comanda-' + id);

        if (!modal) {
            return false;
        }

        elementoAntesDeAbrir = document.activeElement;
        modal.classList.add('activo');
        modal.querySelector('[data-cerrar-modal]').focus();

        return true;
    }

    function cerrarComanda(modal) {
        modal.classList.remove('activo');

        // Quita ?comanda=ID de la dirección, para que al recargar no se vuelva a abrir.
        const url = new URL(window.location.href);
        if (url.searchParams.has('comanda')) {
            url.searchParams.delete('comanda');
            window.history.replaceState({}, '', url);
        }

        if (elementoAntesDeAbrir) {
            elementoAntesDeAbrir.focus();
        }
    }

    // Botón "Ver comanda" de cada tarjeta.
    document.querySelectorAll('[data-abrir-comanda]').forEach(function (boton) {
        boton.addEventListener('click', function (evento) {
            evento.stopPropagation();
            abrirComanda(boton.dataset.abrirComanda);
        });
    });

    // Cerrar con la ×, haciendo clic afuera o con Escape.
    document.querySelectorAll('.modal-comanda').forEach(function (modal) {

        modal.querySelector('[data-cerrar-modal]').addEventListener('click', function () {
            cerrarComanda(modal);
        });

        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) {
                cerrarComanda(modal);
            }
        });

    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            document.querySelectorAll('.modal-comanda.activo').forEach(cerrarComanda);
        }
    });

    // Confirmación antes de cerrar una comanda.
    document.querySelectorAll('form[id^="form-cerrar-comanda-"]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            const numero = form.dataset.mesaNumero;
            if (!confirm('¿Cerrar la comanda de la mesa ' + numero + '? La mesa va a quedar libre.')) {
                evento.preventDefault();
            }
        });
    });

    // Barra inferior: trabaja sobre la mesa seleccionada.
    function comandaDeLaMesaSeleccionada() {
        if (!mesaSeleccionada) {
            alert('Primero seleccioná una mesa.');
            return null;
        }

        const id = mesaSeleccionada.dataset.comandaId;

        if (!id) {
            alert('Esta mesa no tiene una comanda abierta.');
            return null;
        }

        if (!document.getElementById('modal-comanda-' + id)) {
            alert('La comanda de esta mesa no está a tu cargo.');
            return null;
        }

        return id;
    }

    const btnVerComanda = document.getElementById('btn-ver-comanda');
    if (btnVerComanda) {
        btnVerComanda.addEventListener('click', function () {
            const id = comandaDeLaMesaSeleccionada();
            if (id) {
                abrirComanda(id);
            }
        });
    }

    const btnCerrarComanda = document.getElementById('btn-cerrar-comanda');
    if (btnCerrarComanda) {
        btnCerrarComanda.addEventListener('click', function () {
            const id = comandaDeLaMesaSeleccionada();
            if (id) {
                document.getElementById('form-cerrar-comanda-' + id).requestSubmit();
            }
        });
    }

    // Después de agregar, modificar o quitar un producto, la ventana vuelve a abrirse sola.
    const comandaParaAbrir = @json($comandaParaAbrir);
    if (comandaParaAbrir) {
        abrirComanda(comandaParaAbrir);
    }

});
</script>

</body>

</html>
