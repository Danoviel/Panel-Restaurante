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
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
             $table->foreignId('orden_id')->constrained('ordenes')->onDelete('cascade');
            $table->enum('tipo', ['boleta', 'factura', 'ninguno'])->default('boleta');
            $table->string('serie', 10); // "B001", "F001"
            $table->integer('numero'); // Correlativo
            $table->string('cliente_documento', 20)->nullable(); // DNI o RUC
            $table->string('cliente_nombre')->nullable();
            $table->string('cliente_direccion')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('igv', 10, 2);
            $table->decimal('total', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia']);
            $table->enum('estado', ['emitido', 'anulado'])->default('emitido');
            $table->text('motivo_anulacion')->nullable();
            $table->string('ruta_pdf')->nullable();
            $table->timestamp('anulado_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
