<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    //Listar tofos los productos activos
    public function index(Request $request)
    {
        try {
            $query = Producto::with('categoria')->where('activo', true);

            // Filtrar por categoría si viene el parámetro
            if ($request->has('categoria_id')) {
                $query->where('categoria_id', $request->categoria_id);
            }

            // Filtrar por tipo de producto
            if ($request->has('tipo_producto')) {
                $query->where('tipo_producto', $request->tipo_producto);
            }

            $productos = $query->orderBy('nombre', 'asc')->get();

            return response()->json([
                'success' => true,
                'productos' => $productos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Crear un nuevo producto
    public function store(Request $request)
    {
        try {
            // Validación base
            $rules = [
                'categoria_id' => 'required|exists:categorias,id',
                'nombre' => 'required|string|max:150',
                'descripcion' => 'nullable|string',
                'precio_venta' => 'required|numeric|min:0',
                'tipo_producto' => 'required|in:preparado,comprado',
                'activo' => 'nullable|boolean'
            ];

            // Validaciones adicionales para productos comprados
            if ($request->tipo_producto === 'comprado') {
                $rules['precio_compra'] = 'required|numeric|min:0';
                $rules['stock_actual'] = 'required|integer|min:0';
                $rules['stock_minimo'] = 'required|integer|min:0';
                $rules['unidad_medida'] = 'required|string|max:20';
                $rules['sku'] = 'nullable|string|max:50|unique:productos,sku';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Crear producto
            $producto = Producto::create([
                'categoria_id' => $request->categoria_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio_venta' => $request->precio_venta,
                'tipo_producto' => $request->tipo_producto,
                'activo' => $request->activo ?? true,
                // Campos opcionales para productos comprados
                'precio_compra' => $request->precio_compra,
                'stock_actual' => $request->stock_actual,
                'stock_minimo' => $request->stock_minimo,
                'unidad_medida' => $request->unidad_medida,
                'sku' => $request->sku,
            ]);

            $producto->load('categoria');

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'producto' => $producto
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Obtener un producto por ID
    public function show($id)
    {
        try {
            $producto = Producto::with('categoria')->find($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'producto' => $producto
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Actualizar un producto existente
    public function update(Request $request, $id)
    {
        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Validación base
            $rules = [
                'categoria_id' => 'required|exists:categorias,id',
                'nombre' => 'required|string|max:150',
                'descripcion' => 'nullable|string',
                'precio_venta' => 'required|numeric|min:0',
                'tipo_producto' => 'required|in:preparado,comprado',
                'activo' => 'nullable|boolean'
            ];

            // Validaciones adicionales para productos comprados
            if ($request->tipo_producto === 'comprado') {
                $rules['precio_compra'] = 'required|numeric|min:0';
                $rules['stock_actual'] = 'required|integer|min:0';
                $rules['stock_minimo'] = 'required|integer|min:0';
                $rules['unidad_medida'] = 'required|string|max:20';
                $rules['sku'] = 'nullable|string|max:50|unique:productos,sku,' . $id;
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar producto
            $producto->update([
                'categoria_id' => $request->categoria_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio_venta' => $request->precio_venta,
                'tipo_producto' => $request->tipo_producto,
                'activo' => $request->activo ?? $producto->activo,
                'precio_compra' => $request->precio_compra,
                'stock_actual' => $request->stock_actual,
                'stock_minimo' => $request->stock_minimo,
                'unidad_medida' => $request->unidad_medida,
                'sku' => $request->sku,
            ]);

            $producto->load('categoria');

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'producto' => $producto
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    //Eliminar un producto (lógica de desactivación)
    public function destroy($id)
    {
        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Verificar si tiene órdenes asociadas
            $ordenesCount = $producto->detalleOrdenes()->count();

            if ($ordenesCount > 0) {
                // No eliminar, solo desactivar
                $producto->update(['activo' => false]);
                return response()->json([
                    'success' => true,
                    'message' => 'Producto desactivado (tiene historial de ventas)'
                ]);
            }

            // Si no tiene historial, marcar como inactivo
            $producto->update(['activo' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Producto desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Obtener producto con bajo stock
    public function stockBajo()
    {
        try {
            $productos = Producto::where('tipo_producto', 'comprado')
                ->whereNotNull('stock_minimo')
                ->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->where('activo', true)
                ->with('categoria')
                ->get();

            return response()->json([
                'success' => true,
                'productos' => $productos,
                'total' => $productos->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos con stock bajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Actualizar stock de un producto comprado
    public function actualizarStock(Request $request, $id)
    {
        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            if ($producto->tipo_producto !== 'comprado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede actualizar stock de productos comprados'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'stock_actual' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $producto->update([
                'stock_actual' => $request->stock_actual
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock actualizado exitosamente',
                'producto' => $producto
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
