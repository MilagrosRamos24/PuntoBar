{{--
    Botón de cierre de sesión reutilizable.
    Uso en cualquier vista protegida: <x-cerrar-sesion />
    Elige la ruta de logout según el rol del usuario conectado.
--}}
@auth
    <form method="POST"
          action="{{ auth()->user()->role === 'admin' ? route('admin.logout') : route('mozo.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endauth


