<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleCompra extends Model
{
    use HasFactory;

    protected $table = 'DetalleCompras';
    protected $primaryKey = 'IdDetalleCompra';

    protected $fillable = [
        'IdCompra',
        'IdVariante',
        'Cantidad',
        'PrecioUnitario',
        'Subtotal'
    ];

    protected $casts = [
        'Cantidad' => 'integer',
        'PrecioUnitario' => 'decimal:2',
        'Subtotal' => 'decimal:2'
    ];

    public $timestamps = false;

    // Relaciones
    public function compra()
    {
        return $this->belongsTo(Compra::class, 'IdCompra');
    }
}