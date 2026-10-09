@php
    $pesos = function ($valor) {
        $valor = (float) $valor;
        $decimales = fmod($valor, 1) == 0 ? 0 : 2;

        return '$' . number_format($valor, $decimales, ',', '.');
    };

    $hayFiltros = ! empty(array_filter($filtros ?? []));
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comandas - PUNTO BAR</title>

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

        .contenido { max-width: 1300px; margin: auto; padding: 28px 30px 40px; }

        .filtros {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 14px;
            margin-bottom: 22px;
            padding: 18px 20px;
            background: #1b1915;
            border: 1px solid #3d3024;
            border-radius: 13px;
        }

        .filtro { display: flex; flex-direction: column; gap: 6px; }
        .filtro label { color: #9e948b; font-size: 13px; }

        .filtro select,
        .filtro input {
            min-width: 170px;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #5d4834;
            background: #12100d;
            color: #f1e9df;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid #5d4834;
            background: transparent;
            color: #ded3ca;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover { border-color: #d27a40; color: #f0a060; }
        .btn-principal { background: #f09b58; border-color: #f09b58; color: #1c1510; font-weight: 700; }
        .btn-principal:hover { background: #ffad6a; color: #1c1510; }

        .resumen { margin: 0 4px 14px; color: #9e948b; font-size: 14px; }

        .tabla-contenedor {
            overflow-x: auto;
            background: #1b1915;
            border: 1px solid #3d3024;
            border-radius: 13px;
        }

        table { width: 100%; border-collapse: collapse; font-size: 15px; }

        th {
            padding: 16px 18px;
            text-align: left;
            color: #9e948b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            border-bottom: 1px solid #3d3024;
            white-space: nowrap;
        }

        td { padding: 15px 18px; border-bottom: 1px solid #2c241b; white-space: nowrap; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #211d19; }

        .numero { color: #9e948b; }
        .total { font-weight: 700; text-align: right; }
        th.total { text-align: right; }
        .sin-dato { color: #6f665e; }

        .estado {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .estado.abierta { background: #32cf6a; color: #102117; }
        .estado.cerrada { background: #3a332c; color: #d8cec5; }

        .vacio { padding: 60px 20px; text-align: center; color: #9e948b; }

        .paginacion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 18px;
            color: #9e948b;
            font-size: 14px;
        }

        .btn[aria-disabled="true"] { opacity: .4; pointer-events: none; }

        :focus-visible { outline: 3px solid #e2a15c; outline-offset: 2px; }

        @media (max-width: 900px) {
            .topbar { flex-wrap: wrap; padding: 18px; }
            .brand { min-width: 100%; }
            .navegacion { justify-content: flex-start; }
            .usuario { border-left: none; padding-left: 0; margin-left: auto; }
            .contenido { padding: 20px 15px; }
        }
    </style>
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <img src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="PUNTO BAR" class="logo">

            <div class="titulo-sistema">
                <h1>Comandas</h1>
                <p>Listado e historial</p>
            </div>
        </div>

        <nav class="navegacion" aria-label="Menú principal">
            <a href="{{ route('mesas') }}" class="nav-item">Mesas</a>
            <a href="{{ route('admin.comandas.index') }}" class="nav-item activo" aria-current="page">Comandas</a>
        </nav>

        <div class="usuario">Vista de administrador</div>

    </header>

    <main class="contenido">

        {{-- Filtros: estado, fecha y mozo --}}
        <form method="GET" action="{{ route('admin.comandas.index') }}" class="filtros" aria-label="Filtrar comandas">

            <div class="filtro">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todas</option>
                    @foreach (\App\Models\Comanda::ESTADOS as $valor => $texto)
                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $texto }}s</option>
                    @endforeach
                </select>
            </div>

            <div class="filtro">
                <label for="fecha">Fecha</label>
                <input id="fecha" type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}">
            </div>

            <div class="filtro">
                <label for="mozo_id">Mozo</label>
                <select id="mozo_id" name="mozo_id">
                    <option value="">Todos</option>
                    @foreach ($mozos as $mozo)
                        <option value="{{ $mozo->id }}" @selected((int) ($filtros['mozo_id'] ?? 0) === $mozo->id)>{{ $mozo->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-principal">Filtrar</button>

            @if ($hayFiltros)
                <a href="{{ route('admin.comandas.index') }}" class="btn">Quitar filtros</a>
            @endif

        </form>

        <p class="resumen" role="status">
            {{ $comandas->total() }} {{ $comandas->total() === 1 ? 'comanda' : 'comandas' }}
            {{ $hayFiltros ? 'con los filtros elegidos' : 'en total' }}
        </p>

        <div class="tabla-contenedor">

            @if ($comandas->isEmpty())

                <p class="vacio">
                    {{ $hayFiltros ? 'No hay comandas que coincidan con los filtros.' : 'Todavía no hay comandas registradas.' }}
                </p>

            @else

                <table>
                    <thead>
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Mesa</th>
                            <th scope="col">Mozo</th>
                            <th scope="col">Apertura</th>
                            <th scope="col">Cierre</th>
                            <th scope="col">Productos</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="total">Total</th>
                            <th scope="col"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comandas as $comanda)
                            <tr>
                                <td class="numero">#{{ $comanda->id }}</td>
                                <td>Mesa {{ $comanda->mesa->numero }}</td>
                                <td>{{ $comanda->mozo->name ?? '—' }}</td>
                                <td>{{ $comanda->fecha->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if ($comanda->cerrada_en)
                                        {{ $comanda->cerrada_en->format('d/m/Y H:i') }}
                                    @else
                                        <span class="sin-dato">—</span>
                                    @endif
                                </td>
                                <td>{{ (int) $comanda->detalles_sum_cantidad }}</td>
                                <td><span class="estado {{ $comanda->estado }}">{{ $comanda->estado_texto }}</span></td>
                                <td class="total">{{ $pesos($comanda->total) }}</td>
                                <td>
                                    <a href="{{ route('comandas.show', $comanda) }}" class="btn">
                                        Ver<span class="sr-only"> comanda #{{ $comanda->id }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            @endif

        </div>

        @if ($comandas->lastPage() > 1)
            <nav class="paginacion" aria-label="Páginas">
                <a href="{{ $comandas->previousPageUrl() ?? '#' }}" class="btn" @if ($comandas->onFirstPage()) aria-disabled="true" @endif>← Anteriores</a>
                <span>Página {{ $comandas->currentPage() }} de {{ $comandas->lastPage() }}</span>
                <a href="{{ $comandas->nextPageUrl() ?? '#' }}" class="btn" @if (! $comandas->hasMorePages()) aria-disabled="true" @endif>Siguientes →</a>
                <a href="{{ route('admin.descuento.edit') }}" class="nav-item">Descuento</a>
            </nav>
        @endif

    </main>

    <style>
        .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    </style>

</body>

</html>
