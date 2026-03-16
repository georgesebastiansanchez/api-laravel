<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'MovimientosInventario';
    protected $primaryKey = 'IdMovimiento';

    protected $fillable = [
        'IdVariante',
        'TipoMovimiento',
        'Cantidad',
        'StockAnterior',
        'StockNuevo',
        'ReferenciaId',
        'TipoReferencia',
        'Motivo',
        'NumeroDocumentoUsuario'
    ];

    protected $casts = [
        'Cantidad' => 'integer',
        'StockAnterior' => 'integer',
        'StockNuevo' => 'integer',
        'ReferenciaId' => 'integer',
        'FechaMovimiento' => 'datetime'
    ];

    const CREATED_AT = 'FechaMovimiento';
    const UPDATED_AT = null;

    // Relaciones


    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'NumeroDocumentoUsuario', 'NumeroDocumento');
    }
}