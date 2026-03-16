<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    use HasFactory;

    protected $table = 'Carrito';
    protected $primaryKey = 'IdCarrito';

    protected $fillable = [
        'NumeroDocumentoUsuario',
        'IdVariante',
        'Cantidad'
    ];

    protected $casts = [
        'Cantidad' => 'integer',
        'FechaAgregado' => 'datetime'
    ];

    const CREATED_AT = 'FechaAgregado';
    const UPDATED_AT = null;

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'NumeroDocumentoUsuario', 'NumeroDocumento');
    }


    // Método para obtener el subtotal del item
    public function getSubtotal()
    {
        return $this->Cantidad * $this->variante->getPrecioFinal();
    }
}