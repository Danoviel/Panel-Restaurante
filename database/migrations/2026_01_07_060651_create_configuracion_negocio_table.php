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
        Schema::create('configuracion_negocio', function (Blueprint $table) {
            $table->id();
            // Datos del negocio
            $table->string('nombre_comercial', 200);
            $table->string('ruc', 20)->nullable();
            $table->text('direccion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('logo_path')->nullable();
            
            // Tipo de restaurante
            $table->enum('tipo_restaurante', ['casual', 'formal', 'premium'])->default('casual');
            
            // Configuración de comprobantes
            $table->boolean('emite_boletas')->default(true);
            $table->string('serie_boleta', 10)->default('B001');
            $table->integer('numero_actual_boleta')->default(0);
            $table->boolean('emite_facturas')->default(false);
            $table->string('serie_factura', 10)->default('F001');
            $table->integer('numero_actual_factura')->default(0);
            $table->decimal('porcentaje_igv', 5, 2)->default(18.00);
            $table->boolean('incluye_servicio')->default(false);
            $table->decimal('porcentaje_servicio', 5, 2)->default(10.00);
            
            // Funciones activadas
            $table->boolean('usa_mesas')->default(true);
            $table->boolean('usa_reservas')->default(false);
            $table->boolean('usa_clientes_frecuentes')->default(false);
            $table->boolean('usa_programa_puntos')->default(false);
            $table->boolean('usa_delivery')->default(false);
            $table->boolean('usa_para_llevar')->default(false);
            
            // Sistema
            $table->boolean('configuracion_completada')->default(false);
            $table->string('moneda', 10)->default('PEN');
            $table->string('zona_horaria', 50)->default('America/Lima');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_negocio');
    }
};
