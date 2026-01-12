<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ConfiguracionNegocio;

class ConfiguracionNegocioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConfiguracionNegocio::create([
            'nombre_comercial' => 'Restaurante Demo',
            'ruc' => '20123456789',
            'direccion' => 'Av. Principal 123, Lima',
            'telefono' => '01-1234567',
            'email' => 'info@restaurante.com',
            'tipo_restaurante' => 'casual',
            'emite_boletas' => true,
            'serie_boleta' => 'B001',
            'numero_actual_boleta' => 0,
            'emite_facturas' => true,
            'serie_factura' => 'F001',
            'numero_actual_factura' => 0,
            'porcentaje_igv' => 18.00,
            'incluye_servicio' => false,
            'porcentaje_servicio' => 10.00,
            'usa_mesas' => true,
            'usa_reservas' => false,
            'usa_clientes_frecuentes' => false,
            'usa_delivery' => true,
            'usa_para_llevar' => true,
            'configuracion_completada' => false, 
            'moneda' => 'PEN',
            'zona_horaria' => 'America/Lima'
        ]);

        $this->command->info('Configuración del negocio creada');
    }
}
