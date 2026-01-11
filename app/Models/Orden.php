<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orden extends Model{
    
    use HasFactory;
    protected $table = 'ordenes';

    protected $fillable = [
        'mesa_id',
        'usuario_id',
        'estado',
        'subtotal',
        'descuento',
        'impuesto',
        'total',
        'tipo_servicio',
        'numero_personas',
        'notas',
        'pagado_at'
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'impuesto' => 'decimal:2',
            'total' => 'decimal:2',
            'numero_personas' => 'integer',
            'pagado_at' => 'datetime'
        ];
    }

    /// Relacion

    public function mesa(){
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }

    public function usuario(){
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleOrden::class, 'orden_id');
    }

    public function comprobante()
    {
        return $this->hasOne(Comprobante::class, 'orden_id');
    }

    /// SCOPES (Consultas reutilizables)

    ///Órdenes pendientes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    //
    public function scopeEnPreparacion($query)
    {
        return $query->where('estado', 'en_preparacion');
    }

    ///Órdenes activas
    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['pendiente', 'en_preparacion', 'servido']);
    }

    ///Órdenes del día
    public function scopeDelDia($query)
    {
        return $query->whereDate('created_at', today());
    }
}
