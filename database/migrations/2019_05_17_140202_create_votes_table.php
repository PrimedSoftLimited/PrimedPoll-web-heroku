<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->unsignedInteger('voter_id');
            $table->unsignedInteger('option_id');
            $table->unsignedInteger('poll_id');
            $table->timestamps();

            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('voter_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('option_id')
                ->references('id')
                ->on('options')
                ->onDelete('cascade');

            $table->foreign('poll_id')
                ->references('poll_id')
                ->on('options')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('votes');
    }
}
