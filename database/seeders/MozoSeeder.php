<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MozoSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Mozo Prueba',
            'email' => 'mozo@puntobar.local',
            'username' => '45758917',
            'password' => Hash::make('45758917'),
            'role' => 'mozo',
            'estado' => 'activo',
        ]);
    }
}