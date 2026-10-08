@extends('admin.mesas.layout')

@section('title', 'Editar mozo')

@section('content')
<div class="encabezado">
    <div>
        <h1>Editar mozo</h1>
        <p>Modificá los datos de {{ $mozo->name }}.</p>
    </div>
</div>

<div class="tarjeta">
    <form
        method="POST"
        action="{{ route('admin.mozos.update', $mozo) }}"
    >
        @csrf
        @method('PUT')

        @include('admin.mozos.form')
    </form>
</div>
@endsection