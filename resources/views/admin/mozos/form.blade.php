@if($errors->any())
    <div class="tarjeta" role="alert">
        <p><strong>Revisá los siguientes datos:</strong></p>

        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mozo-campos">
    <div>
        <label for="name">Nombre y apellido</label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', isset($mozo) ? $mozo->name : '') }}"
            maxlength="255"
            autocomplete="name"
            required
        >
    </div>

    <div>
<label for="username">DNI (usuario de acceso)</label>        <input
            type="text"
            id="username"
            name="username"
                        placeholder="Ej: 40123456"

            value="{{ old('username', isset($mozo) ? $mozo->username : '') }}"
            minlength="3"
            maxlength="50"
            autocomplete="off"
            autocapitalize="none"
            spellcheck="false"
            required
        >
<small>Ingresá el DNI del mozo, sin puntos ni espacios.</small>    </div>

    <div>
        <label for="email">Correo electrónico</label>
        <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email', isset($mozo) ? $mozo->email : '') }}"
            maxlength="255"
            autocomplete="email"
            required
        >
    </div>

    <div>
        <label for="password">
            {{ isset($mozo) ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
        </label>

        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            maxlength="255"
            autocomplete="new-password"
            @required(!isset($mozo))
        >

        <small>
            @if(isset($mozo))
                Dejá este campo vacío para conservar la contraseña actual.
            @else
                Usá al menos 8 caracteres.
            @endif
        </small>
    </div>

    <div>
        <label for="password_confirmation">Confirmar contraseña</label>
        <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            minlength="8"
            maxlength="255"
            autocomplete="new-password"
            @required(!isset($mozo))
        >
    </div>

    @isset($mozo)
        <div>
            <label for="estado">Estado</label>

            <select id="estado" name="estado" required>
                <option
                    value="activo"
                    @selected(old('estado', $mozo->estado) === 'activo')
                >
                    Activo
                </option>

                <option
                    value="inactivo"
                    @selected(old('estado', $mozo->estado) === 'inactivo')
                >
                    Inactivo
                </option>
            </select>

            <small>Seleccioná Activo para reactivar un mozo dado de baja.</small>
        </div>
    @endisset
</div>

<div class="acciones mozo-botones">
    <button type="submit" class="primario con-icono">
        <x-icono :nombre="isset($mozo) ? 'guardar' : 'mas'" tamano="18" />
        {{ isset($mozo) ? 'Guardar cambios' : 'Crear mozo' }}
    </button>

    <a href="{{ route('admin.mozos.index') }}" class="con-icono"><x-icono nombre="quitar" tamano="16" /> Cancelar</a>
</div>

<style>
    .mozo-campos {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 20px;
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
    }

    .mozo-campos > div {
        min-width: 0;
    }

    .mozo-campos label {
        display: block;
        margin-bottom: 8px;
        color: #f1e9df;
        font-size: 14px;
        font-weight: 600;
    }

    .mozo-campos input,
    .mozo-campos select {
        box-sizing: border-box;
        width: 100%;
        min-height: 46px;
        padding: 12px 14px;
        border: 1px solid #635442;
        border-radius: 8px;
        background: #12100d;
        color: #f1e9df;
        font: inherit;
    }

    .mozo-campos input:focus,
    .mozo-campos select:focus {
        border-color: #f09b58;
        outline: 2px solid #f09b58;
        outline-offset: 2px;
    }

    .mozo-campos small {
        display: block;
        margin-top: 7px;
        color: #b8aea0;
        font-size: 13px;
        line-height: 1.4;
    }

    .mozo-botones {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        width: 100%;
        max-width: 600px;
        margin: 28px auto 0;
    }

    .mozo-botones button,
    .mozo-botones a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        min-height: 44px;
        padding: 12px 22px;
        border: 1px solid #80634e;
        border-radius: 8px;
        background: #211812;
        color: #f1e9df;
        font: inherit;
        text-decoration: none;
        cursor: pointer;
    }

    .mozo-botones button.primario {
        border-color: #f09b58;
        background: #f09b58;
        color: #1c1510;
        font-weight: 700;
    }

    .mozo-botones button.primario:hover {
        border-color: #ffad6a;
        background: #ffad6a;
    }

    .mozo-botones a:hover {
        border-color: #f09b58;
        color: #f09b58;
    }

    @media (max-width: 600px) {
        .mozo-botones button,
        .mozo-botones a {
            width: 100%;
        }
    }
</style>