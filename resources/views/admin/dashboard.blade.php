@extends('admin.layout')
@section('title', 'Panel de administración')
@section('shell-class', 'pb-shell--panel')
@section('content')
<section class="card">
<p class="badge">Administración</p><h1>Hola, {{ auth()->user()->name }}</h1>
<p>Ingresaste al panel de administración de Punto Bar.</p>
<div class="modules">
<div class="module"><strong>Mesas</strong><p>Gestión de mesas · Próximamente</p></div>
<div class="module"><strong>Mozos</strong><p>Gestión del personal · Próximamente</p></div>
<div class="module"><strong>Comandas</strong><p>Seguimiento de pedidos · Próximamente</p></div>
</div>
<form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">Cerrar sesión</button></form>
</section>
@endsection
