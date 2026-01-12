<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso total al sistema'
            ],
            [
                'nombre' => 'Cajero',
                'descripcion' => 'Manejo de caja, ventas y comprobantes'
            ],
            [
                'nombre' => 'Mesero',
                'descripcion' => 'Toma de pedidos y atención de mesas'
            ],
            [
                'nombre' => 'Cocinero',
                'descripcion' => 'Preparación de órdenes'
            ]
        ];

        foreach ($roles as $rol) {
            Rol::create($rol);
        }

        $this->command->info('Roles creados correctamente');
    }
}
