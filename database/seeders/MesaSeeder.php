<?php

namespace Database\Seeders;

use App\Models\Mesa;
use Illuminate\Database\Seeder;

class MesaSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Mesa::updateOrCreate(
                ['numero' => $i],
                [
                    'estado' => 'libre',
                    'mozo_id' => null,
                    'cantidad_personas' => 0,
                ]
            );
        }
    }
}