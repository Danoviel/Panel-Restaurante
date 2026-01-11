<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mesa extends Model{
    use HasFactory;
    protected $table = 'mesas';

    protected $fillable = [
        'numero',
        'capacidad',
        'ubicacion',
        'activo'
    ];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
        ];
    }

    /// Relacion

    public function ordenes(){
        return $this->hasMany(Orden::class, 'mesa_id');
    }

     public function ordenActiva()
    {
        return $this->hasOne(Orden::class, 'mesa_id')
                    ->whereIn('estado', ['pendiente', 'en_preparacion', 'servido'])
                    ->latest();
    }
    
    /// SCOPES (Consultas reutilizables)

    ///Mesas libres
    public function scopeLibres($query)
    {
        return $query->where('estado', 'libre');
    }

    
    ///Mesas ocupadas
    public function scopeOcupadas($query)
    {
        return $query->where('estado', 'ocupada');
    }


}
