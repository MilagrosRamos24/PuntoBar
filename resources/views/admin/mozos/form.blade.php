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
        <label for="username">Usuario de acceso</label>
        <input
            type="text"
            id="username"
            name="username"
            value="{{ old('username', isset($mozo) ? $mozo->username : '') }}"
            minlength="3"
            maxlength="50"
            autocomplete="off"
            autocapitalize="none"
            spellcheck="false"
            required
        >
        <small>Podés usar un DNI o letras, números, punto y guiones.</small>
    </div>

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
    <button type="submit" class="primario">
        {{ isset($mozo) ? 'Guardar cambios' : 'Crear mozo' }}
    </button>

    <a href="{{ route('admin.mozos.index') }}">Cancelar</a>
</div>

<style>
    .mozo-campos {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .mozo-campos label {
        display: block;
        margin-bottom: 8px;
    }

    .mozo-campos input,
    .mozo-campos select {
        box-sizing: border-box;
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #635442;
        border-radius: 8px;
        background: #12110f;
        color: #eee9e1;
        font: inherit;
    }

    .mozo-campos input:focus,
    .mozo-campos select:focus {
        outline: 2px solid #e9a365;
        outline-offset: 2px;
    }

    .mozo-campos small {
        display: block;
        margin-top: 6px;
        color: #b8aea0;
        line-height: 1.4;
    }

    .mozo-botones {
        margin-top: 24px;
    }

    @media (max-width: 650px) {
        .mozo-campos {
            grid-template-columns: 1fr;
        }
    }
</style>