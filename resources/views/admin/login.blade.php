@extends('admin.layout')
@section('title', 'Login de administrador')
@section('content')
<style>
    main {
        width: min(92%, 440px);
        padding: 10px 0 20px;
    }

    .logo {
        width: min(100%, 190px);
    }

    .card {
        padding: 22px;
    }

    h1 {
        font-size: 22px;
        margin-bottom: 6px;
    }

    p {
        font-size: 14px;
        line-height: 1.4;
        margin: 8px 0;
    }

    .badge {
        font-size: 12px;
    }

    label {
        font-size: 14px;
        margin: 12px 0 6px;
    }

    input {
        padding: 10px 12px;
        font-size: 15px;
        border-radius: 8px;
    }

    .show {
        margin-top: 12px;
    }

    button {
        padding: 11px;
        font-size: 15px;
        margin-top: 16px;
    }

    .back {
        margin-top: 14px;
        font-size: 14px;
    }

    @media (max-width: 420px) {
        .card {
            padding: 18px;
        }
    }
</style>
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
<script>document.getElementById('show-password').addEventListener('change',function(){document.getElementById('password').type=this.checked?'text':'password';});</script>
@endsection
