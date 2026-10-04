<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestionar Mesas - PUNTO BAR</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #120d0a;
            color: #f1e9df;
            min-height: 100vh;
        }

        .navbar {
            height: 75px;
            background: #1b1410;
            border-bottom: 1px solid #4a3020;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 40px;
        }

        .logo {
            height: 48px;
            width: auto;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-volver,
        .btn-salir {
            padding: 9px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-volver {
            background: transparent;
            border: 1px solid #4a3020;
            color: #cfc3b8;
        }

        .btn-volver:hover {
            border-color: #8b572f;
            color: #e2a15c;
        }

        .btn-salir {
            background: transparent;
            border: 1px solid #704522;
            color: #e2a15c;
        }

        .btn-salir:hover {
            background: #704522;
            color: white;
        }

        .contenido {
            max-width: 1400px;
            margin: auto;
            padding: 40px;
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .encabezado h1 {
            font-size: 30px;
            color: #f4eee8;
            margin-bottom: 8px;
        }

        .encabezado p {
            color: #9f9389;
            font-size: 15px;
        }

        .btn-nueva {
            background: #8b572f;
            color: white;
            border: none;
            padding: 11px 18px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-nueva:hover {
            background: #a66a3c;
        }

        .mensaje {
            background: rgba(77, 154, 98, 0.12);
            border: 1px solid #356b45;
            color: #79bd8b;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 25px;
        }

        .tabla-contenedor {
            background: #1b1410;
            border: 1px solid #3e2a1e;
            border-radius: 15px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #211812;
            color: #a99b90;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
            padding: 16px 20px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 17px 20px;
            border-top: 1px solid #33231a;
            color: #ddd2c8;
            font-size: 14px;
        }

        tr:hover td {
            background: #211812;
        }

        .numero {
            color: #f1e4d8;
            font-weight: bold;
            font-size: 16px;
        }

        .estado {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: capitalize;
        }

        .libre {
            color: #6da5d6;
            background: rgba(61, 130, 196, 0.12);
        }

        .espera {
            color: #d5aa32;
            background: rgba(213, 170, 50, 0.12);
        }

        .atendida {
            color: #63a977;
            background: rgba(77, 154, 98, 0.12);
        }

        .alerta {
            color: #d15b58;
            background: rgba(185, 74, 72, 0.12);
        }

        .acciones {
            display: flex;
            gap: 8px;
        }

        .btn-editar,
        .btn-eliminar {
            padding: 7px 11px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-editar {
            background: transparent;
            border: 1px solid #4a3020;
            color: #d6a16d;
            text-decoration: none;
        }

        .btn-editar:hover {
            border-color: #a66a3c;
        }

        .btn-eliminar {
            background: transparent;
            border: 1px solid #63312f;
            color: #d87874;
        }

        .btn-eliminar:hover {
            background: #63312f;
            color: white;
        }

        .sin-mesas {
            padding: 50px;
            text-align: center;
            color: #9f9389;
        }

        @media (max-width: 750px) {

            .navbar {
                padding: 0 20px;
            }

            .contenido {
                padding: 25px 20px;
            }

            .encabezado {
                align-items: flex-start;
                flex-direction: column;
            }

            .tabla-contenedor {
                overflow-x: auto;
            }

            table {
                min-width: 700px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">

        <img
            src="{{ asset('img/punto-bar-logo-transparente.png') }}"
            alt="PUNTO BAR"
            class="logo"
        >

        <div class="navbar-right">

            <a href="{{ route('admin.dashboard') }}" class="btn-volver">
                Panel
            </a>

            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf

                <button type="submit" class="btn-salir">
                    Cerrar sesión
                </button>
            </form>

        </div>

    </nav>


    <main class="contenido">

        <div class="encabezado">

            <div>
                <h1>Gestionar mesas</h1>

                <p>
                    Crear, editar y eliminar las mesas del bar.
                </p>
            </div>

            <a href="{{ route('admin.mesas.create') }}" class="btn-nueva">
                + Nueva mesa
            </a>

        </div>


        @if (session('success'))

            <div class="mensaje">
                {{ session('success') }}
            </div>

        @endif


        <div class="tabla-contenedor">

            @if ($mesas->count())

                <table>

                    <thead>
                        <tr>
                            <th>Mesa</th>
                            <th>Estado</th>
                            <th>Mozo asignado</th>
                            <th>Personas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($mesas as $mesa)

                            @php
                                $estadoClase = match ($mesa->estado) {
                                    'libre' => 'libre',
                                    'espera', 'esperando_pedido' => 'espera',
                                    'atendida' => 'atendida',
                                    'alerta' => 'alerta',
                                    default => 'libre',
                                };
                            @endphp

                            <tr>

                                <td class="numero">
                                    Mesa {{ $mesa->numero }}
                                </td>

                                <td>

                                    <span class="estado {{ $estadoClase }}">
                                        {{ str_replace('_', ' ', $mesa->estado) }}
                                    </span>

                                </td>

                                <td>
                                    {{ $mesa->mozo?->name ?? 'Sin asignar' }}
                                </td>

                                <td>
                                    {{ $mesa->cantidad_personas }}
                                </td>

                                <td>

                                    <div class="acciones">

                                        <a
                                            href="{{ route('admin.mesas.edit', $mesa) }}"
                                            class="btn-editar"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            action="{{ route('admin.mesas.destroy', $mesa) }}"
                                            method="POST"
                                            onsubmit="return confirm('¿Seguro que querés eliminar esta mesa?');"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn-eliminar"
                                            >
                                                Eliminar
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            @else

                <div class="sin-mesas">
                    No hay mesas registradas.
                </div>

            @endif

        </div>

    </main>

</body>
</html>