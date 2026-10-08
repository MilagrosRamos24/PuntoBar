@extends('admin.mesas.layout')

@section('title', 'Nuevo mozo')

@section('content')
<div class="encabezado">
    <div>
        <h1>Nuevo mozo</h1>
        <p>Completá los datos. El mozo se registrará como activo.</p>
    </div>
</div>

<div class="tarjeta">
    <form method="POST" action="{{ route('admin.mozos.store') }}">
        @csrf

        @include('admin.mozos.form')
    </form>
</div>
@endsection