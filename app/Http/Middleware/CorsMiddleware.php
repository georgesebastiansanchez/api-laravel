<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 🛠️ ORÍGENES PERMITIDOS DINÁMICOS EN DESARROLLO 🛠️
        $origin = $request->headers->get('Origin');
        
        // Patrones aceptados: cualquier puerto en localhost o 127.0.0.1
        $allowedPatterns = [
            'http://localhost',
            'http://127.0.0.1',
        ];
        
        $isAllowed = false;
        
        // 1. Verificar si el origen comienza con localhost o 127.0.0.1
        if ($origin) {
            foreach ($allowedPatterns as $pattern) {
                // Comprueba si el origen empieza con el patrón (ej. http://localhost:58837 empieza con http://localhost)
                if (str_starts_with($origin, $pattern)) {
                    $isAllowed = true;
                    break;
                }
            }
        }

        // 2. Procesar la respuesta ANTES de establecer los encabezados
        $response = $next($request);

        // 3. Establecer encabezados de Origen si es permitido
        if ($isAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        // 4. Encabezados de Métodos y Headers (Aplican a todos, incluyendo preflight OPTION)
        if ($request->isMethod('OPTIONS')) {
            // Manejar la petición OPTIONS de preflight
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
            return $response; // Devolver la respuesta inmediatamente para OPTIONS
        }
        
        // Asegurar que los encabezados de métodos y headers se envíen en la respuesta final
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');

        return $response;
    }
}