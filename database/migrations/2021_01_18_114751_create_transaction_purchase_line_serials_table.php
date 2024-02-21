<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionPurchaseLineSerialsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_purchase_line_serials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('purchase_line_id');
            $table->unsignedInteger('transaction_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id');
            $table->unsignedInteger('variation_location_serial_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_purchase_line_serials');
    }
}