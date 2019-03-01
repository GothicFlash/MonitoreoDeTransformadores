<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBinnaclesTable extends Migration
{
    public function up()
    {
        Schema::create('binnacles', function (Blueprint $table) {
          $table->integer('register_id')->unsigned();
          $table->integer('gas_id')->unsigned();
          $table->decimal('ppm');
          $table->time('hour');
          $table->foreign('register_id')->references('id')->on('registers');
          $table->foreign('gas_id')->references('id')->on('gas');
        });
    }

    public function down()
    {
        Schema::dropIfExists('binnacles');
    }
}
