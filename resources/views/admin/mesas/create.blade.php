<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva Mesa - PUNTO BAR</title>

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

        .btn-volver {
            background: transparent;
            border: 1px solid #4a3020;
            color: #cfc3b8;

            padding: 9px 16px;
            border-radius: 8px;

            text-decoration: none;
            font-size: 14px;
        }

        .btn-volver:hover {
            border-color: #8b572f;
            color: #e2a15c;
        }

        .contenido {
            max-width: 650px;
            margin: auto;
            padding: 50px 30px;
        }

        .card {
            background: #1b1410;
            border: 1px solid #3e2a1e;
            border-radius: 16px;
            padding: 35px;
        }

        h1 {
            font-size: 28px;
            color: #f4eee8;
            margin-bottom: 8px;
        }

        .descripcion {
            color: #9f9389;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .campo {
            margin-bottom: 24px;
        }

        label {
            display: block;
            color: #cfc3b8;
            font-size: 14px;
            margin-bottom: 9px;
        }

        input {
            width: 100%;
            background: #120d0a;
            border: 1px solid #4a3020;
            border-radius: 8px;

            padding: 12px 14px;

            color: #f1e9df;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: #a66a3c;
        }

        .error {
            margin-top: 7px;
            color: #d87874;
            font-size: 13px;
        }

        .acciones {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
        }

        .btn-cancelar {
            background: transparent;
            border: 1px solid #4a3020;
            color: #cfc3b8;

            padding: 11px 18px;
            border-radius: 8px;

            text-decoration: none;
            font-size: 14px;
        }

        .btn-guardar {
            background: #8b572f;
            border: none;
            color: white;

            padding: 11px 20px;
            border-radius: 8px;

            cursor: pointer;
            font-size: 14px;
        }

        .btn-guardar:hover {
            background: #a66a3c;
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

        <a
            href="{{ route('admin.mesas.index') }}"
            class="btn-volver"
        >
            ← Volver
        </a>

    </nav>


    <main class="contenido">

        <div class="card">

            <h1>Nueva mesa</h1>

            <p class="descripcion">
                Ingresá el número de la nueva mesa.
            </p>


            <form
                action="{{ route('admin.mesas.store') }}"
                method="POST"
            >

                @csrf


                <div class="campo">

                    <label for="numero">
                        Número de mesa
                    </label>

                    <input
                        type="number"
                        name="numero"
                        id="numero"
                        min="1"
                        value="{{ old('numero') }}"
                        required
                        autofocus
                    >

                    @error('numero')
                        <div class="error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="acciones">

                    <a
                        href="{{ route('admin.mesas.index') }}"
                        class="btn-cancelar"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn-guardar"
                    >
                        Crear mesa
                    </button>

                </div>

            </form>

        </div>

    </main>

</body>
</html>