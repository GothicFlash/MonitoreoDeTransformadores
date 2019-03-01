<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDetailTransformersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_transformers', function (Blueprint $table) {
          $table->integer('monitor_id')->unsigned();
          $table->integer('transformer_id')->unsigned();
          $table->timestamp('created_at')->required();
          $table->foreign('monitor_id')->references('id')->on('monitors');
          $table->foreign('transformer_id')->references('id')->on('transformers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_transformers');
    }
}
