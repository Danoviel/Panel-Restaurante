<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionNegocio extends Model
{
    use HasFactory;

    protected $table = 'configuracion_negocio';

    protected $fillable = [
        'nombre_comercial',
        'ruc',
        'direccion',
        'telefono',
        'email',
        'logo_path',
        'tipo_restaurante',
        'emite_boletas',
        'serie_boleta',
        'numero_actual_boleta',
        'emite_facturas',
        'serie_factura',
        'numero_actual_factura',
        'porcentaje_igv',
        'incluye_servicio',
        'porcentaje_servicio',
        'usa_mesas',
        'usa_reservas',
        'usa_clientes_frecuentes',
        'usa_programa_puntos',
        'usa_delivery',
        'usa_para_llevar',
        'configuracion_completada',
        'moneda',
        'zona_horaria'
    ];

    protected function casts(): array
    {
        return [
            'emite_boletas' => 'boolean',
            'emite_facturas' => 'boolean',
            'incluye_servicio' => 'boolean',
            'usa_mesas' => 'boolean',
            'usa_reservas' => 'boolean',
            'usa_clientes_frecuentes' => 'boolean',
            'usa_programa_puntos' => 'boolean',
            'usa_delivery' => 'boolean',
            'usa_para_llevar' => 'boolean',
            'configuracion_completada' => 'boolean',
            'numero_actual_boleta' => 'integer',
            'numero_actual_factura' => 'integer',
            'porcentaje_igv' => 'decimal:2',
            'porcentaje_servicio' => 'decimal:2'
        ];
    }

    // MÉTODOS HELPER

    // Obtener la configuración del negocio
    public static function obtenerConfiguracion()
    {
        return self::first();
    }

    // Verificar si la configuración del negocio está completa
    public static function estaConfigurado()
    {
        $config = self::first();
        return $config && $config->configuracion_completada;
    }
}
