<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Categoria;;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Entradas',
                'descripcion' => 'Platos de entrada',
                'orden' => 1,
                'activo' => true
            ],
            [
                'nombre' => 'Platos de fondo',
                'descripcion' => 'Platos principales',
                'orden' => 2,
                'activo' => true
            ],
            [
                'nombre' => 'Bebidas',
                'descripcion' => 'Bebidas frías y calientes',
                'orden' => 3,
                'activo' => true
            ],
            [
                'nombre' => 'Postres',
                'descripcion' => 'Dulces y postres',
                'orden' => 4,
                'activo' => true
            ],
            [
                'nombre' => 'Extras',
                'descripcion' => 'Acompañamientos y extras',
                'orden' => 5,
                'activo' => true
            ]
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }

        $this->command->info('Categorías creadas correctamente');
    }
}
