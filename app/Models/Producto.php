<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model{
    
    use HasFactory;
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio_compra',
        'precio_venta',
        'stock_actual',
        'stock_minimo',
        'unidad_medida',
        'imagen',
        'sku',
        'tipo_producto',
        'activo'
    ];

    protected $appends = ['imagen_url'];

    protected function casts(): array
    {
        return [
            'precio_compra' => 'float',
            'precio_venta' => 'float',
            'stock_actual' => 'integer',
            'stock_minimo' => 'integer',
            'activo' => 'boolean'
        ];
    }

    // Accessor para URL de imagen
    public function getImagenUrlAttribute()
    {
        return $this->imagen ? url('storage/' . $this->imagen) : null;
    }

    /// Relacion

    public function categoria(){
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function detalleOrdenes(){
        return $this->hasMany(DetalleOrden::class, 'producto_id');
    }

    // SCOPES (Consultas reutilizables)

    
    ///Scope para obtener solo productos activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    
    ///Scope para obtener productos con stock baj
    public function scopeStockBajo($query)
    {
        return $query->whereNotNull('stock_minimo')
                    ->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    
    ///Scope para productos preparados
    public function scopePreparados($query)
    {
        return $query->where('tipo_producto', 'preparado');
    }

    
    ///Scope para productos comprados
    public function scopeComprados($query)
    {
        return $query->where('tipo_producto', 'comprado');
    }

}
