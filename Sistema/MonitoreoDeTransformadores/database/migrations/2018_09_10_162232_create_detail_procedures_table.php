<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDetailProceduresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_procedures', function (Blueprint $table) {
          $table->integer('procedure_id')->unsigned();
          $table->integer('monitor_id')->unsigned();
          $table->foreign('procedure_id')->references('id')->on('procedures');
          $table->foreign('monitor_id')->references('id')->on('monitors');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_procedures');
    }
}
