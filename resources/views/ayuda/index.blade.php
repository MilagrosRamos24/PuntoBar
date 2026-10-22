@extends('layouts.puntobar')

@section('title', 'Manual de usuario')
@section('shell-class', 'pb-shell--productos')

@section('content')
    @php
        $indices = [
            'mozo' => [
                'ingreso'   => 'Ingresar y salir',
                'mesas'     => 'Entender las mesas',
                  'tomar-mesa' => 'Tomar una mesa libre',
                'estado'     => 'Cambiar el estado de la mesa',
                'comanda'   => 'Abrir una comanda',
                'productos' => 'Cargar productos',
                'cantidad'  => 'Cambiar la cantidad o quitar un producto',
                'descuento' => 'Descuento por consumo',
                'cerrar'    => 'Cerrar la comanda',
                'preguntas' => 'Preguntas frecuentes',
            ],
            'administrador' => [
                'ingreso'         => 'Ingresar y salir',
                'panel'           => 'Panel de mesas',
                'gestion-mesas'   => 'Gestionar mesas',
                'mozos'           => 'Gestionar mozos',
                'productos-stock' => 'Productos y stock',
                'masiva'          => 'Actualización masiva de precios',
                'descuento-admin' => 'Descuento por consumo',
                'historial'       => 'Historial de comandas',
                'notificaciones'  => 'Notificaciones de stock',
                'preguntas'       => 'Preguntas frecuentes',
            ],
        ];
    @endphp

    <link rel="stylesheet" href="{{ asset('css/productos.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ayuda.css') }}">

    <div class="pp ay">
        <header class="pp-header">
            <div class="pp-brand">
                <img class="pp-logo" src="{{ asset('img/punto-bar-logo-transparente.png') }}" alt="Punto Bar">
                <div>
                    <h1>Manual de usuario</h1>
                    <p>Todo lo que necesitás para usar Punto Bar, paso a paso.</p>
                </div>
            </div>

            <div class="pp-header-right">
                <nav class="pp-nav" aria-label="Volver">
                    @auth
                        <a href="{{ route('mesas') }}">← Volver a Mesas</a>
                    @else
                        <a href="{{ route('home') }}">← Volver al inicio</a>
                    @endauth
                </nav>
            </div>
        </header>

        <div class="ay-controles">
            <div class="ay-rol" role="group" aria-label="Elegí tu rol">
                <a href="{{ route('ayuda', ['rol' => 'mozo']) }}" @if ($rol === 'mozo') aria-current="true" @endif>Mozo</a>
                <a href="{{ route('ayuda', ['rol' => 'administrador']) }}" @if ($rol === 'administrador') aria-current="true" @endif>Administrador</a>
            </div>

            @if ($hayPdf)
                <a class="ay-pdf" href="{{ asset('manual/manual-de-usuario.pdf') }}" download>Descargar PDF</a>
            @endif
        </div>

        <div class="ay-cuerpo">
            <nav class="ay-indice" aria-label="En esta página">
                <p class="ay-indice-titulo">En esta página</p>
                <ul>
                    @foreach ($indices[$rol] as $ancla => $titulo)
                        <li><a href="#{{ $ancla }}">{{ $titulo }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <article class="ay-contenido">
                <section class="ay-seccion" id="ingreso">
                    <h2>Ingresar y salir</h2>
                    <p>La pantalla de inicio pide elegir el rol: <strong>Administrador</strong> (mesas, mozos y comandas) o <strong>Mozo</strong> (atención de mesas).</p>

                    @if ($rol === 'mozo')
                        <h3>Ingreso del mozo</h3>
                        <ol>
                            <li>En la pantalla de inicio, tocá <strong>Mozo</strong>.</li>
                            <li>En la lista <strong>Mozo</strong>, elegí tu nombre.</li>
                            <li>Escribí tu contraseña y tocá <strong>Ingresar</strong>.</li>
                        </ol>
                        <p>La lista muestra solo a los mozos activos. Si tu nombre no aparece, pedile al administrador que revise que tu cuenta esté activa.</p>
                    @else
                        <h3>Ingreso del administrador</h3>
                        <ol>
                            <li>En la pantalla de inicio, tocá <strong>Administrador</strong>.</li>
                            <li>Escribí tu usuario y tu contraseña.</li>
                            <li>Tocá <strong>Ingresar</strong>. Vas a llegar al panel de <strong>Gestión de mesas</strong>.</li>
                        </ol>
                        <p>Si te equivocás varias veces seguidas, el sistema bloquea el acceso por un tiempo y muestra el aviso «Demasiados intentos». Esperá unos minutos y volvé a probar.</p>
                    @endif

                    <h3>Cerrar sesión</h3>
                    <p>Cuando termines, cerrá la sesión desde el botón <strong>Cerrar sesión</strong> (administrador) o <strong>Salir</strong> (barra inferior de la pantalla de mesas). El sistema vuelve a la pantalla de inicio. En una computadora compartida, cerrar la sesión es importante para que otra persona no use tu cuenta.</p>
                </section>

                @include($rol === 'mozo' ? 'ayuda._mozo' : 'ayuda._administrador')

                <section class="ay-seccion" id="preguntas">
                    <h2>Preguntas frecuentes</h2>
                    <p>La mayoría de los avisos del sistema se resuelven en el momento. Esta tabla reúne los más comunes, con su causa y lo que hay que hacer.</p>

                    <div class="ay-tabla">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Situación o mensaje</th>
                                    <th scope="col">Qué significa</th>
                                    <th scope="col">Qué hacer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>«El usuario o la contraseña son incorrectos.»</td><td>La contraseña no coincide con la cuenta elegida.</td><td>Revisá la contraseña. Si no la recordás, pedile al administrador que la cambie.</td></tr>
                                <tr><td>«Demasiados intentos»</td><td>Hubo varios ingresos fallidos seguidos y el acceso se bloqueó un tiempo.</td><td>Esperá unos minutos y volvé a intentar.</td></tr>
                                <tr><td>Mi nombre no aparece en la lista de mozos</td><td>La cuenta está dada de baja.</td><td>Pedile al administrador que la reactive.</td></tr>
                                <tr><td>No puedo abrir la comanda de una mesa</td><td>La mesa ya tiene una comanda abierta, no tiene mozo asignado o la atiende otro mozo.</td><td>Revisá el mozo asignado a la mesa y si ya hay una comanda abierta.</td></tr>
                                <tr><td>Un producto no aparece al cargar la comanda</td><td>Está dado de baja, o figura como «Sin stock» y no se puede elegir.</td><td>Avisale al administrador para que lo dé de alta o reponga el stock.</td></tr>
                                <tr><td>El sistema no carga la cantidad pedida</td><td>Pedís más unidades de las que hay en stock.</td><td>Cargá la cantidad que indica el aviso o elegí otro producto.</td></tr>
                                <tr><td>No puedo cerrar la comanda</td><td>La comanda no tiene productos.</td><td>Cargá al menos un producto antes de cerrarla.</td></tr>
                                <tr><td>No puedo modificar una comanda</td><td>La comanda ya está cerrada.</td><td>Avisale al administrador.</td></tr>
                                <tr><td>«Ese usuario de acceso ya está registrado.»</td><td>Otro mozo ya usa ese DNI.</td><td>Revisá que el DNI esté bien escrito.</td></tr>
                                <tr><td>Un precio no cambió con la actualización masiva</td><td>El producto tiene candado.</td><td>Editá el producto y activá <strong>Permitir actualización masiva</strong>.</td></tr>
                                <tr><td>«Page expired» (error 419)</td><td>La página estuvo abierta mucho tiempo o la sesión se perdió.</td><td>Recargá la página, o cerrá la sesión y volvé a ingresar.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <p>Si aparece un error que no figura acá, o una pantalla de error en inglés, avisale a la persona responsable del sistema.</p>
                </section>
            </article>
        </div>
    </div>
@endsection