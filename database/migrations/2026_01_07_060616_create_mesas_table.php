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
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 10)->unique(); 
            $table->integer('capacidad')->default(4);
            $table->string('ubicacion', 50)->nullable(); 
            $table->enum('estado', ['libre', 'ocupada', 'reservada', 'mantenimiento'])->default('libre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
