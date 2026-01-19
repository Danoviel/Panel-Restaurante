<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use App\Models\ConfiguracionNegocio;
use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ComprobanteController extends Controller
{
    //Listar para los comprobantes emitidos
    public function index(Request $request)
    {
        try {
            // Validar parámetros de filtro
            $validator = Validator::make($request->all(), [
                'tipo' => 'nullable|in:boleta,factura,ninguno',
                'fecha' => 'nullable|date',
                'metodo_pago' => 'nullable|in:efectivo,tarjeta,yape,plin,transferencia'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Comprobante::with('orden.mesa')->where('estado', 'emitido');

            // Filtrar por tipo
            if ($request->filled('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            // Filtrar por fecha
            if ($request->filled('fecha')) {
                $query->whereDate('created_at', $request->fecha);
            } else {
                // Por defecto, comprobantes del día
                $query->whereDate('created_at', today());
            }

            // Filtrar por método de pago
            if ($request->filled('metodo_pago')) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            $comprobantes = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'comprobantes' => $comprobantes
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener comprobantes', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener comprobantes'
            ], 500);
        }
    }

    //Obtener un comprobante por ID
    public function show($id)
    {
        try {
            $comprobante = Comprobante::with('orden.detalles.producto')->find($id);

            if (!$comprobante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comprobante no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'comprobante' => $comprobante
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener comprobante', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'comprobante_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener comprobante'
            ], 500);
        }
    }

    //Generar un nuevo comprobante para una orden
    public function generar(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'orden_id' => 'required|exists:ordenes,id',
                'tipo' => 'required|in:boleta,factura,ninguno',
                'metodo_pago' => 'required|in:efectivo,tarjeta,yape,plin,transferencia',
                'cliente_documento' => 'required_if:tipo,factura|nullable|string|max:20',
                'cliente_nombre' => 'required_if:tipo,factura|nullable|string|max:255',
                'cliente_direccion' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que la orden existe
            $orden = Orden::with('detalles')->find($request->orden_id);

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            // Verificar que la orden no tenga comprobante ya
            if ($orden->comprobante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta orden ya tiene un comprobante generado'
                ], 400);
            }

            // Obtener configuración del negocio con bloqueo para evitar números duplicados
            $config = ConfiguracionNegocio::lockForUpdate()->first();

            if (!$config) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se ha configurado el negocio. Complete el wizard de configuración.'
                ], 400);
            }

            // Determinar serie y número según el tipo
            $serie = null;
            $numero = null;

            if ($request->tipo === 'boleta') {
                if (!$config->emite_boletas) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'La emisión de boletas no está habilitada'
                    ], 400);
                }
                $serie = $config->serie_boleta;
                $numero = $config->numero_actual_boleta + 1;
                $config->increment('numero_actual_boleta');
            } elseif ($request->tipo === 'factura') {
                if (!$config->emite_facturas) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'La emisión de facturas no está habilitada'
                    ], 400);
                }
                $serie = $config->serie_factura;
                $numero = $config->numero_actual_factura + 1;
                $config->increment('numero_actual_factura');
            }

            // Crear el comprobante
            $comprobante = Comprobante::create([
                'orden_id' => $orden->id,
                'tipo' => $request->tipo,
                'serie' => $serie,
                'numero' => $numero,
                'cliente_documento' => $request->cliente_documento,
                'cliente_nombre' => $request->cliente_nombre,
                'cliente_direccion' => $request->cliente_direccion,
                'subtotal' => $orden->subtotal,
                'igv' => $orden->impuesto,
                'total' => $orden->total,
                'metodo_pago' => $request->metodo_pago,
                'estado' => 'emitido'
            ]);

            // Actualizar estado de la orden a "pagado"
            $orden->update([
                'estado' => 'pagado',
                'pagado_at' => now()
            ]);

            // Liberar mesa si tiene
            if ($orden->mesa_id) {
                $orden->mesa->update(['estado' => 'libre']);
            }

            DB::commit();

            $comprobante->load('orden.detalles.producto');

            return response()->json([
                'success' => true,
                'message' => 'Comprobante generado exitosamente',
                'comprobante' => $comprobante
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al generar comprobante', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'orden_id' => $request->orden_id ?? null
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al generar comprobante'
            ], 500);
        }
    }

    //Anular un comprobante
    public function anular(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $comprobante = Comprobante::find($id);

            if (!$comprobante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comprobante no encontrado'
                ], 404);
            }

            if ($comprobante->estado === 'anulado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El comprobante ya está anulado'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'motivo_anulacion' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $comprobante->update([
                'estado' => 'anulado',
                'motivo_anulacion' => $request->motivo_anulacion,
                'anulado_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comprobante anulado exitosamente',
                'comprobante' => $comprobante
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular comprobante', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'comprobante_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al anular comprobante'
            ], 500);
        }
    }

    //Obtener resumen del día
    public function resumenDia()
    {
        try {
            $fecha = today();

            $totalBoletas = Comprobante::where('tipo', 'boleta')
                ->where('estado', 'emitido')
                ->whereDate('created_at', $fecha)
                ->sum('total');

            $totalFacturas = Comprobante::where('tipo', 'factura')
                ->where('estado', 'emitido')
                ->whereDate('created_at', $fecha)
                ->sum('total');

            $cantidadBoletas = Comprobante::where('tipo', 'boleta')
                ->where('estado', 'emitido')
                ->whereDate('created_at', $fecha)
                ->count();

            $cantidadFacturas = Comprobante::where('tipo', 'factura')
                ->where('estado', 'emitido')
                ->whereDate('created_at', $fecha)
                ->count();

            // Por método de pago
            $porMetodoPago = Comprobante::select('metodo_pago', DB::raw('SUM(total) as total'))
                ->where('estado', 'emitido')
                ->whereDate('created_at', $fecha)
                ->groupBy('metodo_pago')
                ->get();

            return response()->json([
                'success' => true,
                'resumen' => [
                    'fecha' => $fecha->format('Y-m-d'),
                    'boletas' => [
                        'cantidad' => $cantidadBoletas,
                        'total' => number_format($totalBoletas, 2)
                    ],
                    'facturas' => [
                        'cantidad' => $cantidadFacturas,
                        'total' => number_format($totalFacturas, 2)
                    ],
                    'total_general' => number_format($totalBoletas + $totalFacturas, 2),
                    'por_metodo_pago' => $porMetodoPago
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener resumen del día', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen'
            ], 500);
        }
    }
}
