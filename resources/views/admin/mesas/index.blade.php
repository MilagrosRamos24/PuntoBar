@extends('admin.mesas.layout')
@section('title', 'Gestionar mesas')
@section('content')
<div class="encabezado"><div><h1>Gestionar mesas</h1><p>Consultá, creá, modificá y eliminá las mesas del bar.</p></div><a class="primario" href="{{ route('admin.mesas.create') }}">+ Nueva mesa</a></div>
<div class="tarjeta tabla">
<table>
    <thead><tr><th scope="col">Mesa</th><th scope="col">Estado</th><th scope="col">Mozo asignado</th><th scope="col">Personas</th><th scope="col">Acciones</th></tr></thead>
    <tbody>
    @forelse($mesas as $mesa)
        <tr>
            <th scope="row">{{ $mesa->numero }}</th>
            <td><span class="estado {{ $mesa->estado }}">{{ $mesa->estado_texto }}</span></td>
            <td>{{ $mesa->mozo?->name ?? 'Sin asignar' }}</td><td>{{ $mesa->cantidad_personas }}</td>
            <td><div class="acciones">
                <a href="{{ route('admin.mesas.show', $mesa) }}">Consultar</a>
                <a href="{{ route('admin.mesas.edit', $mesa) }}">Editar</a>
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
