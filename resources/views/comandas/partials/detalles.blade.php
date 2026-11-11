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
                <option
                 value=""
                 disabled
                 @selected(! ($esLaComandaActual && old('producto_id')))
                 >
                  Elegí un producto
                </option>
                @foreach ($productos as $categoria => $lista)
                <optgroup label="{{ \App\Models\Producto::CATEGORIAS[$categoria] ?? ucfirst($categoria) }}">
                    @foreach ($lista as $producto)
                    @php
                    $sinStock = $producto->llevaStock()
                    && (int) $producto->stock <= 0;
                    @endphp
                    <option
                    value="{{ $producto->id }}"
                    @disabled($sinStock)
                    @selected(
                            ! $sinStock
                             && $esLaComandaActual
                              && old('producto_id') == $producto->id
                             )
                             >
                    {{ $producto->nombre }} · {{ $pesos($producto->precio) }}{{ $sinStock ? ' - Sin stock' : ($producto->llevaStock() ? ' · quedan ' . $producto->stock : '') }}
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

        <button type="submit" class="btn-principal">
            <x-icono nombre="mas" tamano="16" />
            Agregar
        </button>

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