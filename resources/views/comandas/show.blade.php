@extends('layouts.puntobar')

@section('title', 'Comanda mesa ' . $comanda->mesa->numero)

@section('content')
<style>
    .comanda { display: grid; gap: 16px; }
    .comanda .card { background: #1a1815; border: 1px solid #403628; border-radius: 16px; padding: 22px; }
    .comanda h1 { margin: 4px 0 10px; font-size: 26px; }
    .comanda .badge { color: #e9a365; font-size: 13px; letter-spacing: 1px; text-transform: uppercase; margin: 0; }
    .comanda .datos { display: flex; flex-wrap: wrap; gap: 6px 18px; color: #b8aea0; font-size: 14px; }
    .comanda .datos strong { color: #eee9e1; font-weight: 600; }
    .comanda .pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .comanda .pill.abierta { background: #32cf6a; color: #102117; }
    .comanda .pill.cerrada { background: #5f5a52; color: #eee9e1; }
    .comanda .aviso { padding: 12px 14px; border-radius: 10px; font-size: 14px; }
    .comanda .aviso.ok { border: 1px solid #3f7a4f; background: #1d2b20; color: #c9f0d3; }
    .comanda .aviso.error { border: 1px solid #d88a80; background: #382321; color: #ffd2cb; }
    .comanda .aviso p { margin: 2px 0; color: inherit; }
    .comanda h2 { font-size: 17px; margin: 0 0 12px; }
    .comanda .linea { display: grid; grid-template-columns: 1fr auto; gap: 6px 12px; padding: 12px 0; border-bottom: 1px solid #2e261d; }
    .comanda .linea:last-child { border-bottom: none; }
    .comanda .linea .nombre { color: #eee9e1; }
    .comanda .linea .precio { color: #8f857a; font-size: 13px; }
    .comanda .linea .subtotal { text-align: right; color: #eee9e1; font-weight: 600; }
    .comanda .acciones-linea { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .comanda .acciones-linea form { display: flex; gap: 8px; align-items: center; margin: 0; }
    .comanda input[type="number"] { width: 76px; padding: 8px; margin: 0; }
    .comanda select { width: 100%; padding: 12px; background: #12110f; border: 1px solid #635442; border-radius: 10px; color: #eee9e1; font-size: 15px; }
    .comanda button { width: auto; margin: 0; padding: 8px 14px; font-size: 14px; }
    .comanda .btn-secundario { background: transparent; border: 1px solid #635442; color: #eee9e1; font-weight: 400; }
    .comanda .btn-secundario:hover { border-color: #e9a365; color: #e9a365; background: transparent; }
    .comanda .btn-peligro { background: transparent; border: 1px solid #8c4a42; color: #ffb3a8; font-weight: 400; }
    .comanda .btn-peligro:hover { background: #382321; }
    .comanda .btn-ancho { width: 100%; padding: 14px; font-size: 16px; margin-top: 14px; }
    .comanda .agregar { display: grid; grid-template-columns: 1fr 90px; gap: 10px; align-items: end; }
    .comanda .agregar label { display: block; margin: 0 0 6px; font-size: 14px; color: #b8aea0; }
    .comanda .agregar input[type="number"] { width: 100%; padding: 12px; }
    .comanda .totales { display: grid; gap: 6px; font-size: 15px; }
    .comanda .totales div { display: flex; justify-content: space-between; color: #b8aea0; }
    .comanda .totales .total { color: #eee9e1; font-size: 22px; font-weight: 700; margin-top: 6px; }
    .comanda .totales .total span:last-child { color: #e9a365; }
    .comanda .vacio { color: #8f857a; margin: 0; }
    .comanda .volver { text-align: center; }
    .comanda :focus-visible { outline: 3px solid #e9a365; outline-offset: 2px; }
    .comanda .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
</style>

@php
    $abierta = $comanda->estaAbierta();
    $pesos = fn ($valor) => '$' . number_format((float) $valor, 2, ',', '.');
@endphp

<div class="comanda">

    {{-- Encabezado: mesa, mozo, fecha/hora y estado (PB-10) --}}
    <section class="card">
        <p class="badge">Comanda #{{ $comanda->id }}</p>
        <h1>Mesa {{ $comanda->mesa->numero }}</h1>
        <div class="datos">
            <span>Mozo: <strong>{{ $comanda->mozo->name }}</strong></span>
            <span>Abierta: <strong>{{ $comanda->fecha->format('d/m/Y H:i') }}</strong></span>
            <span>Estado: <span class="pill {{ $comanda->estado }}">{{ $comanda->estado_texto }}</span></span>
            @if ($comanda->cerrada_en)
                <span>Cerrada: <strong>{{ $comanda->cerrada_en->format('d/m/Y H:i') }}</strong></span>
            @endif
        </div>
    </section>

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

    {{-- Productos cargados: cambiar cantidades y quitar (PB-12) --}}
    <section class="card" aria-labelledby="titulo-productos">
        <h2 id="titulo-productos">Productos</h2>

        @forelse ($comanda->detalles as $detalle)
            <div class="linea">
                <div>
                    <div class="nombre">{{ $detalle->cantidad }} × {{ $detalle->producto->nombre }}</div>
                    <div class="precio">{{ $pesos($detalle->precio_unitario) }} c/u</div>
                </div>
                <div class="subtotal">{{ $pesos($detalle->total) }}</div>

                @if ($abierta)
                    <div class="acciones-linea">
                        <form method="POST" action="{{ route('comandas.productos.update', [$comanda, $detalle]) }}">
                            @csrf
                            @method('PATCH')
                            <label class="sr-only" for="cantidad-{{ $detalle->id }}">Cantidad de {{ $detalle->producto->nombre }}</label>
                            <input id="cantidad-{{ $detalle->id }}" type="number" name="cantidad"
                                   value="{{ $detalle->cantidad }}" min="1" max="99" required>
                            <button type="submit" class="btn-secundario">Actualizar</button>
                        </form>

                        <form method="POST" action="{{ route('comandas.productos.destroy', [$comanda, $detalle]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-peligro">Quitar</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <p class="vacio">Todavía no hay productos cargados.</p>
        @endforelse
    </section>

    {{-- Agregar productos y cantidades (PB-11) --}}
    @if ($abierta)
        <section class="card" aria-labelledby="titulo-agregar">
            <h2 id="titulo-agregar">Agregar producto</h2>

            @if ($productos->isEmpty())
                <p class="vacio">No hay productos activos en el catálogo.</p>
            @else
                <form method="POST" action="{{ route('comandas.productos.store', $comanda) }}" class="agregar">
                    @csrf
                    <div>
                        <label for="producto_id">Producto</label>
                        <select id="producto_id" name="producto_id" required>
                            <option value="" disabled @selected(! old('producto_id'))>Elegí un producto</option>
                            @foreach ($productos as $categoria => $lista)
                                <optgroup label="{{ \App\Models\Producto::CATEGORIAS[$categoria] ?? ucfirst($categoria) }}">
                                    @foreach ($lista as $producto)
                                        <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>
                                            {{ $producto->nombre }} · {{ $pesos($producto->precio) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cantidad">Cantidad</label>
                        <input id="cantidad" type="number" name="cantidad" value="{{ old('cantidad', 1) }}" min="1" max="99" required>
                    </div>
                    <button type="submit" class="btn-ancho" style="grid-column: 1 / -1;">Agregar</button>
                </form>
            @endif
        </section>
    @endif

    {{-- Totales y cierre (PB-14) --}}
    <section class="card" aria-labelledby="titulo-total">
        <h2 id="titulo-total">Total</h2>
        <div class="totales">
            <div><span>Subtotal</span><span>{{ $pesos($comanda->subtotal) }}</span></div>
            @if ((float) $comanda->descuento > 0)
                <div><span>Descuento</span><span>− {{ $pesos($comanda->descuento) }}</span></div>
            @endif
            <div class="total"><span>Total</span><span>{{ $pesos($comanda->total) }}</span></div>
        </div>

        @if ($abierta)
            <form method="POST" action="{{ route('comandas.cerrar', $comanda) }}"
                  onsubmit="return confirm('¿Cerrar la comanda de la mesa {{ $comanda->mesa->numero }}? La mesa va a quedar libre.');">
                @csrf
                <button type="submit" class="btn-ancho">Cerrar comanda</button>
            </form>
        @endif
    </section>

    <p class="volver"><a href="{{ route('mesas') }}">← Volver a mesas</a></p>

</div>
@endsection
