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
                        <x-icono nombre="ajustes" tamano="34" :base="false" />
                    </div>

                    <h3>Administrador</h3>

                    <p>
                        Mesas, mozos y comandas
                    </p>

                </a>

                <!-- MOZO -->
                <a href="{{ route('mozo.login') }}" class="opcion">

                    <div class="icono" aria-hidden="true">
                        <x-icono nombre="mesa" tamano="34" :base="false" />
                    </div>

                    <h3>Mozo</h3>

                    <p>
                        Atención de mesas
                    </p>

                </a>

            </div>

        </section>
 
        <p style="text-align: center; margin-top: 24px">
    <a href="{{ route('ayuda') }}" style="display: inline-flex; align-items: center; min-height: 48px; padding: 0 22px; border: 1px solid #e9a365; border-radius: 14px; color: #e9a365; font-weight: 600; text-decoration: none">¿Primera vez en Punto Bar? Leé el manual de usuario</a>
</p>
@endsection
