<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid("user_id"); // shop id 
            $table->uuid("dept_record_id");
            $table->integer("type"); // 1 = add dept , 0 = delete ,
            $table->uuid("good_id")->nullable(); // if type = 0
            $table->double("payment_value")->nullable(); // if type = 1
            $table->integer("quantity")->nullable(); // if type = 1
            $table->text("notes")->nullable();
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
        Schema::dropIfExists('client_logs');
    }
};