<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Mesa;;

class MesaSeeder extends Seeder
{
    public function run(): void
    {
        // Crear 10 mesas del salón principal
        for ($i = 1; $i <= 10; $i++) {
            Mesa::create([
                'numero' => (string)$i,
                'capacidad' => rand(2, 6), // Capacidad entre 2 y 6 personas
                'ubicacion' => 'Salón principal',
                'estado' => 'libre'
            ]);
        }

        // Crear 3 mesas de la terraza
        for ($i = 11; $i <= 13; $i++) {
            Mesa::create([
                'numero' => (string)$i,
                'capacidad' => 4,
                'ubicacion' => 'Terraza',
                'estado' => 'libre'
            ]);
        }

        $this->command->info('Mesas creadas correctamente');
    }
}
