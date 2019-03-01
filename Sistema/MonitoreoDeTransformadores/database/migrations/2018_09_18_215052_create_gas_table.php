<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGasTable extends Migration
{
    public function up()
    {
        Schema::create('gas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique()->required();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gas');
    }
}
