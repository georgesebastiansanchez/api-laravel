<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalleventa'; // minúscula, sin 's'
    protected $primaryKey = 'IdDetalleVenta';
    public $timestamps = false;
    
    protected $fillable = [
        'IdVenta',
        'IdProducto',
        'Cantidad',
        'PrecioUnitario',
        'Total'
    ];
    
    protected $casts = [
        'Cantidad' => 'integer',
        'PrecioUnitario' => 'decimal:2',
        'Total' => 'decimal:2'
    ];
    
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'IdVenta', 'IdVenta');
    }
    
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'IdProducto', 'IdProducto');
    }
}
