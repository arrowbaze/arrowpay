<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArrowbazeTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('arrowbaze_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('access_token');
            $table->dateTime('expiration_time')->nullable();
            $table->longText('raw')->nullable(); // store raw JSON response from Orange API
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('arrowbaze_tokens');
    }
}
