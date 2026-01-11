<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleOrden extends Model
{
    use HasFactory;
    protected $table = 'detalles_ordenes';

    protected $fillable = [
        'orden_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'notas',
        'estado'
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2'
        ];
    }

    /// Relacion

    public function orden(){
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    public function producto(){
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /// SCOPES (Consultas reutilizables)    

    ///Detalles pendientes de preparar
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    ///Detalles en preparacion
    public function scopePreparando($query)
    {
        return $query->where('estado', 'preparando');
    }
}
