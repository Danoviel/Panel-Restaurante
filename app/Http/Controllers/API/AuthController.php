<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
USE Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Login de usuario
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Verificar si el usuario está activo
        $user = Auth::guard('api')->user();
        if (!$user->activo) {
            Auth::guard('api')->logout();
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo. Contacte al administrador.'
            ], 403);
        }

        return $this->respondWithToken($token);
    }

    // Registro de usuario
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'telefono' => 'nullable|string|max:20',
            'rol_id' => 'required|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefono' => $request->telefono,
            'rol_id' => $request->rol_id,
            'activo' => true
        ]);

        $token = Auth::guard('api')->login($user);

        return $this->respondWithToken($token);
    }

    // Obtener el usuario autenticado
    public function me()
    {
        $userId = Auth::guard('api')->id();
        $user = User::with('rol')->find($userId);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'rol' => [
                    'id' => $user->rol->id,
                    'nombre' => $user->rol->nombre
                ],
                'activo' => $user->activo
            ]
        ]);
    }

    // Cerrar sesión
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    // Formatear respuesta con el token
    protected function respondWithToken($token)
    {
        $user = Auth::guard('api')->user();
        
        // Cargar la relación usando with en vez de load
        $user = User::with('rol')->find($user->id);

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, 
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'rol' => [
                    'id' => $user->rol->id,
                    'nombre' => $user->rol->nombre
                ]
            ]
        ]);
    }
}
