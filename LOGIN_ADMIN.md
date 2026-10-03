# PB-01 — Login de administrador

Implementado sobre PuntoBar(1).rar. home.blade.php se conserva exactamente como fue recibido. Se agregan el formulario, validación contra users con contraseña hasheada, redirección a /admin/panel y mensaje de credenciales incorrectas. El panel inicial muestra módulos pendientes, sin implementar sus operaciones.

## Integración en tu copia actual

Copiá app/Http/Controllers/AdminLoginController.php, app/Http/Middleware/EnsureAdministrator.php, app/Console/Commands/CreateAdministrator.php, resources/views/admin/, la nueva migración y tests/Feature/AdminLoginTest.php. Reemplazá routes/web.php solamente si todavía contiene únicamente la ruta de inicio; si tus compañeras agregaron rutas, incorporá las nuevas rutas conservando las de ellas. No reemplaces tu .env, tu .git ni home.blade.php.

Con MySQL iniciado, la base creada y tu .env configurado, ejecutá desde la raíz:

```powershell
composer install
php artisan optimize:clear
php artisan migrate
php artisan admin:crear
php artisan serve
```

El comando admin:crear pregunta usuario, nombre, correo y contraseña con confirmación; no trae contraseña predeterminada ni modifica cuentas existentes. Podés usar admin como usuario y elegir tu contraseña. El usuario se guarda en minúsculas. Los usuarios existentes mantienen acceso sin privilegios administrativos y username vacío. No ejecutes migrate:fresh: no hace falta eliminar datos.

Abrí http://127.0.0.1:8000 y elegí Administrador, o ingresá directamente en http://127.0.0.1:8000/admin/login.

Si usás el paquete como instalación nueva: ejecutá composer install, copiá .env.example a .env, configurá DB_CONNECTION=mysql y tus datos DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD; ejecutá php artisan key:generate antes de los comandos restantes. El paquete no incluye .env ni dependencias instaladas.

## Verificación

```powershell
php artisan test --filter=AdminLoginTest
```

Incluye nueve pruebas de formulario, ingreso, credenciales inválidas, campos requeridos, rol, protección del panel, salida y límite de intentos. No fueron ejecutadas en el entorno de edición porque no dispone de PHP ni Composer. Probá también Mostrar contraseña y el diseño en pantalla pequeña desde tu navegador. Comprobá que la sesión permite entrar y que, después de salir, la URL del panel vuelve al login.

La contraseña no se repuebla tras un error. Los formularios incluyen CSRF; la sesión se regenera al autenticar y se invalida al salir. Tras cinco intentos fallidos por usuario/IP se espera hasta un minuto.

Referencia técnica: Laravel. (s. f.). Basic authentication: Login/Logout. https://laravel.com/learn/getting-started-with-laravel/basic-authentication-loginlogout
