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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_venta', 10, 2);
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);

            $table->decimal('precio_compra', 10, 2)->nullable();
            $table->integer('stock_actual')->nullable();
            $table->integer('stock_minimo')->nullable();
            $table->string('unidad_medida', 20)->nullable(); 
            $table->string('sku', 50)->unique()->nullable();

            $table->enum('tipo_producto', ['preparado', 'comprado'])->default('preparado');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
