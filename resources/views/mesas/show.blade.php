<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Mesa {{ $mesa->numero }} · Punto Bar</title>

    <link rel="stylesheet" href="{{ asset('css/mesas.css') }}">
</head>
<body>
    <header class="cabecera">
        <img
            src="{{ asset('img/punto-bar-logo-transparente.png') }}"
            alt="Punto Bar"
        >

        <nav aria-label="Navegación">
            <a href="{{ route('mesas') }}">Volver a las mesas</a>

            <form
                action="{{ auth()->user()->role === 'admin'
                    ? route('admin.logout')
                    : route('mozo.logout') }}"
                method="POST"
            >
                @csrf

                <button type="submit">Cerrar sesión</button>
            </form>
        </nav>
    </header>

    <main class="contenido">
        <section class="tarjeta formulario">
            <h1>Mesa {{ $mesa->numero }}</h1>

            <dl>
                <dt>Estado</dt>
                <dd>
                    <span class="estado {{ $mesa->estado }}">
                        {{ $mesa->estado_texto }}
                    </span>
                </dd>

                <dt>Mozo asignado</dt>
                <dd>{{ $mesa->mozo?->name ?? 'Sin asignar' }}</dd>

                <dt>Cantidad de personas</dt>
                <dd>{{ $mesa->cantidad_personas }}</dd>
            </dl>

            <form
                action="{{ route('mesas.estado', $mesa) }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="campo">
                    <label for="estado">Cambiar estado</label>

                    <select name="estado" id="estado" required>
                        @if(!array_key_exists($mesa->estado, \App\Models\Mesa::ESTADOS))
                            <option value="" selected disabled>
                                Elegí un nuevo estado
                            </option>
                        @endif

                        @foreach(\App\Models\Mesa::ESTADOS as $valor => $texto)
                            <option
                                value="{{ $valor }}"
                                @selected($mesa->estado === $valor)
                            >
                                {{ $texto }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="primario">
                    Actualizar estado
                </button>
            </form>
        </section>
    </main>
</body>
</html>