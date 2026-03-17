<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->id('IdDetalleVenta');

            // Relación con ventas
            $table->unsignedBigInteger('IdVenta');
            $table->foreign('IdVenta')
                  ->references('IdVenta')
                  ->on('ventas')
                  ->onDelete('cascade');

            // Relación con productos
            $table->unsignedBigInteger('IdProducto');
            $table->foreign('IdProducto')
                  ->references('IdProducto')
                  ->on('productos')
                  ->onDelete('restrict');

            // Datos del detalle
            $table->integer('Cantidad')->default(1);
            $table->decimal('PrecioUnitario', 10, 2);
            $table->decimal('Subtotal', 10, 2);
            $table->decimal('Descuento', 10, 2)->default(0);
            $table->decimal('Total', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_venta');
    }
};
