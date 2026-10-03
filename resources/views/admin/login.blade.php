@extends('admin.layout')
@section('title', 'Login de administrador')
@section('content')
<section class="card" aria-labelledby="login-title">
<p class="badge">Acceso de administrador</p><h1 id="login-title">Bienvenido a Punto Bar</h1>
<p>Ingresá tu usuario y contraseña para acceder al panel.</p>
@if($errors->any())
<div class="error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
@endif
<form method="POST" action="{{ route('admin.login.store') }}">
@csrf
<label for="username">Usuario</label>
<input id="username" name="username" type="text" value="{{ old('username') }}" maxlength="50" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
<label for="password">Contraseña</label>
<input id="password" name="password" type="password" maxlength="255" autocomplete="current-password" required>
<label class="show"><input id="show-password" type="checkbox">Mostrar contraseña</label>
<button type="submit">Ingresar al panel</button>
</form>
</section>
<a class="back" href="{{ route('home') }}">Volver al inicio</a>
@push('scripts')
<script>document.getElementById('show-password').addEventListener('change',function(){document.getElementById('password').type=this.checked?'text':'password';});</script>
@endpush
@endsection
