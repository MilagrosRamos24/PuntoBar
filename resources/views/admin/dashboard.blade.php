@extends('admin.layout')

@section('title', 'Panel de administración')

@section('shell-class', 'pb-shell--panel')

@section('admin-header')
    <x-admin-nav titulo="Administración" />
@endsection

@section('content')

<section class="card">

    <p class="badge">Administración</p>

    <h1>Hola, {{ auth()->user()->name }}</h1>

    <p>
        Ingresaste al panel de administración de Punto Bar.
    </p>

    <div class="modules">

        {{-- MÓDULO MESAS --}}
        <a href="{{ route('admin.mesas.index') }}" class="module">
            <strong class="con-icono"><x-icono nombre="mesas" /> Mesas</strong>
            <p>Gestión de mesas</p>
        </a>

        {{-- MÓDULO MOZOS --}}
        <a href="{{ route('admin.mozos.index') }}" class="module">
            <strong class="con-icono"><x-icono nombre="mozo" /> Mozos</strong>
            <p>Gestión del personal</p>
        </a>

        {{-- MÓDULO COMANDAS --}}
        <a href="{{ route('admin.comandas.index') }}" class="module">
            <strong class="con-icono"><x-icono nombre="comanda" /> Comandas</strong>
            <p>Listado e historial de pedidos</p>
        </a>

    </div>

    <form
        method="POST"
        action="{{ route('admin.logout') }}"
    >
        @csrf

        <button type="submit" class="con-icono">
            <x-icono nombre="salir" tamano="18" />
            Cerrar sesión
        </button>
    </form>

</section>

@endsection