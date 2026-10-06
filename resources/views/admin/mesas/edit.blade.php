@extends('admin.mesas.layout')
@section('title', 'Editar mesa')
@section('content')
<section class="tarjeta formulario">
    <h1>Editar mesa {{ $mesa->numero }}</h1><p>Modificá el número, estado, mozo y cantidad de personas.</p>
    <form action="{{ route('admin.mesas.update', $mesa) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.mesas.form')
    </form>
</section>
@endsection
