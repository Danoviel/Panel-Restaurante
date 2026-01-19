<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CajaController extends Controller
{
    // Obtener la caja actual del usuario autenticado
    public function actual()
    {
        try {
            $caja = Caja::where('usuario_id', Auth::id())
                ->where('estado', 'abierta')
                ->with('usuario')
                ->latest()
                ->first();

            if (!$caja) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay una caja abierta',
                    'caja' => null
                ], 200);
            }

            // Calcular ventas del día
            $ventasEfectivo = Comprobante::where('metodo_pago', 'efectivo')
                ->where('estado', 'emitido')
                ->whereDate('created_at', $caja->fecha_apertura->toDateString())
                ->sum('total');

            $caja->ventas_efectivo = $ventasEfectivo;
            $caja->monto_esperado = $caja->monto_inicial + $ventasEfectivo;

            return response()->json([
                'success' => true,
                'caja' => $caja
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener caja actual', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener caja actual'
            ], 500);
        }
    }

    
    // Abrir una nueva caja
    public function abrir(Request $request)
    {
        try {
            // Verificar que no haya una caja abierta
            $cajaAbierta = Caja::where('usuario_id', Auth::id())
                ->where('estado', 'abierta')
                ->exists();

            if ($cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una caja abierta. Debes cerrarla antes de abrir una nueva.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'monto_inicial' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $caja = Caja::create([
                'usuario_id' => Auth::id(),
                'fecha_apertura' => now(),
                'monto_inicial' => $request->monto_inicial,
                'estado' => 'abierta'
            ]);

            $caja->load('usuario');

            return response()->json([
                'success' => true,
                'message' => 'Caja abierta exitosamente',
                'caja' => $caja
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al abrir caja', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al abrir caja'
            ], 500);
        }
    }

    
    // Cerrar la caja actual
    public function cerrar(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $caja = Caja::find($id);

            if (!$caja) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caja no encontrada'
                ], 404);
            }

            // Verificar que sea la caja del usuario autenticado
            if ($caja->usuario_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para cerrar esta caja'
                ], 403);
            }

            if ($caja->estado === 'cerrada') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta caja ya está cerrada'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'monto_real' => 'required|numeric|min:0',
                'notas' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calcular ventas en efectivo del día
            $ventasEfectivo = Comprobante::where('metodo_pago', 'efectivo')
                ->where('estado', 'emitido')
                ->whereDate('created_at', $caja->fecha_apertura->toDateString())
                ->sum('total');

            $montoEsperado = $caja->monto_inicial + $ventasEfectivo;
            $diferencia = $request->monto_real - $montoEsperado;

            $caja->update([
                'fecha_cierre' => now(),
                'monto_esperado' => $montoEsperado,
                'monto_real' => $request->monto_real,
                'diferencia' => $diferencia,
                'notas' => $request->notas,
                'estado' => 'cerrada'
            ]);

            DB::commit();

            $caja->load('usuario');

            // Información adicional para la respuesta
            $caja->ventas_efectivo = $ventasEfectivo;
            $caja->total_comprobantes = Comprobante::where('estado', 'emitido')
                ->whereDate('created_at', $caja->fecha_apertura->toDateString())
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada exitosamente',
                'caja' => $caja
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cerrar caja', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
                'caja_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar caja'
            ], 500);
        }
    }

    //Listar historial de cajas
    public function historial(Request $request)
    {
        try {
            // Validar parámetros de filtro
            $validator = Validator::make($request->all(), [
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'estado' => 'nullable|in:abierta,cerrada'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Caja::with('usuario');

            // Filtrar por usuario (solo admin puede ver todas)
            if (!$this->esAdmin()) {
                $query->where('usuario_id', Auth::id());
            }

            // Filtrar por fecha
            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_apertura', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_apertura', '<=', $request->fecha_hasta);
            }

            // Filtrar por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            $cajas = $query->orderBy('fecha_apertura', 'desc')->get();

            return response()->json([
                'success' => true,
                'cajas' => $cajas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener historial de cajas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial'
            ], 500);
        }
    }

    
    // Obtener detalles de una caja específica por ID
    public function show($id)
    {
        try {
            $caja = Caja::with('usuario')->find($id);

            if (!$caja) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caja no encontrada'
                ], 404);
            }

            // Verificar permisos
            if (!$this->esAdmin() && $caja->usuario_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta caja'
                ], 403);
            }

            // Obtener información adicional
            $fecha = $caja->fecha_apertura->toDateString();

            $comprobantes = Comprobante::where('estado', 'emitido')
                ->whereDate('created_at', $fecha)
                ->get();

            $ventasPorMetodo = $comprobantes->groupBy('metodo_pago')->map(function($items) {
                return [
                    'cantidad' => $items->count(),
                    'total' => $items->sum('total')
                ];
            });

            $caja->detalle = [
                'total_comprobantes' => $comprobantes->count(),
                'ventas_por_metodo' => $ventasPorMetodo
            ];

            return response()->json([
                'success' => true,
                'caja' => $caja
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener caja', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
                'caja_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener caja'
            ], 500);
        }
    }

    
    // Verificar si el usuario es administrador
    private function esAdmin()
    {
        $user = Auth::user();
        return $user && $user->rol && $user->rol->nombre === 'Administrador';
    }
}
