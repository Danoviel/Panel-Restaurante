<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DetalleOrden;
use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrdenController extends Controller
{
    //Listar órdenes con filtros opcionales
    public function index(Request $request)
    {
        try {
            // Validar parámetros de filtro
            $validator = Validator::make($request->all(), [
                'estado' => 'nullable|in:pendiente,en_preparacion,servido,pagado,cancelado',
                'fecha' => 'nullable|date',
                'todas' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Orden::with(['mesa', 'usuario', 'detalles.producto']);

            // Filtrar por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtrar por fecha
            if ($request->filled('fecha')) {
                $query->whereDate('created_at', $request->fecha);
            }

            // Órdenes del día por defecto
            if (!$request->filled('fecha') && !$request->has('todas')) {
                $query->whereDate('created_at', today());
            }

            $ordenes = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'ordenes' => $ordenes
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener órdenes', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener órdenes'
            ], 500);
        }
    }

    
    //Obtner detalles de una orden específica
    public function show($id)
    {
        try {
            $orden = Orden::with(['mesa', 'usuario', 'detalles.producto.categoria', 'comprobante'])
                ->find($id);

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'orden' => $orden
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener orden', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'orden_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener orden'
            ], 500);
        }
    }


    //Crear una nueva orden
    public function store(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $validator = Validator::make($request->all(), [
                'mesa_id' => 'nullable|exists:mesas,id',
                'tipo_servicio' => 'required|in:salon,delivery,para_llevar',
                'numero_personas' => 'nullable|integer|min:1',
                'notas' => 'nullable|string',
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.notas' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar mesa si es tipo salón
            if ($request->tipo_servicio === 'salon' && !$request->mesa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe seleccionar una mesa para servicio en salón'
                ], 422);
            }

            // Verificar que la mesa esté libre
            if ($request->mesa_id) {
                $mesa = Mesa::find($request->mesa_id);
                if ($mesa->estado !== 'libre') {
                    return response()->json([
                        'success' => false,
                        'message' => 'La mesa no está disponible'
                    ], 400);
                }
            }

            // Calcular totales
            $subtotal = 0;
            $productosData = [];

            foreach ($request->productos as $item) {
                $producto = Producto::find($item['producto_id']);
                
                if (!$producto->activo) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "El producto {$producto->nombre} no está disponible"
                    ], 400);
                }

                // Verificar stock si es producto comprado
                if ($producto->tipo_producto === 'comprado') {
                    if ($producto->stock_actual < $item['cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock_actual}"
                        ], 400);
                    }
                }

                $subtotalProducto = $producto->precio_venta * $item['cantidad'];
                $subtotal += $subtotalProducto;

                $productosData[] = [
                    'producto' => $producto,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal' => $subtotalProducto,
                    'notas' => $item['notas'] ?? null
                ];
            }

            // Calcular impuesto (IGV 18% en Perú)
            $impuesto = $subtotal * 0.18;
            $total = $subtotal + $impuesto;

            // Crear la orden
            $orden = Orden::create([
                'mesa_id' => $request->mesa_id,
                'usuario_id' => Auth::id(),
                'estado' => 'pendiente',
                'subtotal' => $subtotal,
                'descuento' => 0,
                'impuesto' => $impuesto,
                'total' => $total,
                'tipo_servicio' => $request->tipo_servicio,
                'numero_personas' => $request->numero_personas ?? 1,
                'notas' => $request->notas
            ]);

            // Crear detalles de la orden
            foreach ($productosData as $item) {
                DetalleOrden::create([
                    'orden_id' => $orden->id,
                    'producto_id' => $item['producto']->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                    'notas' => $item['notas'],
                    'estado' => 'pendiente'
                ]);

                // Descontar stock si es producto comprado
                if ($item['producto']->tipo_producto === 'comprado') {
                    $item['producto']->decrement('stock_actual', $item['cantidad']);
                }
            }

            // Actualizar estado de la mesa
            if ($request->mesa_id) {
                Mesa::where('id', $request->mesa_id)->update(['estado' => 'ocupada']);
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $orden->load(['mesa', 'usuario', 'detalles.producto']);

            return response()->json([
                'success' => true,
                'message' => 'Orden creada exitosamente',
                'orden' => $orden
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear orden', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear orden'
            ], 500);
        }
    }

    
    //Agregar productos a una orden existente
    public function agregarProductos(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $orden = Orden::find($id);

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            if ($orden->estado === 'pagado' || $orden->estado === 'cancelado') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden agregar productos a una orden pagada o cancelada'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.notas' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $subtotalAdicional = 0;

            foreach ($request->productos as $item) {
                $producto = Producto::find($item['producto_id']);

                if (!$producto->activo) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "El producto {$producto->nombre} no está disponible"
                    ], 400);
                }

                // Verificar stock
                if ($producto->tipo_producto === 'comprado') {
                    if ($producto->stock_actual < $item['cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para {$producto->nombre}"
                        ], 400);
                    }
                }

                $subtotalProducto = $producto->precio_venta * $item['cantidad'];
                $subtotalAdicional += $subtotalProducto;

                DetalleOrden::create([
                    'orden_id' => $orden->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal' => $subtotalProducto,
                    'notas' => $item['notas'] ?? null,
                    'estado' => 'pendiente'
                ]);

                // Descontar stock
                if ($producto->tipo_producto === 'comprado') {
                    $producto->decrement('stock_actual', $item['cantidad']);
                }
            }

            // Recalcular totales
            $nuevoSubtotal = $orden->subtotal + $subtotalAdicional;
            $nuevoImpuesto = $nuevoSubtotal * 0.18;
            $nuevoTotal = $nuevoSubtotal + $nuevoImpuesto;

            $orden->update([
                'subtotal' => $nuevoSubtotal,
                'impuesto' => $nuevoImpuesto,
                'total' => $nuevoTotal
            ]);

            DB::commit();

            $orden->load(['mesa', 'usuario', 'detalles.producto']);

            return response()->json([
                'success' => true,
                'message' => 'Productos agregados exitosamente',
                'orden' => $orden
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al agregar productos a orden', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'orden_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar productos'
            ], 500);
        }
    }

    
    //Cambiar estado de una orden
    public function cambiarEstado(Request $request, $id)
    {
        try {
            $orden = Orden::find($id);

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:pendiente,en_preparacion,servido,pagado,cancelado'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orden->update([
                'estado' => $request->estado,
                'pagado_at' => $request->estado === 'pagado' ? now() : null
            ]);

            // Si la orden se marca como pagada o cancelada, liberar la mesa
            if (($request->estado === 'pagado' || $request->estado === 'cancelado') && $orden->mesa_id) {
                Mesa::where('id', $orden->mesa_id)->update(['estado' => 'libre']);
            }

            $orden->load(['mesa', 'usuario', 'detalles.producto']);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'orden' => $orden
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de orden', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'orden_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado'
            ], 500);
        }
    }


    //Cancelar una orden
    public function cancelar($id)
    {
        DB::beginTransaction();

        try {
            $orden = Orden::with('detalles.producto')->find($id);

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            if ($orden->estado === 'pagado') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una orden ya pagada'
                ], 400);
            }

            // Devolver stock de productos comprados
            foreach ($orden->detalles as $detalle) {
                if ($detalle->producto->tipo_producto === 'comprado') {
                    $detalle->producto->increment('stock_actual', $detalle->cantidad);
                }
            }

            // Cambiar estado a cancelado
            $orden->update(['estado' => 'cancelado']);

            // Liberar mesa
            if ($orden->mesa_id) {
                Mesa::where('id', $orden->mesa_id)->update(['estado' => 'libre']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orden cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cancelar orden', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'orden_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar orden'
            ], 500);
        }
    }

    
    //Ordenes activas (pendientes, en preparación, servido)
    public function activas()
    {
        try {
            $ordenes = Orden::whereIn('estado', ['pendiente', 'en_preparacion', 'servido'])
                ->with(['mesa', 'usuario', 'detalles.producto'])
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'ordenes' => $ordenes,
                'total' => $ordenes->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener órdenes activas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener órdenes activas'
            ], 500);
        }
    }

    
    //Órdenes para cocina (pendientes y en preparación)
    public function cocina()
    {
        try {
            $ordenes = Orden::whereIn('estado', ['pendiente', 'en_preparacion'])
                ->with(['mesa', 'usuario', 'detalles' => function($query) {
                    $query->whereIn('estado', ['pendiente', 'preparando'])
                          ->with('producto');
                }])
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'ordenes' => $ordenes
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener órdenes de cocina', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener órdenes de cocina'
            ], 500);
        }
    }
}
