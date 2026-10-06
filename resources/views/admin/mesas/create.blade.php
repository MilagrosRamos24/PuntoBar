@extends('admin.mesas.layout')
@section('title', 'Nueva mesa')
@section('content')
<section class="tarjeta formulario">
    <h1>Nueva mesa</h1><p>Ingresá los datos de la mesa.</p>
    <form action="{{ route('admin.mesas.store') }}" method="POST">
        @csrf
        @include('admin.mesas.form')
    </form>
</section>
@endsection
