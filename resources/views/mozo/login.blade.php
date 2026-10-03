@extends('layouts.puntobar')
@section('title', 'Login de mozo')
@section('content')
    <section class="card" aria-labelledby="mozo-title">

        <p class="badge">Acceso de mozo</p>
        <h1 id="mozo-title">Bienvenido a Punto Bar</h1>

        <p class="descripcion">
            Seleccioná tu nombre e ingresá tu contraseña.
        </p>

        @if ($errors->any())
            <div class="error" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

      <form action="{{ route('mozo.login.process') }}" method="POST">
    @csrf

    <div class="campo">
        <label for="username">Mozo</label>

        <select name="username" id="username" required>
            <option value="">Seleccionar mozo</option>

            @foreach ($mozos as $mozo)
                <option
                    value="{{ $mozo->username }}"
                    {{ old('username') == $mozo->username ? 'selected' : '' }}
                >
                    {{ $mozo->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="campo">
        <label for="password">Contraseña</label>

        <input
            type="password"
            name="password"
            id="password"
            autocomplete="current-password"
            placeholder="Ingresá tu contraseña"
            required
        >
    </div>

    <label class="show"><input id="show-password" type="checkbox">Mostrar contraseña</label>

    <button type="submit" >
        Ingresar
    </button>
</form>

    </section>

        <a href="{{ route('home') }}" class="back">
            ← Volver
        </a>


@endsection
@push('scripts')
<script>document.getElementById('show-password').addEventListener('change',function(){document.getElementById('password').type=this.checked?'text':'password';});</script>
@endpush
