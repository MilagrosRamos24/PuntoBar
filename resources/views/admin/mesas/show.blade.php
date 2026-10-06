@extends('admin.mesas.layout')
@section('title', 'Consultar mesa')
@section('content')
<section class="tarjeta formulario">
    <h1>Mesa {{ $mesa->numero }}</h1>
    <dl>
        <dt>Estado</dt><dd><span class="estado {{ $mesa->estado }}">{{ $mesa->estado_texto }}</span></dd>
        <dt>Mozo asignado</dt><dd>{{ $mesa->mozo?->name ?? 'Sin asignar' }}</dd>
        <dt>Cantidad de personas</dt><dd>{{ $mesa->cantidad_personas }}</dd>
    </dl>
    <div class="acciones">
        <a href="{{ route('admin.mesas.index') }}">Volver al listado</a>
        <a class="primario" href="{{ route('admin.mesas.edit', $mesa) }}">Editar</a>
        <form action="{{ route('admin.mesas.destroy', $mesa) }}" method="POST" onsubmit="return confirm('¿Eliminar esta mesa? Esta acción no se puede deshacer.');">
            @csrf @method('DELETE')<button class="peligro">Eliminar</button>
        </form>
    </div>
</section>
@endsection
