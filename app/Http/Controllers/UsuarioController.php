<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    /**
     * Obtener todos los usuarios (solo admin)
     */
    public function getAllUsers()
    {
        try {
            $usuarios = Usuario::with('tipoDocumento')
                ->select('NumeroDocumento', 'Nombre1', 'Nombre2', 'Apellido1', 'Apellido2', 
                        'Email', 'Telefono', 'Role', 'IdTipoDocumento', 'Edad', 'Direccion')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Usuarios obtenidos exitosamente',
                'data' => $usuarios,
                'status' => 200
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Obtener usuario por NumeroDocumento (solo admin)
     */
    public function getUserById($numeroDocumento)
    {
        try {
            $usuario = Usuario::with('tipoDocumento')
                ->where('NumeroDocumento', $numeroDocumento)
                ->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'status' => 404
                ], 404);
            }

            // Ocultar contraseña en la respuesta
            $usuario->makeHidden(['Contrasena']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario encontrado',
                'data' => $usuario,
                'status' => 200
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el usuario',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Actualizar usuario (solo admin)
     */
    public function updateUserById(Request $request, $numeroDocumento)
    {
        try {
            $usuario = Usuario::where('NumeroDocumento', $numeroDocumento)->first();
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'status' => 404
                ], 404);    
            }

            $validator = Validator::make($request->all(), [
                'Nombre1' => 'sometimes|string|max:45',
                'Nombre2' => 'sometimes|nullable|string|max:45',
                'Apellido1' => 'sometimes|string|max:100',
                'Apellido2' => 'sometimes|nullable|string|max:45',
                'Email' => 'sometimes|email|max:100|unique:usuarios,Email,' . $numeroDocumento . ',NumeroDocumento',
                'Contrasena' => 'sometimes|string|min:6|max:255',
                'FechaNacimiento' => 'sometimes|date',
                'Direccion' => 'sometimes|string|max:255',
                'Telefono' => 'sometimes|string|max:15',
                'Edad' => 'sometimes|integer|min:0|max:120',
                'Role' => 'sometimes|in:user,admin',
                'IdTipoDocumento' => 'sometimes|integer|exists:TipoDocumento,IdTipoDocumento'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'status' => 400
                ], 400);    
            }

            // Preparar datos para actualizar
            $updateData = $request->only([
                'Nombre1', 'Nombre2', 'Apellido1', 'Apellido2', 'Email', 
                'FechaNacimiento', 'Direccion', 'Telefono', 'Edad', 'Role', 'IdTipoDocumento'
            ]);

            // Si se incluye contraseña, encriptarla
            if ($request->has('Contrasena')) {
                $updateData['Contrasena'] = Hash::make($request->Contrasena);
            }

            $usuario->update($updateData);

            // Refrescar y ocultar contraseña
            $usuario->fresh()->load('tipoDocumento')->makeHidden(['Contrasena']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'data' => $usuario,
                'status' => 200
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
    /**
 * Crear nuevo usuario (solo admin)
 */
public function createUser(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'NumeroDocumento' => 'required|string|max:20|unique:usuarios,NumeroDocumento',
            'Nombre1' => 'required|string|max:45',
            'Nombre2' => 'nullable|string|max:45',
            'Apellido1' => 'required|string|max:100',
            'Apellido2' => 'nullable|string|max:45',
            'Email' => 'required|email|max:100|unique:usuarios,Email',
            'Contrasena' => 'required|string|min:6|max:255',
            'FechaNacimiento' => 'nullable|date',
            'Direccion' => 'nullable|string|max:255',
            'Telefono' => 'nullable|string|max:15',
            'Edad' => 'nullable|integer|min:0|max:120',
            'Role' => 'required|in:user,admin',
            'IdTipoDocumento' => 'required|integer|exists:TipoDocumento,IdTipoDocumento'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        // Crear usuario
        $usuario = Usuario::create([
            'NumeroDocumento' => $request->NumeroDocumento,
            'Nombre1' => $request->Nombre1,
            'Nombre2' => $request->Nombre2,
            'Apellido1' => $request->Apellido1,
            'Apellido2' => $request->Apellido2,
            'Email' => $request->Email,
            'Contrasena' => Hash::make($request->Contrasena),
            'FechaNacimiento' => $request->FechaNacimiento,
            'Direccion' => $request->Direccion,
            'Telefono' => $request->Telefono,
            'Edad' => $request->Edad,
            'Role' => $request->Role,
            'IdTipoDocumento' => $request->IdTipoDocumento
        ]);

        $usuario->load('tipoDocumento')->makeHidden(['Contrasena']);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => $usuario,
            'status' => 201
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al crear el usuario',
            'error' => $e->getMessage(),
            'status' => 500
        ], 500);
    }
}
    /**
     * Eliminar usuario (solo admin)
     */
    public function deleteUserById($numeroDocumento)
    {
        try {
            $usuario = Usuario::where('NumeroDocumento', $numeroDocumento)->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'status' => 404
                ], 404);
            }

            // Prevenir que un admin se elimine a sí mismo
            $currentUser = auth('api')->user();
            if ($usuario->NumeroDocumento === $currentUser->NumeroDocumento) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propia cuenta',
                    'status' => 400
                ], 400);
            }

            $usuario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado correctamente',
                'status' => 200
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}