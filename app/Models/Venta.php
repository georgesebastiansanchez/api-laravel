<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';
    protected $primaryKey = 'IdVenta';
    public $timestamps = false;

    protected $fillable = [
        'NumeroDocumentoCliente',
        'DocumentoUsuario',
        'FechaVenta',
        'Total',
        'MetodoPago',
        'Estado',
        'Notas'
    ];

    protected $casts = [
        'Total' => 'decimal:2'
    ];

    // Relación con el usuario que registró la venta
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'DocumentoUsuario', 'NumeroDocumento');
    }
}