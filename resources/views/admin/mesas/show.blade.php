@extends('admin.mesas.layout')
@section('title', 'Consultar mesa')
@section('content')
@php
    // Color del círculo según el estado (los nombres guardados en la base, y la alerta de espera)
    $clasesEstado = [
        'libre' => 'libre',
        'ocupada' => 'espera',
        'reservada' => 'atendida',
        'pendiente_de_cierre' => 'alerta',
    ];
    $claseEstado = $clasesEstado[$mesa->estado_visual ?? $mesa->estado] ?? 'libre';
@endphp
<section class="tarjeta formulario">
    <div class="titulo-mesa">
        <span class="icono-mesa {{ $claseEstado }}">
            <x-icono nombre="mesa" tamano="30" />
        </span>
        <h1>Mesa {{ $mesa->numero }}</h1>
    </div>
    <dl>
        <dt>Disponibilidad</dt><dd>{{ $mesa->activa ? 'Activa' : 'Inactiva' }}</dd>
        <dt>Estado</dt><dd><span class="estado {{ $mesa->estado }}">{{ $mesa->estado_texto }}</span></dd>
        <dt>Mozo asignado</dt><dd>{{ $mesa->mozo?->name ?? 'Sin asignar' }}</dd>
        <dt>Cantidad de personas</dt><dd>{{ $mesa->cantidad_personas }}</dd>
    </dl>
    <div class="acciones">
        <a href="{{ route('admin.mesas.index') }}" class="con-icono">
            <x-icono nombre="volver" tamano="18" />
            Volver al listado
        </a>
        <a class="primario con-icono" href="{{ route('admin.mesas.edit', $mesa) }}">
            <x-icono nombre="editar" tamano="18" />
            Editar
        </a>
<form action="{{ route('admin.mesas.actividad', $mesa) }}" method="POST"
    data-confirmar="{{ $mesa->activa ? '¿Desactivar esta mesa? Se conservará su historial y se quitará de la vista de atención.' : '¿Reactivar esta mesa? Volverá a estar disponible como libre.' }}"
    data-confirmar-titulo="{{ $mesa->activa ? 'Desactivar mesa' : 'Reactivar mesa' }}"
    data-confirmar-aceptar="{{ $mesa->activa ? 'Desactivar' : 'Reactivar' }}">
    @csrf
    @method('PATCH')
    <button type="submit" class="{{ $mesa->activa ? 'peligro' : 'primario' }} con-icono">
        {{ $mesa->activa ? 'Desactivar' : 'Reactivar' }}
    </button>
</form>

    </div>
</section>
@endsection
