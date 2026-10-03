@extends('layouts.puntobar')
@section('title', 'Inicio')
@section('shell-class', 'pb-shell--home')
@section('content')
        <!-- LOGIN -->
        <section class="card">

            <h1>Ingresar al sistema</h1>

            <p class="descripcion">
                Elegí tu rol para continuar
            </p>

            <div class="opciones">

                <!-- ADMINISTRADOR -->
                <a href="{{ route('admin.login') }}" class="opcion">

                    <div class="icono" aria-hidden="true">
                        🤵
                    </div>

                    <h3>Administrador</h3>

                    <p>
                        Mesas, mozos y comandas
                    </p>

                </a>

                <!-- MOZO -->
                <a href="{{ route('mozo.login') }}" class="opcion">

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

@endsection
