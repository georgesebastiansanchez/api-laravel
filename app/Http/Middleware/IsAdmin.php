<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // El usuario ya fue autenticado por IsUserAuth middleware
        $user = $request->auth_user ?? auth('api')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado',
                'status' => 401
            ], 401);
        }
        
        // Verificar si es admin o empleado
        if (!in_array($user->Role, ['admin', 'empleado'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo empleados y administradores.',
                'status' => 403
            ], 403);
        }

        return $next($request);
    }
}