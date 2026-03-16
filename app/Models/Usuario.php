<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'Usuarios';
    protected $primaryKey = 'NumeroDocumento';
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = [
        'NumeroDocumento',
        'Nombre1',
        'Nombre2',
        'Apellido1',
        'Apellido2',
        'Direccion',
        'Telefono',
        'Email',
        'Contrasena',
        'FechaNacimiento',
        'Edad',
        'Role',
        'IdTipoDocumento',
        'Activo'
    ];

    protected $hidden = [
        'Contrasena',
        'remember_token',
    ];

    protected $casts = [
        'FechaNacimiento' => 'date',
        'Edad' => 'integer',
        'Activo' => 'boolean',
        'FechaRegistro' => 'datetime'
    ];

    const CREATED_AT = 'FechaRegistro';
    const UPDATED_AT = null;

    public function getAuthPassword()
    {
        return $this->Contrasena;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function isAdmin()
    {
        return $this->Role === 'admin';
    }

    public function isEmpleado()
    {
        return $this->Role === 'empleado';
    }

    // ============================================
    // RELACIONES EXISTENTES
    // ============================================
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'IdTipoDocumento');
    }

    public function carrito()
    {
        return $this->hasMany(Carrito::class, 'NumeroDocumentoUsuario', 'NumeroDocumento');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'NumeroDocumentoCliente', 'NumeroDocumento');
    }

    public function ventasComoVendedor()
    {
        return $this->hasMany(Venta::class, 'NumeroDocumentoVendedor', 'NumeroDocumento');
    }

    public function compras()
    {
        return $this->hasMany(Compra::class, 'NumeroDocumentoUsuario', 'NumeroDocumento');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'NumeroDocumentoUsuario', 'NumeroDocumento');
    }

    // ============================================
    // NUEVAS RELACIONES PARA SISTEMA DE PERMISOS
    // ============================================
    public function permisos()
    {
        return $this->hasMany(PermisoUsuario::class, 'NumeroDocumento', 'NumeroDocumento');
    }

    public function modulosConAcceso()
    {
        return $this->belongsToMany(
            Modulo::class,
            'permisos_usuario',
            'NumeroDocumento',
            'IdModulo',
            'NumeroDocumento',
            'IdModulo'
        )->wherePivot('TieneAcceso', true);
    }

    public function solicitudesAcceso()
    {
        return $this->hasMany(SolicitudAcceso::class, 'NumeroDocumento', 'NumeroDocumento');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'NumeroDocumento', 'NumeroDocumento');
    }

    public function notificacionesNoLeidas()
    {
        return $this->notificaciones()->noLeidas();
    }

    // Método helper para verificar si tiene acceso a un módulo
    public function tieneAccesoModulo($nombreModulo)
    {
        if ($this->Role === 'admin') {
            return true; // Los admin tienen acceso a todo
        }

        return $this->permisos()
            ->whereHas('modulo', function($query) use ($nombreModulo) {
                $query->where('NombreModulo', $nombreModulo);
            })
            ->where('TieneAcceso', true)
            ->exists();
    }
}