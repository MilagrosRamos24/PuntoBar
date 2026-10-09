@extends('admin.mesas.layout')

@section('title', 'Gestionar mozos')

@section('content')
<div class="encabezado">
    <div>
        <h1>Gestionar mozos</h1>
        <p>Consultá, registrá, editá y da de baja los mozos del bar.</p>
    </div>

    <a class="primario" href="{{ route('admin.mozos.create') }}">
        + Nuevo mozo
    </a>
</div>

@if(session('success'))
    <div class="tarjeta" role="status">
        <p>{{ session('success') }}</p>
    </div>
@endif

<div class="tarjeta tabla">
    <table>
        <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Usuario</th>
                <th scope="col">Correo</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>

        <tbody>
            @forelse($mozos as $mozo)
                <tr>
                    <th scope="row">{{ $mozo->name }}</th>
                    <td>{{ $mozo->username }}</td>
                    <td>{{ $mozo->email }}</td>
                    <td>
                        {{ $mozo->estado === 'activo' ? 'Activo' : 'Inactivo' }}
                    </td>

                    <td>
                        <div class="acciones">
                            <a href="{{ route('admin.mozos.edit', $mozo) }}">
                                Editar
                            </a>

                            @if($mozo->estado === 'activo')
                            <form
                            action="{{ route('admin.mozos.destroy', $mozo) }}"
                            method="POST"
                            onsubmit="return confirm('¿Dar de baja a este mozo? Su historial se conservará.');"
                            >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="peligro">
                                 Dar de baja
                                </button>
                            </form>
                            @else
                            <form
                            action="{{ route('admin.mozos.habilitar', $mozo) }}"
                            method="POST"
                            >
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="primario">
                                 Habilitar
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        No hay mozos registrados. Usá “Nuevo mozo” para comenzar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($mozos->hasPages())
    <nav class="acciones" aria-label="Paginación de mozos">
        @if(!$mozos->onFirstPage())
            <a href="{{ $mozos->previousPageUrl() }}">Anterior</a>
        @endif

        <span>
            Página {{ $mozos->currentPage() }} de {{ $mozos->lastPage() }}
        </span>

        @if($mozos->hasMorePages())
            <a href="{{ $mozos->nextPageUrl() }}">Siguiente</a>
        @endif
    </nav>
@endif
@endsection