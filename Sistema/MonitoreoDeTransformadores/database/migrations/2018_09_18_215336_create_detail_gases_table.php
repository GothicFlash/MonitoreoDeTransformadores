<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDetailGasesTable extends Migration
{
    public function up()
    {
        Schema::create('detail_gases', function (Blueprint $table) {
          $table->integer('monitor_id')->unsigned();
          $table->integer('gas_id')->unsigned();
          $table->foreign('monitor_id')->references('id')->on('monitors');
          $table->foreign('gas_id')->references('id')->on('gas');
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_gases');
    }
}
