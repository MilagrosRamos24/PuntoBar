<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class CreateAdministrator extends Command {
    protected $signature = 'admin:crear';
    protected $description = 'Crea un administrador de Punto Bar sin modificar usuarios existentes';
    public function handle(): int {
        $username = Str::lower(trim((string) $this->ask('Usuario (letras, números, guion o punto)')));
        $name = trim((string) $this->ask('Nombre del administrador'));
        $email = trim((string) $this->ask('Correo electrónico'));
        $password = (string) $this->secret('Contraseña (mínimo 8 caracteres)');
        $confirm = (string) $this->secret('Repetí la contraseña');
        if (! preg_match('/^[a-z0-9_.-]{3,50}$/', $username) || $name === '' || mb_strlen($name) > 255 || strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || strlen($password) > 255 || $password !== $confirm) {
            $this->error('Datos inválidos. Revisá usuario, nombre, correo y contraseñas.');
            return self::FAILURE;
        }
        if (User::where('username', $username)->orWhere('email', $email)->exists()) {
            $this->error('El usuario o correo ya existe. No se modificó ninguna cuenta.');
            return self::FAILURE;
        }
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->username = $username;
        $user->role = 'admin';
        $user->password = Hash::make($password);
        $user->save();
        $this->info('Administrador creado. Ingresá en /admin/login.');
        return self::SUCCESS;
    }
}
