<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdenFabricacionIdToControlStockTable extends Migration
{
    public function up()
    {
        Schema::table('control_stock', function (Blueprint $table) {
            $table->unsignedBigInteger('orden_fabricacion_id')->nullable()->after('id');
            $table->foreign('orden_fabricacion_id')->references('id')->on('ordenes_fabricacion')->onDelete('cascade');
   


            $table->index(['orden_fabricacion_id', 'modelo_id']);
        });
    }

    public function down()
    {
        Schema::table('control_stock', function (Blueprint $table) {
            $table->dropForeign(['orden_fabricacion_id']);
            $table->dropIndex(['orden_fabricacion_id', 'modelo_id']);
            $table->dropColumn('orden_fabricacion_id');
        });
    }
}
