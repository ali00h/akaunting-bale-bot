<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBaleBot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bale_bot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('chat_id');
            $table->string('json_data', 600);
            $table->dateTime('start_date');
            $table->smallInteger('status');
            $table->tinyInteger('archive');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bale_bot');
    }
}
