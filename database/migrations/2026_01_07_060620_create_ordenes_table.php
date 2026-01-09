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
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();
             $table->foreignId('mesa_id')->nullable()->constrained('mesas')->onDelete('set null');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade'); // Mesero que atendió
            $table->enum('estado', ['pendiente', 'en_preparacion', 'servido', 'pagado', 'cancelado'])->default('pendiente');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('impuesto', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('tipo_servicio', ['salon', 'delivery', 'para_llevar'])->default('salon');
            $table->integer('numero_personas')->default(1);
            $table->text('notas')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
