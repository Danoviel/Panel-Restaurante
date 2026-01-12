<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Categoria;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener IDs de categorías
        $entradas = Categoria::where('nombre', 'Entradas')->first();
        $platosFondo = Categoria::where('nombre', 'Platos de fondo')->first();
        $bebidas = Categoria::where('nombre', 'Bebidas')->first();
        $postres = Categoria::where('nombre', 'Postres')->first();

        $productos = [
            // ENTRADAS (Preparados)
            [
                'categoria_id' => $entradas->id,
                'nombre' => 'Tequeños',
                'descripcion' => '6 unidades con salsa golf',
                'precio_venta' => 12.00,
                'tipo_producto' => 'preparado',
                'activo' => true
            ],
            [
                'categoria_id' => $entradas->id,
                'nombre' => 'Causa limeña',
                'descripcion' => 'Papa amarilla rellena de pollo',
                'precio_venta' => 15.00,
                'tipo_producto' => 'preparado',
                'activo' => true
            ],

            // PLATOS DE FONDO (Preparados)
            [
                'categoria_id' => $platosFondo->id,
                'nombre' => 'Lomo saltado',
                'descripcion' => 'Carne, papas fritas y arroz',
                'precio_venta' => 25.00,
                'tipo_producto' => 'preparado',
                'activo' => true
            ],
            [
                'categoria_id' => $platosFondo->id,
                'nombre' => 'Ceviche',
                'descripcion' => 'Pescado fresco, limón, camote y choclo',
                'precio_venta' => 30.00,
                'tipo_producto' => 'preparado',
                'activo' => true
            ],
            [
                'categoria_id' => $platosFondo->id,
                'nombre' => 'Ají de gallina',
                'descripcion' => 'Pollo deshilachado en crema, arroz y papa',
                'precio_venta' => 22.00,
                'tipo_producto' => 'preparado',
                'activo' => true
            ],

            // BEBIDAS (Comprados)
            [
                'categoria_id' => $bebidas->id,
                'nombre' => 'Inca Kola 500ml',
                'descripcion' => 'Gaseosa',
                'precio_compra' => 2.50,
                'precio_venta' => 5.00,
                'stock_actual' => 50,
                'stock_minimo' => 10,
                'unidad_medida' => 'unidad',
                'sku' => 'BEB-IK-500',
                'tipo_producto' => 'comprado',
                'activo' => true
            ],
            [
                'categoria_id' => $bebidas->id,
                'nombre' => 'Coca Cola 500ml',
                'descripcion' => 'Gaseosa',
                'precio_compra' => 2.50,
                'precio_venta' => 5.00,
                'stock_actual' => 45,
                'stock_minimo' => 10,
                'unidad_medida' => 'unidad',
                'sku' => 'BEB-CC-500',
                'tipo_producto' => 'comprado',
                'activo' => true
            ],
            [
                'categoria_id' => $bebidas->id,
                'nombre' => 'Cerveza Pilsen',
                'descripcion' => 'Cerveza 330ml',
                'precio_compra' => 4.00,
                'precio_venta' => 8.00,
                'stock_actual' => 30,
                'stock_minimo' => 15,
                'unidad_medida' => 'unidad',
                'sku' => 'BEB-PIL-330',
                'tipo_producto' => 'comprado',
                'activo' => true
            ],
            [
                'categoria_id' => $bebidas->id,
                'nombre' => 'Chicha morada',
                'descripcion' => 'Jarra 1 litro',
                'precio_venta' => 8.00,
                'tipo_producto' => 'preparado',
                'activo' => true
            ],

            // POSTRES (Preparados)
            [
                'categoria_id' => $postres->id,
                'nombre' => 'Suspiro limeño',
                'descripcion' => 'Dulce de leche con merengue',
                'precio_venta' => 10.00,
                'tipo_producto' => 'preparado',
                'activo' => true
            ],
            [
                'categoria_id' => $postres->id,
                'nombre' => 'Mazamorra morada',
                'descripcion' => 'Con arroz con leche',
                'precio_venta' => 8.00,
                'tipo_producto' => 'preparado',
                'activo' => true
            ]
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }

        $this->command->info('Productos creados correctamente');
    }
}
