<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('operario_sectores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operario_id')->constrained('operarios')->onDelete('cascade');
            $table->foreignId('sector_id')->constrained('sectores')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['operario_id', 'sector_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('operario_sectores');
    }
};