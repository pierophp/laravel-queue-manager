<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QueueConfigAddAggregator extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('queue_config', function (Blueprint $table) {
            $table->string('aggregator')->nullable()->index();
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
            $table->dropColumn('aggregator');
        });
    }
}
