<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Punto Bar - Acceso de Mozo</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #12110f;
            color: #f1eee9;
            font-family: Arial, Helvetica, sans-serif;
        }

        .contenedor {
            width: 100%;
            max-width: 500px;
            padding: 30px;
        }

        .logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo-imagen {
            width: 330px;
            height: auto;
            display: block;
        }

        .subtitulo {
            text-align: center;
            color: #b8b1a8;
            font-size: 15px;
            margin-bottom: 30px;
        }

        .card {
            background: #1a1815;
            border: 1px solid #403628;
            border-radius: 20px;
            padding: 30px;
        }

        .titulo {
            text-align: center;
            font-size: 22px;
            margin-bottom: 8px;
        }

        .descripcion {
            text-align: center;
            color: #aaa39b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .campo {
            margin-bottom: 20px;
        }

        .campo label {
            display: block;
            margin-bottom: 8px;
            color: #ddd7d0;
            font-size: 14px;
        }

        .campo select,
        .campo input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 10px;
            border: 1px solid #403628;
            background: #12110f;
            color: #f1eee9;
            font-size: 15px;
            outline: none;
        }

        .campo select:focus,
        .campo input:focus {
            border-color: #e89455;
        }

        .campo select option {
            background: #1a1815;
            color: #f1eee9;
        }

        .error {
            background: #3a211b;
            border: 1px solid #754333;
            color: #f0b09a;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .boton {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #e89455;
            color: #17120f;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .boton:hover {
            background: #f0a46b;
        }

        .volver {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #aaa39b;
            text-decoration: none;
            font-size: 14px;
        }

        .volver:hover {
            color: #e89455;
        }

        @media (max-width: 600px) {
            .contenedor {
                padding: 20px;
            }

            .logo-imagen {
                width: 250px;
            }

            .card {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <div class="logo">
        <img
            src="{{ asset('img/punto-bar-logo-transparente.png') }}"
            alt="Punto Bar"
            class="logo-imagen"
        >
    </div>

    <p class="subtitulo">
        Gestión de mesas y comandas
    </p>

    <div class="card">

        <h1 class="titulo">Acceso de Mozo</h1>

        <p class="descripcion">
            Seleccioná tu nombre e ingresá tu contraseña.
        </p>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

      <form action="{{ route('mozo.login.process') }}" method="POST">
    @csrf

    <div class="campo">
        <label for="username">Mozo</label>

        <select name="username" id="username" required>
            <option value="">Seleccionar mozo</option>

            @foreach ($mozos as $mozo)
                <option
                    value="{{ $mozo->username }}"
                    {{ old('username') == $mozo->username ? 'selected' : '' }}
                >
                    {{ $mozo->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="campo">
        <label for="password">Contraseña</label>

        <input
            type="password"
            name="password"
            id="password"
            placeholder="Ingresá tu contraseña"
            required
        >
    </div>

    <button type="submit" class="boton">
        Ingresar
    </button>
</form>

        <a href="{{ url('/') }}" class="volver">
            ← Volver
        </a>

    </div>

</div>

</body>
</html>