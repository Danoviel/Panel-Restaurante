<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    use HasFactory;
    protected $table = 'comprobantes';

    protected $fillable = [
        'orden_id',
        'tipo',
        'serie',
        'numero',
        'cliente_documento',
        'cliente_nombre',
        'cliente_direccion',
        'subtotal',
        'igv',
        'total',
        'metodo_pago',
        'estado',
        'motivo_anulacion',
        'ruta_pdf',
        'anulado_at'
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'anulado_at' => 'datetime'
        ];
    }

    /// Relacion

    public function orden(){
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    /// SCOPES (Consultas reutilizables)

    ///Comprobantes emitidos
    public function scopeEmitidos($query)
    {
        return $query->where('estado', 'emitido');
    }

    ///Comprobantes anulados
    public function scopeAnulados($query)
    {
        return $query->where('estado', 'anulado');
    }

    ///Boletas
    public function scopeBoletas($query)
    {
        return $query->where('tipo', 'boleta');
    }

    ///Facturas
    public function scopeFacturas($query)
    {
        return $query->where('tipo', 'factura');
    }

    ///Comprobantes del día
    public function scopeDelDia($query)
    {
        return $query->whereDate('created_at', today());
    }
}
