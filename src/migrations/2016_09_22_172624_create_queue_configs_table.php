<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQueueConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('class_name');
            $table->boolean('active')->index();
            $table->boolean('schedulable')->index()->nullable();
            $table->json('schedule_config')->nullable();
            $table->integer('max_attempts')->nullable();
            $table->integer('max_instances')->nullable();
            $table->integer('timeout')->nullable();
            $table->integer('delay')->nullable();
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
        Schema::dropIfExists('queue_config');
    }
}
