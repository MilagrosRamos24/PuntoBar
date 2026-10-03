@auth
    <form method="POST"
          action="{{ auth()->user()->role === 'admin' ? route('admin.logout') : route('mozo.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endauth
