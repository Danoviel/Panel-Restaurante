<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{

    //Listar categorías activas ordenadas por 'orden' ascendente
    public function index()
    {
        try {
            $categorias = Categoria::where('activo', true)
                ->orderBy('orden', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'categorias' => $categorias
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Crear una nueva categoría
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:categorias,nombre',
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
                'activo' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $categoria = Categoria::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'orden' => $request->orden ?? 0,
                'activo' => $request->activo ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'categoria' => $categoria
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Obtener detalles de una categoría específica por ID
    public function show($id)
    {
        try {
            $categoria = Categoria::find($id);

            if (!$categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'categoria' => $categoria
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Actualizar una categoría existente
    public function update(Request $request, $id)
    {
        try {
            $categoria = Categoria::find($id);

            if (!$categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:categorias,nombre,' . $id,
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
                'activo' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $categoria->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'orden' => $request->orden ?? $categoria->orden,
                'activo' => $request->activo ?? $categoria->activo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'categoria' => $categoria
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Eliminar una categoría
    public function destroy($id)
    {
        try {
            $categoria = Categoria::find($id);

            if (!$categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            // Verificar si tiene productos asociados
            $productosCount = $categoria->productos()->count();

            if ($productosCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar. La categoría tiene {$productosCount} productos asociados."
                ], 400);
            }

            // Marcar como inactiva en vez de eliminar
            $categoria->update(['activo' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría desactivada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Obtner categorias por su productos
    public function conProductos()
    {
        try {
            $categorias = Categoria::where('activo', true)
                ->with(['productos' => function($query) {
                    $query->where('activo', true);
                }])
                ->orderBy('orden', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'categorias' => $categorias
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías con productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
