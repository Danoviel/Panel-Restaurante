<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Verificar que el usuario esté autenticado
        if (!auth('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        $userId = auth('api')->id();
        
        // Obtener el usuario con su rol
        $user = User::with('rol')->find($userId);

        // Verificar que el usuario tenga un rol
        if (!$user || !$user->rol) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario sin rol asignado'
            ], 403);
        }

        // Verificar si el rol del usuario está en los roles permitidos
        if (!in_array($user->rol->nombre, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder a este recurso',
                'rol_requerido' => $roles,
                'tu_rol' => $user->rol->nombre
            ], 403);
        }

        return $next($request);
    }
}
