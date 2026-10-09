@extends('layouts.puntobar')

@section('title', 'Productos')
@section('shell-class', 'pb-shell--productos')

@section('content')
    @php
        $categorias = \App\Models\Producto::CATEGORIAS;
        $tipos = \App\Models\Producto::TIPOS;

        // Si el servidor rechazó el formulario, se vuelve a mostrar con lo que se había escrito.
        $e = $errors->getBag('producto');
        $hayOld = old('_form') === 'producto';
        $editandoId = $hayOld ? old('_editando') : null;
        $f = [
            'nombre'    => $hayOld ? old('nombre', '') : '',
            'categoria' => $hayOld ? old('categoria', 'bebida') : 'bebida',
            'tipo'      => $hayOld ? old('tipo', 'unidad') : 'unidad',
            'stock'     => $hayOld ? old('stock', '') : '',
            'precio'    => $hayOld ? old('precio', '') : '',
            'activo'    => $hayOld ? (bool) old('activo') : true,
            'masiva'    => $hayOld ? (bool) old('permite_actualizacion_masiva') : true,
        ];
    @endphp

    <link rel="stylesheet" href="{{ asset('css/productos.css') }}">

    <div class="pp">
        <header class="pp-header">
            <div class="pp-brand">
                <img class="pp-logo" src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="Punto Bar">
                <div>
                    <h1>Productos</h1>
                    <p>Catálogo de bebidas, comidas y postres</p>
                </div>
            </div>

            <div class="pp-header-right">
                <nav class="pp-nav" aria-label="Secciones del administrador">
                    <a href="{{ route('mesas') }}">Mesas</a>
                    <a href="{{ route('admin.mesas.index') }}">Gestión de mesas</a>
                    <a href="{{ route('admin.productos.index') }}" aria-current="page">Productos</a>
                </nav>
                   <x-campana-notificaciones />
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="pp-link-btn">Cerrar sesión</button>
                </form>
            </div>
        </header>

        <div class="pp-toolbar">
            <nav class="pp-tabs" aria-label="Filtrar por categoría">
                <a href="{{ route('admin.productos.index') }}" @class(['pp-tab', 'is-active' => ! $filtro])>
                    Todos <span>{{ $todos->count() }}</span>
                </a>
                @foreach ($categorias as $clave => $nombre)
                    <a href="{{ route('admin.productos.index', ['categoria' => $clave]) }}" @class(['pp-tab', 'is-active' => $filtro === $clave])>
                        {{ $nombre }} <span>{{ $conteos[$clave] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="pp-actions">
                <button type="button" class="pp-btn pp-btn--ghost" data-abrir-masiva>Actualizar precios</button>
                <button type="button" class="pp-btn pp-btn--primary" data-nuevo>+ Nuevo producto</button>
            </div>
        </div>

        <div class="pp-card">
            <div class="pp-scroll">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th scope="col">Producto</th>
                            <th scope="col">Categoría</th>
                            <th scope="col">Precio</th>
                            <th scope="col">Stock</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="pp-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($productos as $producto)
                            @php
                                $activo = $producto->estado === 'activo';
                                $esUnidad = $producto->llevaStock();
                                $sinStock = $esUnidad && $producto->stock === 0;
                                $stockBajo = $esUnidad && $producto->stock > 0 && $producto->stock <= 5;
                                $datosEdicion = [
                                    'id'        => $producto->id,
                                    'url'       => route('admin.productos.update', $producto),
                                    'nombre'    => $producto->nombre,
                                    'categoria' => $producto->categoria,
                                    'tipo'      => $producto->tipo,
                                    'stock'     => $producto->stock,
                                    'precio'    => (int) $producto->precio,
                                    'activo'    => $activo,
                                    'masiva'    => (bool) $producto->permite_actualizacion_masiva,
                                ];
                            @endphp
                            <tr @class(['is-inactive' => ! $activo])>
                                <td>
                                    <span class="pp-name">
                                        {{ $producto->nombre }}
                                        @unless ($producto->permite_actualizacion_masiva)
                                            <span class="pp-lock" role="img" title="No permite actualización masiva de precios" aria-label="No permite actualización masiva de precios">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#8b8175" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path></svg>
                                            </span>
                                        @endunless
                                    </span>
                                </td>
                                <td><span class="pp-badge pp-badge--{{ $producto->categoria }}">{{ ucfirst($producto->categoria) }}</span></td>
                                <td><span class="pp-price">$ {{ number_format($producto->precio, 0, ',', '.') }}</span></td>
                                <td>
                                    @if ($esUnidad)
                                        <span class="pp-stock">
                                            <span @class(['pp-num', 'is-danger' => $sinStock, 'is-warn' => $stockBajo])>{{ $producto->stock }} u.</span>
                                            @if ($sinStock)
                                                <span class="pp-chip pp-chip--danger">Sin stock</span>
                                            @elseif ($stockBajo)
                                                <span class="pp-chip pp-chip--warn">Stock bajo</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="pp-muted">Sin control</span>
                                    @endif
                                </td>
                                <td>
                                    <span @class(['pp-state', 'pp-state--on' => $activo, 'pp-state--off' => ! $activo])>{{ $activo ? 'Activo' : 'Inactivo' }}</span>
                                </td>
                                <td class="pp-cell-actions">
                                    <div class="pp-row-actions">
                                        <button type="button" class="pp-btn pp-btn--ghost pp-btn--sm" data-editar="{{ json_encode($datosEdicion, JSON_UNESCAPED_UNICODE) }}">Editar</button>
                                        <form method="POST" action="{{ route('admin.productos.estado', $producto) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" @class(['pp-btn', 'pp-btn--ghost', 'pp-btn--sm', 'is-danger' => $activo, 'is-ok' => ! $activo])>{{ $activo ? 'Dar de baja' : 'Dar de alta' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="pp-empty">No hay productos en esta categoría.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="pp-note">Los productos dados de baja o sin stock no se pueden cargar en una comanda. El stock de los productos por unidad se descuenta solo cada vez que un mozo los carga.</p>
    </div>

    @if (session('success'))
        <div class="pp-toast" role="status" data-toast>
            <span class="pp-toast-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a9bf86" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </span>
            <span>{{ session('success') }}</span>
            <button type="button" aria-label="Cerrar aviso">×</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="pp-toast pp-toast--error" role="alert" data-toast="9000">
            <span class="pp-toast-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d9908f" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </span>
            <span>{{ $errors->first() }}</span>
            <button type="button" aria-label="Cerrar aviso">×</button>
        </div>
    @endif

    {{-- Crear / editar producto --}}
    <dialog class="pp-dialog" id="dlg-producto" aria-labelledby="dlg-producto-titulo">
        <form method="POST" id="form-producto" class="pp-dialog-body" novalidate
              action="{{ $editandoId ? route('admin.productos.update', $editandoId) : route('admin.productos.store') }}">
            @csrf
            <input type="hidden" name="_method" value="{{ $editandoId ? 'PUT' : 'POST' }}">
            <input type="hidden" name="_form" value="producto">
            <input type="hidden" name="_editando" value="{{ $editandoId }}">

            <div class="pp-dialog-head">
                <h2 id="dlg-producto-titulo">{{ $editandoId ? 'Editar producto' : 'Nuevo producto' }}</h2>
                <p id="dlg-producto-sub">{{ $editandoId ? 'Modificá los datos del producto' : 'Completá los datos para sumarlo al catálogo' }}</p>
            </div>

            <div class="pp-field">
                <label for="fp-nombre">Nombre</label>
                <input id="fp-nombre" name="nombre" type="text" maxlength="100" placeholder="Ej: Agua mineral 500 ml"
                       value="{{ $f['nombre'] }}" @class(['pp-input', 'is-invalid' => $e->has('nombre')])>
                @if ($e->has('nombre'))<p class="pp-error">{{ $e->first('nombre') }}</p>@endif
            </div>

            <fieldset class="pp-fieldset">
                <legend class="pp-label">Categoría</legend>
                <div class="pp-seg">
                    @foreach ($categorias as $clave => $plural)
                        <label class="pp-seg-{{ $clave }}">
                            <input type="radio" name="categoria" value="{{ $clave }}" @checked($f['categoria'] === $clave)>
                            <span>{{ ucfirst($clave) }}</span>
                        </label>
                    @endforeach
                </div>
                @if ($e->has('categoria'))<p class="pp-error">{{ $e->first('categoria') }}</p>@endif
            </fieldset>

            <fieldset class="pp-fieldset">
                <legend class="pp-label">Tipo de producto</legend>
                <div class="pp-seg">
                    @foreach ($tipos as $clave => $nombre)
                        <label>
                            <input type="radio" name="tipo" value="{{ $clave }}" @checked($f['tipo'] === $clave)>
                            <span>{{ $nombre }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="pp-help" id="fp-tipo-ayuda"></p>
                @if ($e->has('tipo'))<p class="pp-error">{{ $e->first('tipo') }}</p>@endif
            </fieldset>

            <div class="pp-row">
                <div class="pp-field">
                    <label for="fp-precio">Precio</label>
                    <div class="pp-prefix">
                        <span>$</span>
                        <input id="fp-precio" name="precio" type="text" inputmode="numeric" placeholder="0"
                               value="{{ $f['precio'] }}" @class(['pp-input', 'is-invalid' => $e->has('precio')])>
                    </div>
                    @if ($e->has('precio'))<p class="pp-error">{{ $e->first('precio') }}</p>@endif
                </div>

                <div class="pp-field" id="fp-stock-campo" @if ($f['tipo'] !== 'unidad') hidden @endif>
                    <label for="fp-stock">Stock actual</label>
                    <div class="pp-suffix">
                        <input id="fp-stock" name="stock" type="text" inputmode="numeric" placeholder="0"
                               value="{{ $f['stock'] }}" @class(['pp-input', 'is-invalid' => $e->has('stock')])>
                        <span>u.</span>
                    </div>
                    @if ($e->has('stock'))<p class="pp-error">{{ $e->first('stock') }}</p>@endif
                </div>
            </div>

            <div class="pp-switch-row">
                <div>
                    <strong>Activo</strong>
                    <small>Si está inactivo, el mozo no puede cargarlo en una comanda</small>
                </div>
                <input type="checkbox" class="pp-switch" role="switch" name="activo" value="1" aria-label="Producto activo" @checked($f['activo'])>
            </div>

            <div class="pp-switch-row">
                <div>
                    <strong>Permitir actualización masiva</strong>
                    <small>Si está desactivado, este producto nunca recibe cambios de precio masivos</small>
                </div>
                <input type="checkbox" class="pp-switch" role="switch" name="permite_actualizacion_masiva" value="1" aria-label="Permitir actualización masiva" @checked($f['masiva'])>
            </div>

            <div class="pp-dialog-actions">
                <button type="button" class="pp-btn pp-btn--ghost" data-cerrar>Cancelar</button>
                <button type="submit" class="pp-btn pp-btn--primary" id="fp-guardar">{{ $editandoId ? 'Guardar cambios' : 'Crear producto' }}</button>
            </div>
        </form>
    </dialog>

    {{-- Actualización masiva de precios --}}
    <dialog class="pp-dialog pp-dialog--wide" id="dlg-masiva" aria-labelledby="dlg-masiva-titulo">
        <form method="POST" id="form-masiva" class="pp-dialog-body" novalidate
              action="{{ route('admin.productos.masiva.aplicar') }}">
            @csrf

            <div class="pp-dialog-head">
                <h2 id="dlg-masiva-titulo">Actualización masiva de precios</h2>
                <p>Modificá varios precios a la vez. Esta acción solo cambia precios: el stock, el estado y las categorías no se tocan.</p>
            </div>

            <div class="pp-masiva">
                {{-- 1. Qué cambio hacer y a quién --}}
                <div class="pp-masiva-col pp-masiva-config">
                    <fieldset class="pp-fieldset">
                        <legend class="pp-label">Acción</legend>
                        <div class="pp-seg">
                            <label><input type="radio" name="direccion" value="1" checked><span>Aumentar</span></label>
                            <label><input type="radio" name="direccion" value="-1"><span>Disminuir</span></label>
                        </div>
                    </fieldset>

                    <fieldset class="pp-fieldset">
                        <legend class="pp-label">Tipo de ajuste</legend>
                        <div class="pp-radios pp-radios--row">
                            <label class="pp-radio"><input type="radio" name="tipo" value="porcentaje" checked><span class="pp-radio-dot"></span><span>Porcentaje (%)</span></label>
                            <label class="pp-radio"><input type="radio" name="tipo" value="monto"><span class="pp-radio-dot"></span><span>Monto fijo ($)</span></label>
                        </div>
                    </fieldset>

                    <div class="pp-field">
                        <label for="m-valor" id="m-valor-label">Porcentaje de aumento</label>
                        <div class="pp-valor is-porcentaje" id="m-valor-wrap">
                            <span class="pp-valor-pre">$</span>
                            <input id="m-valor" name="valor" type="text" inputmode="decimal" autocomplete="off" placeholder="15" class="pp-input">
                            <span class="pp-valor-suf">%</span>
                        </div>
                    </div>

                    <fieldset class="pp-fieldset">
                        <legend class="pp-label">Aplicar a</legend>
                        <div class="pp-radios">
                            <label class="pp-radio"><input type="radio" name="alcance" value="todos" checked><span class="pp-radio-dot"></span><span>Todos los productos</span></label>
                            <label class="pp-radio"><input type="radio" name="alcance" value="categoria"><span class="pp-radio-dot"></span><span>Una categoría</span></label>
                            <label class="pp-radio"><input type="radio" name="alcance" value="seleccionados"><span class="pp-radio-dot"></span><span>Productos seleccionados</span></label>
                        </div>
                        <div class="pp-seg pp-seg--sm" id="m-categorias" hidden>
                            @foreach ($categorias as $clave => $plural)
                                <label class="pp-seg-{{ $clave }}"><input type="radio" name="categoria" value="{{ $clave }}" @checked($clave === 'bebida')><span>{{ $plural }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                {{-- 2. Productos a modificar --}}
                <div class="pp-masiva-col">
                    <span class="pp-label">Productos a modificar</span>
                    <p class="pp-help" id="m-lista-ayuda"></p>
                    <div class="pp-listbox" id="m-lista">
                        @foreach ($todos as $p)
                            @php
                                $bloqueado = ! $p->permite_actualizacion_masiva;
                            @endphp
                            <label @class(['pp-check', 'is-locked' => $bloqueado])
                                   data-id="{{ $p->id }}"
                                   data-categoria="{{ $p->categoria }}"
                                   data-nombre="{{ $p->nombre }}"
                                   data-precio="{{ (int) $p->precio }}"
                                   data-bloqueado="{{ $bloqueado ? '1' : '0' }}">
                                <input type="checkbox" value="{{ $p->id }}" @disabled($bloqueado)>
                                <span class="pp-check-box" aria-hidden="true">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#1b1815" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </span>
                                <span class="pp-dot pp-dot--{{ $p->categoria }}"></span>
                                <span class="pp-check-name">{{ $p->nombre }}</span>
                                @if ($bloqueado)
                                    <span class="pp-chip pp-chip--muted">Bloqueado</span>
                                @endif
                                @if ($p->estado !== 'activo')
                                    <span class="pp-chip pp-chip--muted">Inactivo</span>
                                @endif
                                <span class="pp-check-price">$ {{ number_format($p->precio, 0, ',', '.') }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="pp-help">Los productos bloqueados no permiten actualización masiva: nunca se modifican, aunque estén dentro del alcance elegido.</p>
                </div>

                {{-- 3. Vista previa (la calcula el servidor) --}}
                <div class="pp-masiva-col">
                    <div class="pp-preview-head">
                        <span class="pp-label">Vista previa</span>
                        <span class="pp-counter" id="m-contador" aria-live="polite"></span>
                    </div>
                    <div class="pp-listbox">
                        <table class="pp-prev">
                            <thead>
                                <tr>
                                    <th scope="col">Producto</th>
                                    <th scope="col" class="pp-right">Actual</th>
                                    <th scope="col" class="pp-right">Ajuste</th>
                                    <th scope="col" class="pp-right">Nuevo precio</th>
                                </tr>
                            </thead>
                            <tbody id="m-filas"></tbody>
                        </table>
                        <p class="pp-prev-msg" id="m-mensaje" hidden></p>
                    </div>
                </div>
            </div>

            <div class="pp-masiva-foot">
                <span class="pp-masiva-aviso" id="m-aviso" aria-live="polite"></span>
                <div class="pp-dialog-actions">
                    <button type="button" class="pp-btn pp-btn--ghost" data-cerrar-masiva>Cancelar</button>
                    <button type="button" class="pp-btn pp-btn--primary" id="m-aplicar" disabled>Aplicar actualización</button>
                </div>
            </div>
        </form>
    </dialog>

    {{-- Confirmación antes de aplicar --}}
    <dialog class="pp-dialog pp-dialog--confirm" id="dlg-confirmar" aria-labelledby="dlg-confirmar-titulo">
        <div class="pp-dialog-body">
            <div class="pp-dialog-head">
                <h2 id="dlg-confirmar-titulo"></h2>
                <p id="dlg-confirmar-detalle"></p>
            </div>
            <div class="pp-dialog-actions">
                <button type="button" class="pp-btn pp-btn--ghost" id="c-volver">Volver</button>
                <button type="button" class="pp-btn pp-btn--primary" id="c-confirmar">Sí, aplicar</button>
            </div>
        </div>
    </dialog>

    <div id="pp-config" hidden
         data-url-store="{{ route('admin.productos.store') }}"
         data-url-vista-previa="{{ route('admin.productos.masiva.vista-previa') }}"
         data-reabrir="{{ $e->any() ? '1' : '' }}"></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/productos.js') }}" defer></script>
    <script src="{{ asset('js/productos-masiva.js') }}" defer></script>
@endpush