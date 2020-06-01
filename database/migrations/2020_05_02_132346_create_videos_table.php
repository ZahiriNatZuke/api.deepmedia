<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->string('title')->unique();
            $table->string('description');
            $table->enum('state', ['Public', 'Private']);
            $table->enum('category', ['Gameplay', 'Musical', 'Joke', 'Interesting', 'Tech', 'Tutorial']);
            $table->string('poster');
            $table->string('video');
            $table->double('duration');
            $table->enum('type', ['video/mp4', 'video/avi', 'video/x-matroska']);
            $table->integer('views_count')->default(0);
            $table->integer('downloads_count')->default(0);
            $table->timestamps();
            $table->index('channel_id');
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('videos');
    }
}
