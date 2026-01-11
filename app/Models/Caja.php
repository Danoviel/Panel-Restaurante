<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;
    protected $table = 'cajas';

    protected $fillable = [
        'usuario_id',
        'fecha_apertura',
        'fecha_cierre',
        'monto_inicial',
        'monto_esperado',
        'monto_real',
        'diferencia',
        'notas',
        'estado'
    ];

    protected function casts(): array
    {
        return [
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
            'monto_inicial' => 'decimal:2',
            'monto_esperado' => 'decimal:2',
            'monto_real' => 'decimal:2',
            'diferencia' => 'decimal:2'
        ];
    }

    /// Relacion

    public function usuario(){
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /// SCOPES (Consultas reutilizables)

    ///Cajas abiertas
    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'abierta');
    }

    ///Cajas cerradas
    public function scopeCerradas($query)
    {
        return $query->where('estado', 'cerrada');
    }

    ///Caja activa por usuario
    public function scopeCajaActiva($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId)
                    ->where('estado', 'abierta')
                    ->latest();
    }
}
