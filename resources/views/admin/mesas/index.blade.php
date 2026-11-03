@extends('admin.mesas.layout')
@section('title', 'Gestionar mesas')
@section('content')
@if (session('error'))
    <div role="alert" style="
        background: #fdecea;
        color: #842029;
        border: 1px solid #f5c2c7;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 18px;
    ">
        {{ session('error') }}
    </div>
@endif
<div class="encabezado"><div><h1>Gestionar mesas</h1><p>Consultá, creá, modificá, desactivá y reactivá las mesas del bar.</p></div><a class="primario con-icono" href="{{ route('admin.mesas.create') }}"><x-icono nombre="mas" tamano="18" /> Nueva mesa</a></div>
<div class="tarjeta tabla">
<table>
    <thead><tr><th scope="col">Mesa</th><th scope="col">Estado</th><th scope="col">Mozo asignado</th><th scope="col">Personas</th><th scope="col">Acciones</th></tr></thead>
    <tbody>
    @forelse($mesas as $mesa)
        <tr>
            <th scope="row">{{ $mesa->numero }}</th>
            <td>@if ($mesa->activa)<span class="estado {{ $mesa->estado }}">{{ $mesa->estado_texto }}</span>@else<span style="color: #6c757d; font-weight: 600;">Inactiva</span>@endif</td>
            <td>{{ $mesa->mozo?->name ?? 'Sin asignar' }}</td><td>{{ $mesa->cantidad_personas }}</td>
            <td><div class="acciones">
                <a href="{{ route('admin.mesas.show', $mesa) }}" class="con-icono"><x-icono nombre="ver" tamano="16" /> Consultar</a>
                <a href="{{ route('admin.mesas.edit', $mesa) }}" class="con-icono"><x-icono nombre="editar" tamano="16" /> Editar</a>
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
            </div></td>
        </tr>
    @empty
        <tr><td colspan="5">No hay mesas registradas. Usá “Nueva mesa” para comenzar.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
