<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariationLocationSerialsTable extends Migration
{
    public function up()
    {
        Schema::create('variation_location_serials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('variation_location_detail_id');
            $table->unsignedInteger('product_id');
            $table->text('serial_number');
            $table->enum('status', ['available', 'sold', 'returned'])->default('available');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('variation_location_serials');
    }
}