<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class MesaController extends Controller
{
    //Listar mesas
    public function index(Request $request)
    {
        try {
            // Validar parámetros de filtro
            $validator = Validator::make($request->all(), [
                'estado' => 'nullable|in:libre,ocupada,reservada,mantenimiento',
                'ubicacion' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Mesa::query();

            // Filtrar por estado si viene el parámetro
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtrar por ubicación
            if ($request->filled('ubicacion')) {
                $query->where('ubicacion', $request->ubicacion);
            }

            $mesas = $query->orderBy('numero', 'asc')->get();

            // Cargar la orden activa de cada mesa
            $mesas->load(['ordenActiva' => function($query) {
                $query->with('usuario', 'detalles.producto');
            }]);

            return response()->json([
                'success' => true,
                'mesas' => $mesas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener mesas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mesas'
            ], 500);
        }
    }

    //Crear una nueva mesa
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'numero' => 'required|string|max:10|unique:mesas,numero',
                'capacidad' => 'required|integer|min:1|max:20',
                'ubicacion' => 'nullable|string|max:50',
                'estado' => 'nullable|in:libre,ocupada,reservada,mantenimiento'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $mesa = Mesa::create([
                'numero' => $request->numero,
                'capacidad' => $request->capacidad,
                'ubicacion' => $request->ubicacion ?? 'Salón principal',
                'estado' => $request->estado ?? 'libre'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mesa creada exitosamente',
                'mesa' => $mesa
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear mesa', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear mesa'
            ], 500);
        }
    }

    //Obtener una mesa en específico por ID
    public function show($id)
    {
        try {
            $mesa = Mesa::with(['ordenActiva' => function($query) {
                $query->with('usuario', 'detalles.producto');
            }])->find($id);

            if (!$mesa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'mesa' => $mesa
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener mesa', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'mesa_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mesa'
            ], 500);
        }
    }

    //Actualizar una mesa existente
    public function update(Request $request, $id)
    {
        try {
            $mesa = Mesa::find($id);

            if (!$mesa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'numero' => 'required|string|max:10|unique:mesas,numero,' . $id,
                'capacidad' => 'required|integer|min:1|max:20',
                'ubicacion' => 'nullable|string|max:50',
                'estado' => 'nullable|in:libre,ocupada,reservada,mantenimiento'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $mesa->update([
                'numero' => $request->numero,
                'capacidad' => $request->capacidad,
                'ubicacion' => $request->ubicacion ?? $mesa->ubicacion,
                'estado' => $request->estado ?? $mesa->estado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mesa actualizada exitosamente',
                'mesa' => $mesa
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar mesa', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'mesa_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar mesa'
            ], 500);
        }
    }

    //Eliminar una mesa
    public function destroy($id)
    {
        try {
            $mesa = Mesa::find($id);

            if (!$mesa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa no encontrada'
                ], 404);
            }

            // Verificar si tiene órdenes asociadas
            $ordenesCount = $mesa->ordenes()->count();

            if ($ordenesCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. La mesa tiene historial de órdenes.'
                ], 400);
            }

            // Verificar que esté libre
            if ($mesa->estado !== 'libre') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una mesa que no está libre'
                ], 400);
            }

            $mesa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mesa eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar mesa', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'mesa_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar mesa'
            ], 500);
        }
    }


    //Cambiar el estado de una mesa
    public function cambiarEstado(Request $request, $id)
    {
        try {
            $mesa = Mesa::find($id);

            if (!$mesa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:libre,ocupada,reservada,mantenimiento'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $mesa->update([
                'estado' => $request->estado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado de mesa actualizado',
                'mesa' => $mesa
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de mesa', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'mesa_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado'
            ], 500);
        }
    }


    //Obtener mesas libres 
    public function libres()
    {
        try {
            $mesas = Mesa::where('estado', 'libre')
                ->orderBy('numero', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'mesas' => $mesas,
                'total' => $mesas->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener mesas libres', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mesas libres'
            ], 500);
        }
    }


    //Obtener mesas ocupadas
    public function ocupadas()
    {
        try {
            $mesas = Mesa::where('estado', 'ocupada')
                ->with(['ordenActiva' => function($query) {
                    $query->with('usuario', 'detalles.producto');
                }])
                ->orderBy('numero', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'mesas' => $mesas,
                'total' => $mesas->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener mesas ocupadas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mesas ocupadas'
            ], 500);
        }
    }


    //Obtener resumen del estado de mesas
    public function resumen()
    {
        try {
            $total = Mesa::count();
            $libres = Mesa::where('estado', 'libre')->count();
            $ocupadas = Mesa::where('estado', 'ocupada')->count();
            $reservadas = Mesa::where('estado', 'reservada')->count();
            $mantenimiento = Mesa::where('estado', 'mantenimiento')->count();

            return response()->json([
                'success' => true,
                'resumen' => [
                    'total' => $total,
                    'libres' => $libres,
                    'ocupadas' => $ocupadas,
                    'reservadas' => $reservadas,
                    'mantenimiento' => $mantenimiento,
                    'porcentaje_ocupacion' => $total > 0 ? round(($ocupadas / $total) * 100, 2) : 0
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de mesas', [
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
