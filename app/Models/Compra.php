<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'compras';   // Nombre exacto de la tabla

    protected $primaryKey = 'IdCompras';  // Llave primaria personalizada

    public $timestamps = false; // Porque tu tabla ya maneja FechaCreacion

    protected $fillable = [
        'FechaCompra',
        'Total',
        'Estado',
        'FechaCreacion',
        'IdProveedor',
        'NumeroDocumentoUsuario'
    ];

    /**
     * Relación con Proveedor
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'IdProveedor', 'IdProveedor');
    }

    /**
     * Relación con Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'NumeroDocumentoUsuario', 'NumeroDocumento');
    }
}
