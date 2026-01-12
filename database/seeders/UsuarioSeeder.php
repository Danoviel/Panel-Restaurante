<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el rol de Administrador
        $rolAdmin = Rol::where('nombre', 'Administrador')->first();

        // Crear usuario administrador
        User::create([
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'email' => 'admin@restaurante.com',
            'telefono' => '999888777',
            'password' => Hash::make('admin123'), // Contraseña encriptada
            'rol_id' => $rolAdmin->id,
            'activo' => true
        ]);

        // Crear un cajero de prueba
        $rolCajero = Rol::where('nombre', 'Cajero')->first();
        
        User::create([
            'nombre' => 'María',
            'apellido' => 'López',
            'email' => 'cajero@restaurante.com',
            'telefono' => '999777666',
            'password' => Hash::make('cajero123'),
            'rol_id' => $rolCajero->id,
            'activo' => true
        ]);

        // Crear un mesero de prueba
        $rolMesero = Rol::where('nombre', 'Mesero')->first();
        
        User::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'mesero@restaurante.com',
            'telefono' => '999666555',
            'password' => Hash::make('mesero123'),
            'rol_id' => $rolMesero->id,
            'activo' => true
        ]);

        $this->command->info('Usuarios creados correctamente');
        $this->command->info('Admin: admin@restaurante.com / admin123');
        $this->command->info('Cajero: cajero@restaurante.com / cajero123');
        $this->command->info('Mesero: mesero@restaurante.com / mesero123');
    }
}
