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
        Schema::create('servicio_tecnico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('cascade');
            $table->string('factura');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');//deberia ser una fk hacia el usuario de rol tecnico
            $table->string('serie');
            $table->dateTime('fecha_salida')->nullable();
            $table->string('estado')->default('Pendiente');
            $table->boolean('pagado')->default(0);
            $table->string('razon_social');
            $table->string('dni_cuit');
            $table->string('cliente_distribuidor');
            $table->string('contacto');
            $table->string('vendedor');
            $table->string('direccion');
            $table->string('provincia');
            $table->string('localidad');
            $table->string('telefono');
            $table->string('email');
            $table->string('horarios');
            $table->foreignId('problema_id')->constrained('problemas')->onDelete('cascade');
            $table->foreignId('subproblema_id')->constrained('subproblemas')->onDelete('cascade');
            $table->string('interno_externo');
            $table->string('reinc')->nullable(); //si (celeste) / no (verde)
            $table->decimal('cantidad', 8, 0)->nullable();
            $table->decimal('importe')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio_tecnico');
    }
};
