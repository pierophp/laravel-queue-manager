<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QueueConfigAddInstances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('queue_config', function (Blueprint $table) {
            $table->integer('min_instances')->nullable();
            $table->integer('current_instances')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('queue_config', function (Blueprint $table) {
            $table->dropColumn('min_instances');
            $table->dropColumn('current_instances');
        });
    }
}
