<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminMozoController extends Controller
{
    /**
     * Listar mozos activos e inactivos.
     */
    public function index()
    {
        $mozos = User::where('role', 'mozo')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10);

        return view('admin.mozos.index', compact('mozos'));
    }

    /**
     * Mostrar el formulario de alta.
     */
    public function create()
    {
        return view('admin.mozos.create');
    }

    /**
     * Guardar un mozo nuevo.
     */
    public function store(Request $request)
    {
        $this->normalizarDatos($request);

        $datos = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email'),
                ],
                'username' => [
                    'required',
                    'string',
                    'min:3',
                    'max:50',
                    'regex:/^[a-z0-9_.-]+$/',
                    Rule::unique('users', 'username'),
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                    'confirmed',
                ],
            ],
            $this->mensajes()
        );

        $mozo = new User();
        $mozo->name = $datos['name'];
        $mozo->email = $datos['email'];
        $mozo->username = $datos['username'];

        // El cast "hashed" de User cifra la contraseña al asignarla.
        $mozo->password = $datos['password'];

        // Estos valores los decide el servidor.
        $mozo->role = 'mozo';
        $mozo->estado = 'activo';
        $mozo->save();

        return redirect()
            ->route('admin.mozos.index')
            ->with('success', 'Mozo creado correctamente.');
    }

    /**
     * Mostrar el formulario de edición.
     */
    public function edit(User $mozo)
    {
        $this->verificarMozo($mozo);

        return view('admin.mozos.edit', compact('mozo'));
    }

    /**
     * Guardar los cambios de un mozo.
     */
    public function update(Request $request, User $mozo)
    {
        $this->verificarMozo($mozo);
        $this->normalizarDatos($request);

        $datos = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($mozo),
                ],
                'username' => [
                    'required',
                    'string',
                    'min:3',
                    'max:50',
                    'regex:/^[a-z0-9_.-]+$/',
                    Rule::unique('users', 'username')->ignore($mozo),
                ],
                'password' => [
                    'nullable',
                    'string',
                    'min:8',
                    'max:255',
                    'confirmed',
                ],
                'estado' => [
                    'required',
                    Rule::in(['activo', 'inactivo']),
                ],
            ],
            $this->mensajes()
        );

        $mozo->name = $datos['name'];

        if ($mozo->email !== $datos['email']) {
            $mozo->email_verified_at = null;
        }

        $mozo->email = $datos['email'];
        $mozo->username = $datos['username'];
        $mozo->estado = $datos['estado'];

        // Si la contraseña queda vacía, conserva la anterior.
        if ($request->filled('password')) {
            $mozo->password = $datos['password'];
        }

        $mozo->save();

        return redirect()
            ->route('admin.mozos.index')
            ->with('success', 'Datos del mozo actualizados correctamente.');
    }

    /**
     * Baja lógica: conservar el registro y cambiar su estado.
     */
    public function destroy(User $mozo)
    {
        $this->verificarMozo($mozo);

        if ($mozo->estado === 'inactivo') {
            return redirect()
                ->route('admin.mozos.index')
                ->with('success', 'El mozo ya estaba dado de baja.');
        }

        $mozo->estado = 'inactivo';
        $mozo->save();

        return redirect()
            ->route('admin.mozos.index')
            ->with('success', 'Mozo dado de baja correctamente.');
    }
    /**
     * * Reactivar un mozo conservando sus datos y contraseña. */
    public function habilitar(User $mozo)
    {
    $this->verificarMozo($mozo);
     $mozo->estado = 'activo';
     $mozo->save();
     return redirect()
        ->route('admin.mozos.index')
        ->with('success', 'Mozo habilitado correctamente.');
        }
        
    /**
     * Impedir que este módulo modifique administradores.
     */
    private function verificarMozo(User $mozo): void
    {
        abort_unless($mozo->role === 'mozo', 404);
    }

    /**
     * Quitar espacios externos y normalizar el usuario de acceso.
     */
    private function normalizarDatos(Request $request): void
    {
        $datos = [];

        foreach (['name', 'email', 'username'] as $campo) {
            $valor = $request->input($campo);

            if (is_string($valor)) {
                $datos[$campo] = $campo === 'username'
                    ? Str::lower(trim($valor))
                    : trim($valor);
            }
        }

        $request->merge($datos);
    }

    /**
     * Mensajes de validación.
     */
    private function mensajes(): array
    {
        return [
            'name.required' => 'Ingresá el nombre del mozo.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'Ingresá un correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'email.max' => 'El correo no puede superar los 255 caracteres.',
            'email.unique' => 'Ese correo ya pertenece a otro usuario.',
            'username.required' => 'Ingresá un usuario de acceso.',
            'username.min' => 'El usuario debe tener al menos 3 caracteres.',
            'username.max' => 'El usuario no puede superar los 50 caracteres.',
            'username.regex' => 'Usá letras sin acentos, números, punto, guion o guion bajo.',
            'username.unique' => 'Ese usuario de acceso ya está registrado.',
            'password.required' => 'Ingresá una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no puede superar los 255 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'estado.required' => 'Seleccioná el estado del mozo.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ];
    }
}