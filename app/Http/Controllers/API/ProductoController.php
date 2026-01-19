<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    //Listar todos los productos
    public function index(Request $request)
    {
        try {
            // Validar parámetros de filtro
            $validator = Validator::make($request->all(), [
                'categoria_id' => 'nullable|integer|exists:categorias,id',
                'tipo_producto' => 'nullable|in:preparado,comprado'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Producto::with('categoria')->where('activo', true);

            // Filtrar por categoría
            if ($request->filled('categoria_id')) {
                $query->where('categoria_id', $request->categoria_id);
            }

            // Filtrar por tipo de producto
            if ($request->filled('tipo_producto')) {
                $query->where('tipo_producto', $request->tipo_producto);
            }

            $productos = $query->orderBy('nombre', 'asc')->get();

            // Agregar URL completa de la imagen
            $productos->transform(function ($producto) {
                if ($producto->imagen) {
                    $producto->imagen_url = url('storage/' . $producto->imagen);
                } else {
                    $producto->imagen_url = null;
                }
                return $producto;
            });

            return response()->json([
                'success' => true,
                'productos' => $productos
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos'
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

            // Agregar URL completa de la imagen
            if ($producto->imagen) {
                $producto->imagen_url = url('storage/' . $producto->imagen);
            }

            return response()->json([
                'success' => true,
                'producto' => $producto
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener producto', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'producto_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener producto'
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
                'activo' => 'nullable|boolean',
                'imagen' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048' // Máx 2MB
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

            // Procesar imagen si existe
            $imagenPath = null;
            if ($request->hasFile('imagen')) {
                $imagenPath = $this->guardarImagen($request->file('imagen'));
            }

            // Crear producto
            $producto = Producto::create([
                'categoria_id' => $request->categoria_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio_venta' => $request->precio_venta,
                'tipo_producto' => $request->tipo_producto,
                'activo' => $request->activo ?? true,
                'imagen' => $imagenPath,
                // Campos para productos comprados
                'precio_compra' => $request->tipo_producto === 'comprado' ? $request->precio_compra : null,
                'stock_actual' => $request->tipo_producto === 'comprado' ? $request->stock_actual : null,
                'stock_minimo' => $request->tipo_producto === 'comprado' ? $request->stock_minimo : null,
                'unidad_medida' => $request->tipo_producto === 'comprado' ? $request->unidad_medida : null,
                'sku' => $request->tipo_producto === 'comprado' ? $request->sku : null,
            ]);

            $producto->load('categoria');

            // Agregar URL de imagen
            if ($producto->imagen) {
                $producto->imagen_url = url('storage/' . $producto->imagen);
            }

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'producto' => $producto
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear producto', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto'
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
                'activo' => 'nullable|boolean',
                'imagen' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048'
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

            // Procesar nueva imagen si existe
            $imagenPath = $producto->imagen;
            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior
                if ($producto->imagen) {
                    Storage::disk('public')->delete($producto->imagen);
                }
                $imagenPath = $this->guardarImagen($request->file('imagen'));
            }

            // Actualizar producto
            $producto->update([
                'categoria_id' => $request->categoria_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio_venta' => $request->precio_venta,
                'tipo_producto' => $request->tipo_producto,
                'activo' => $request->activo ?? $producto->activo,
                'imagen' => $imagenPath,
                'precio_compra' => $request->tipo_producto === 'comprado' ? $request->precio_compra : null,
                'stock_actual' => $request->tipo_producto === 'comprado' ? $request->stock_actual : null,
                'stock_minimo' => $request->tipo_producto === 'comprado' ? $request->stock_minimo : null,
                'unidad_medida' => $request->tipo_producto === 'comprado' ? $request->unidad_medida : null,
                'sku' => $request->tipo_producto === 'comprado' ? $request->sku : null,
            ]);

            $producto->load('categoria');

            // Agregar URL de imagen
            if ($producto->imagen) {
                $producto->imagen_url = url('storage/' . $producto->imagen);
            }

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'producto' => $producto
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar producto', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'producto_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto'
            ], 500);
        }
    }

    // Eliminar imagen de un producto
    public function eliminarImagen($id)
    {
        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
                $producto->update(['imagen' => null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar imagen de producto', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'producto_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen'
            ], 500);
        }
    }

    //Eliminar un producto (desactivar)
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
                $producto->update(['activo' => false]);
                return response()->json([
                    'success' => true,
                    'message' => 'Producto desactivado (tiene historial de ventas)'
                ]);
            }

            // Desactivar producto
            $producto->update(['activo' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Producto desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar producto', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'producto_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto'
            ], 500);
        }
    }

    //Obtener productos con stock bajo
    public function stockBajo()
    {
        try {
            $productos = Producto::where('tipo_producto', 'comprado')
                ->whereNotNull('stock_minimo')
                ->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->where('activo', true)
                ->with('categoria')
                ->get();

            // Agregar URLs de imágenes
            $productos->transform(function ($producto) {
                if ($producto->imagen) {
                    $producto->imagen_url = url('storage/' . $producto->imagen);
                }
                return $producto;
            });

            return response()->json([
                'success' => true,
                'productos' => $productos,
                'total' => $productos->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos con stock bajo', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos con stock bajo'
            ], 500);
        }
    }

    //Actualizar stock de un producto
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
            Log::error('Error al actualizar stock', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'producto_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar stock'
            ], 500);
        }
    }                                           

    //Guardar imagen
    private function guardarImagen($imagen)
    {
        $nombreOriginal = pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $imagen->getClientOriginalExtension();
        $nombreArchivo = $nombreOriginal . '_' . time() . '.' . $extension;
        
        $path = $imagen->storeAs('productos', $nombreArchivo, 'public');
        
        return $path;
    }
}
