<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar los seeders en orden
        $this->call([
            RolSeeder::class,
            UsuarioSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            MesaSeeder::class,
            ConfiguracionNegocioSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🎉 Base de datos poblada correctamente!');
        $this->command->info('');
    }
}