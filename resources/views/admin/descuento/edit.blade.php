@php
    $pesos = function ($valor) {
        $valor = (float) $valor;
        $decimales = fmod($valor, 1) == 0 ? 0 : 2;

        return '$' . number_format($valor, $decimales, ',', '.');
    };

    $porcentajeTexto = rtrim(rtrim(number_format((float) $configuracion->porcentaje, 2, ',', '.'), '0'), ',');
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descuento por consumo - PUNTO BAR</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #12100d;
            color: #f1e9df;
            min-height: 100vh;
        }

        /* ===== Barra superior (misma estética que mesas) ===== */

        .topbar {
            min-height: 92px;
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 0 40px;
            background: #15130f;
            border-bottom: 1px solid #34271c;
        }

        .brand { display: flex; align-items: center; min-width: 360px; }

        .logo {
            width: 125px;
            height: auto;
            padding-right: 25px;
            margin-right: 25px;
            border-right: 1px solid #403126;
        }

        .titulo-sistema h1 { font-size: 28px; color: #f5eee7; }
        .titulo-sistema p { margin-top: 5px; color: #9e948b; font-size: 15px; }

        .navegacion { display: flex; gap: 8px; flex: 1; justify-content: center; }

        .nav-item {
            padding: 14px 20px;
            border-radius: 9px;
            color: #c7bdb4;
            text-decoration: none;
            font-size: 16px;
            border: 1px solid transparent;
        }

        .nav-item:hover { color: #f0a15f; border-color: #4a3424; }
        .nav-item.activo { color: #f39b55; background: #211a14; border-color: #543a26; }

        .usuario {
            padding-left: 30px;
            border-left: 1px solid #403126;
            color: #e3d9d0;
            font-size: 14px;
            font-weight: 700;
        }

        /* ===== Contenido ===== */

        .contenido {
            max-width: 680px;
            margin: auto;
            padding: 32px 20px 48px;
            display: grid;
            gap: 20px;
        }

        .tarjeta {
            padding: 26px;
            background: #1b1915;
            border: 1px solid #3d3024;
            border-radius: 14px;
        }

        .tarjeta h2 { font-size: 19px; margin-bottom: 8px; }
        .tarjeta > p { color: #a99d94; line-height: 1.55; font-size: 15px; }

        .estado-actual {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .pill {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .pill.activo { background: #32cf6a; color: #102117; }
        .pill.inactivo { background: #3a332c; color: #d8cec5; }

        .resumen-regla {
            font-size: 18px;
            line-height: 1.5;
            color: #f1e9df;
        }

        .resumen-regla strong { color: #f39b55; }

        .aviso {
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 14px;
        }

        .aviso.ok { border: 1px solid #3f7a4f; background: #1d2b20; color: #c9f0d3; }
        .aviso.error { border: 1px solid #d88a80; background: #382321; color: #ffd2cb; }
        .aviso p + p { margin-top: 4px; }

        form { display: grid; gap: 18px; margin-top: 18px; }

        .campos { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .campo label { display: block; margin-bottom: 7px; color: #cfc5bc; font-size: 14px; }

        .con-prefijo { position: relative; }

        .con-prefijo span {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #8f837a;
            font-size: 15px;
            pointer-events: none;
        }

        .con-prefijo .antes { left: 13px; }
        .con-prefijo .despues { right: 13px; }

        .campo input[type="number"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 9px;
            border: 1px solid #5d4834;
            background: #12100d;
            color: #f1e9df;
            font-size: 16px;
        }

        .con-prefijo .monto { padding-left: 28px; }
        .con-prefijo .porc { padding-right: 32px; }

        .ayuda { margin-top: 6px; color: #8f837a; font-size: 13px; }

        .interruptor {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid #3d3024;
            border-radius: 10px;
            cursor: pointer;
        }

        .interruptor input { width: 20px; height: 20px; accent-color: #f39b55; cursor: pointer; }
        .interruptor span { font-size: 15px; }

        .btn-principal {
            padding: 14px 20px;
            border-radius: 9px;
            border: 1px solid #f09b58;
            background: #f09b58;
            color: #1c1510;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-principal:hover { background: #ffad6a; }

        .nota { color: #8f837a; font-size: 13px; line-height: 1.5; }

        .btn-volver {
            justify-self: start; 
            color: #c7bdb4;
            text-decoration: none;
            font-size: 15px;
        }

        .btn-volver:hover { 
            color: #f0a15f;
         }


        :focus-visible { outline: 3px solid #e2a15c; outline-offset: 2px; }

        @media (max-width: 900px) {
            .topbar { flex-wrap: wrap; padding: 18px; }
            .brand { min-width: 100%; }
            .navegacion { justify-content: flex-start; }
            .usuario { border-left: none; padding-left: 0; margin-left: auto; }
        }

        @media (max-width: 520px) {
            .campos { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <img src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="PUNTO BAR" class="logo">

            <div class="titulo-sistema">
                <h1>Descuento por consumo</h1>
                <p>Configuración del administrador</p>
            </div>
        </div>

        <nav class="navegacion" aria-label="Menú principal">
            <a href="{{ route('mesas') }}" class="nav-item">Mesas</a>
            <a href="{{ route('admin.comandas.index') }}" class="nav-item">Comandas</a>
            <a href="{{ route('admin.descuento.edit') }}" class="nav-item activo" aria-current="page">Descuento</a>
        </nav>

        <div class="usuario">Vista de administrador</div>

    </header>

    <main class="contenido">
        <a href="{{ route('mesas') }}" class="btn-volver"> ← Volver a mesas</a>

        @if (session('success'))
            <div class="aviso ok" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="aviso error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Cómo está funcionando hoy --}}
        <section class="tarjeta" aria-labelledby="titulo-actual">
            <div class="estado-actual">
                <h2 id="titulo-actual">Situación actual</h2>
                <span class="pill {{ $configuracion->activo ? 'activo' : 'inactivo' }}">
                    {{ $configuracion->activo ? 'Activo' : 'Desactivado' }}
                </span>
            </div>

            @if ($configuracion->activo)
                <p class="resumen-regla">
                    Las comandas que superen <strong>{{ $pesos($configuracion->monto_tope) }}</strong>
                    tienen un <strong>{{ $porcentajeTexto }} %</strong> de descuento.
                </p>
            @else
                <p class="resumen-regla">Hoy no se aplica ningún descuento.</p>
            @endif
        </section>

        {{-- Formulario --}}
        <section class="tarjeta" aria-labelledby="titulo-config">
            <h2 id="titulo-config">Configurar</h2>
            <p>
                Cuando el subtotal de una comanda supera el monto tope, el sistema descuenta el porcentaje
                sobre el total. Si después se quitan productos y el subtotal vuelve a quedar por debajo
                del tope, el descuento se quita solo.
            </p>

            <form method="POST" action="{{ route('admin.descuento.update') }}">
                @csrf
                @method('PUT')

                <div class="campos">
                    <div class="campo">
                        <label for="monto_tope">Monto tope</label>
                        <div class="con-prefijo">
                            <span class="antes" aria-hidden="true">$</span>
                            <input
                                id="monto_tope"
                                class="monto"
                                type="number"
                                name="monto_tope"
                                min="0"
                                step="0.01"
                                value="{{ old('monto_tope', (float) $configuracion->monto_tope) }}"
                                required
                            >
                        </div>
                        <p class="ayuda">El descuento se aplica cuando el subtotal lo supera.</p>
                    </div>

                    <div class="campo">
                        <label for="porcentaje">Porcentaje de descuento</label>
                        <div class="con-prefijo">
                            <input
                                id="porcentaje"
                                class="porc"
                                type="number"
                                name="porcentaje"
                                min="0.01"
                                max="100"
                                step="0.01"
                                value="{{ old('porcentaje', (float) $configuracion->porcentaje ?: '') }}"
                                required
                            >
                            <span class="despues" aria-hidden="true">%</span>
                        </div>
                        <p class="ayuda">Por ejemplo, 10 para un 10 %.</p>
                    </div>
                </div>

                <label class="interruptor">
                    <input type="hidden" name="activo" value="0">
                    <input
                        type="checkbox"
                        name="activo"
                        value="1"
                        @checked(old('activo', $configuracion->activo))
                    >
                    <span>Aplicar el descuento</span>
                </label>

                <button type="submit" class="btn-principal">Guardar configuración</button>

                <p class="nota">
                    Al guardar, se recalculan las {{ $comandasAbiertas }}
                    {{ $comandasAbiertas === 1 ? 'comanda abierta' : 'comandas abiertas' }}.
                    Las comandas cerradas conservan el descuento con el que se cerraron.
                </p>
            </form>
        </section>

    </main>

</body>

</html>
