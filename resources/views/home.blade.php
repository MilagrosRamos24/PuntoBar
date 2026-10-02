<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Punto Bar - Gestión</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: #12110f;
            color: #eee9e1;
            font-family: Arial, Helvetica, sans-serif;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .contenedor {
            width: 760px;
            max-width: 90%;
            text-align: center;
        }

        /* LOGO */

       .logo {
    margin-bottom: 0px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.logo-imagen {
    width: 390px;
    height: auto;
    display: block;
}
     

    
        /* TARJETA */

        .login-card {
            background: #1a1815;
            border: 1px solid #403628;
            border-radius: 20px;
            padding: 40px;
            text-align: left;
            position: relative;
            top: -60px;
        }

        .login-card h2 {
            font-size: 25px;
            margin-bottom: 5px;
        }

        .descripcion {
            color: #a49b8e;
            font-size: 16px;
            margin-bottom: 22px;
        }

        /* OPCIONES */

        .opciones {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px;
        }

        .opcion {
            background: #12110f;
            border: 1px solid #3c352d;
            min-height: 155px;
            padding: 25px;

            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;

            text-decoration: none;
            color: #eee9e1;

            transition: 0.2s ease;
        }

        .opcion:first-child {
            border-radius: 16px 0 0 16px;
        }

        .opcion:last-child {
            border-radius: 0 16px 16px 0;
        }

        .opcion:hover {
            border-color: #e89455;
            background: #191613;
            transform: translateY(-2px);
        }

        /* ICONOS */

        .icono {
            width: 58px;
            height: 58px;
            border-radius: 50%;

            display: flex;
            justify-content: center;
            align-items: center;

            background: #29231c;
            color: #e9a365;

            font-size: 25px;
            margin-bottom: 14px;
        }

        .opcion h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .opcion p {
            color: #a49b8e;
            font-size: 14px;
            text-align: center;
        }

        /* RESPONSIVE */

        @media (max-width: 650px) {

            .logo-imagen {
    width: 390px;
}

            .opciones {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .opcion:first-child,
            .opcion:last-child {
                border-radius: 15px;
            }

            .login-card {
                padding: 25px;
            }
        }
    </style>
</head>

<body>

    <main class="contenedor">

        <!-- LOGO -->
<div class="logo">
    <img src="{{ asset('img/punto-bar-logo-transparente.png') }}"
         alt="Punto Bar - Gestión de Bares"
         class="logo-imagen">
</div>
       

        <!-- LOGIN -->
        <section class="login-card">

            <h2>Ingresar al sistema</h2>

            <p class="descripcion">
                Elegí tu rol para continuar
            </p>

            <div class="opciones">

                <!-- ADMINISTRADOR -->
                <a href="/admin/login" class="opcion">

                    <div class="icono" aria-hidden="true">
                        🤵
                    </div>

                    <h3>Administrador</h3>

                    <p>
                        Mesas, mozos y comandas
                    </p>

                </a>

                <!-- MOZO -->
                <a href="/mozo/login" class="opcion">

                    <div class="icono" aria-hidden="true">
                        🍽️
                    </div>

                    <h3>Mozo</h3>

                    <p>
                        Atención de mesas
                    </p>

                </a>

            </div>

        </section>

    </main>

</body>
</html>