<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    // Registro de usuario
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'NumeroDocumento' => 'required|integer|unique:usuarios,NumeroDocumento',
            'Nombre1' => 'required|string|max:45',
            'Nombre2' => 'nullable|string|max:45',
            'Apellido1' => 'required|string|max:100',
            'Apellido2' => 'nullable|string|max:45',
            'Email' => 'required|string|email|max:100|unique:usuarios,Email',
            'Contrasena' => 'required|string|min:6|max:255',
            'password_confirmation' => 'required|string|min:6|same:Contrasena',
            'FechaNacimiento' => 'required|date',
            'Direccion' => 'required|string|max:255',
            'Telefono' => 'required|string|max:15',
            'Edad' => 'required|integer|min:0|max:120',
            'Role' => 'sometimes|in:user,admin',
            'IdTipoDocumento' => 'required|integer|exists:tipodocumento,IdTipoDocumento'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación de los datos',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
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
                'Role' => $request->Role ?? 'user',
                'IdTipoDocumento' => $request->IdTipoDocumento
            ]);

            $usuario->load('tipoDocumento');
            $usuario->makeHidden(['Contrasena']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => $usuario,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // 🔐 Login con JWT
    public function login(Request $request)
    {
        // Recolectar datos
        $emailLower = $request->input('email');
        $emailUpper = $request->input('Email');
        $numeroDoc = $request->input('NumeroDocumento');
        $passwordField = $request->input('password');
        $contrasenaField = $request->input('Contrasena');

        // Validación flexible
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'Email' => 'nullable|email',
            'NumeroDocumento' => 'nullable|integer',
            'password' => 'nullable|string',
            'Contrasena' => 'nullable|string',
        ], [
            'email.email' => 'Datos inválidos. Verifica el formato de tu email.',
            'Email.email' => 'Datos inválidos. Verifica el formato de tu email.',
        ]);

        // Debe venir al menos (email o Email o NumeroDocumento) y la contraseña
        $password = $contrasenaField ?? $passwordField;
        $identifierProvided = $emailLower || $emailUpper || $numeroDoc;

        if (!$identifierProvided || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar email (o Email) o NumeroDocumento, junto con la contraseña.'
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Buscar usuario: priorizar NumeroDocumento -> Email -> email
        if (!empty($numeroDoc)) {
            $usuario = Usuario::where('NumeroDocumento', $numeroDoc)->first();
        } else {
            $emailToSearch = $emailUpper ?? $emailLower;
            $usuario = Usuario::where('Email', $emailToSearch)->first();
        }

        if (!$usuario || !Hash::check($password, $usuario->Contrasena)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        try {
            $token = JWTAuth::fromUser($usuario);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el token',
                'error' => $e->getMessage()
            ], 500);
        }

        // Ocultar campos sensibles antes de retornar
        $usuario->makeHidden(['Contrasena']);

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user' => $usuario,
            'token' => $token
        ], 200);
    }


    // 🔒 Solicitar restablecimiento de contraseña (Envía el correo)
    public function forgotPassword(Request $request)
    {
        // Normalizar el campo de email (puede venir como 'email' o 'Email')
        $inputEmail = $request->input('email') ?? $request->input('Email');

        // Validar el email
        $validator = Validator::make(['email' => $inputEmail], [
            'email' => 'required|email',
        ], [
            'email.required' => 'El campo de correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico es inválido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Buscar al usuario
        $usuario = Usuario::where('Email', $inputEmail)->first();

        if (!$usuario) {
            // Retornar éxito para no revelar si el email existe
            return response()->json([
                'success' => true,
                'message' => 'Si la dirección de correo electrónico existe en nuestro sistema, se le ha enviado un enlace para restablecer la contraseña.'
            ]);
        }

        // Generar token
        $token = Str::random(60);

        // Almacenamiento y eliminación de tokens antiguos (envuelto en try-catch)
        try {
            // Eliminar tokens antiguos para este usuario
            DB::table('password_reset_tokens')->where('email', $usuario->Email)->delete();

            // Almacenar el nuevo token
            DB::table('password_reset_tokens')->insert([
                'email' => $usuario->Email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);
        } catch (\Exception $e) {
             return response()->json([
                'success' => false,
                'message' => 'Error de conexión al crear el token. Asegúrate de que la tabla password_reset_tokens exista.',
                'error' => $e->getMessage()
            ], 500);
        }

        // Envío del correo
        try {
            $nombreCompleto = trim($usuario->Nombre1 . ' ' . $usuario->Apellido1);

            Mail::send('emails.password_reset', [
                'token' => $token,
                'email' => $usuario->Email,
                'nombre' => $nombreCompleto
            ], function ($message) use ($usuario) {
                $message->to($usuario->Email, $usuario->Nombre1)->subject('Restablecer Contraseña');
                // FIX: Al usar env() con un segundo argumento, proporcionamos un valor por defecto ('noreply@example.com', 'App Name')
                // si las variables de entorno MAIL_FROM_ADDRESS o MAIL_FROM_NAME no están definidas, evitando el TypeError.
                $message->from(env('MAIL_FROM_ADDRESS', 'noreply@example.com'), env('MAIL_FROM_NAME', 'App Name'));
            });

            return response()->json([
                'success' => true,
                'message' => 'Enlace de restablecimiento de contraseña enviado a su correo electrónico.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el correo de restablecimiento. Verifica la configuración de MAIL en tu entorno.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // 🔑 Restablecer contraseña (Recibe el token y la nueva contraseña)
    public function resetPassword(Request $request)
    {
        // 1. Normalizar y validar los campos de entrada
        $inputEmail = $request->input('email') ?? $request->input('Email');
        $inputPassword = $request->input('Contrasena') ?? $request->input('password');
        $inputConfirmation = $request->input('Contrasena_confirmation') ?? $request->input('password_confirmation');

        $dataToValidate = [
            'email' => $inputEmail,
            'token' => $request->token,
            'Contrasena' => $inputPassword,
            'Contrasena_confirmation' => $inputConfirmation,
        ];

        // Se usa 'Contrasena' para la nueva contraseña, y 'Contrasena_confirmation' para la confirmación.
        $validator = Validator::make($dataToValidate, [
            'email' => 'required|email|exists:usuarios,Email',
            'token' => 'required|string|min:60', // 60 es la longitud del token Str::random(60)
            'Contrasena' => 'required|string|min:6|confirmed', // 'confirmed' busca 'Contrasena_confirmation'
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico es inválido.',
            'email.exists' => 'El correo electrónico no está registrado en nuestro sistema.',
            'token.required' => 'El token de restablecimiento es obligatorio.',
            'Contrasena.required' => 'La nueva contraseña es obligatoria.',
            'Contrasena.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'Contrasena.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación. Asegúrate que el correo sea válido y las contraseñas coincidan y tengan al menos 6 caracteres.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Verificar el token en la base de datos
        try {
            $resetData = DB::table('password_reset_tokens')
                ->where('email', $inputEmail)
                ->where('token', $request->token)
                ->first();
        } catch (\Exception $e) {
             return response()->json([
                'success' => false,
                'message' => 'Error de conexión al buscar el token. Asegúrate de que la tabla password_reset_tokens exista.',
                'error' => $e->getMessage()
            ], 500);
        }

        if (!$resetData || Carbon::parse($resetData->created_at)->addHours(24)->isPast()) {
            // Retorna un error específico si el token no existe o ha expirado
            return response()->json([
                'success' => false,
                'message' => 'Token de restablecimiento inválido o expirado.',
            ], 400);
        }

        // 3. Buscar y actualizar al usuario
        $usuario = Usuario::where('Email', $inputEmail)->first();

        if (!$usuario) {
            // Esto no debería suceder si 'email.exists' pasó, pero es una buena práctica de seguridad
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        try {
            // Actualizar la contraseña
            $usuario->Contrasena = Hash::make($inputPassword);
            $usuario->save();

            // 4. Eliminar el token usado
            DB::table('password_reset_tokens')->where('email', $inputEmail)->delete();

            // 5. Éxito
            return response()->json([
                'success' => true,
                'message' => 'Contraseña restablecida exitosamente.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar la contraseña.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // 🔐 Obtener usuario autenticado
    public function getUser(Request $request)
    {
        try {
            // Asegurarse de que el token JWT esté presente y sea válido
            $usuario = JWTAuth::parseToken()->authenticate();

            // Si se autentica, ocultar la contraseña antes de devolver los datos
            $usuario->makeHidden(['Contrasena']);

            return response()->json([
                'success' => true,
                'user' => $usuario,
            ]);
        } catch (JWTException $e) {
            // Capturar errores específicos del JWT (token no proporcionado, inválido o expirado)
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado',
            ], 401);
        }
    }

    // 🔐 Cerrar sesión
    public function logout(Request $request)
    {
        try {
            // Invalidar el token actual
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
            ]);
        } catch (JWTException $e) {
            // Esto puede ocurrir si el token ya está expirado/inválido, pero intentamos invalidarlo
            return response()->json([
                'success' => false,
                'message' => 'No se pudo cerrar la sesión o el token ya era inválido.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
